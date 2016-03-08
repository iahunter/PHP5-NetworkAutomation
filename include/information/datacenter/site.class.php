<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once "information/information.class.php";

class Datacenter_Site	extends Information
{
	public $category = "Datacenter";
	public $type = "Datacenter_Site";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("sitecode"	,"stringfield0");
		$CHANGED += $this->customfield("name"		,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['sitecode'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["sitecode"] == "")
		{
			$this->data['error'] .= "ERROR: site code provided is not valid!\n";
			return 0;
		}

		// If we are EDITING existing data AND changing the site code, REJECT the change!
		if ( isset($this->data["id"]) && $NEWDATA["sitecode"] != $this->data["sitecode"] )
		{
			$this->data['error'] .= "ERROR: site codes may NOT be changed!\n";
			return 0;
		}

		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing sites with the same name!
					"category"		=> "Datacenter",
					"type"			=> "Site",
					"stringfield1"	=> $NEWDATA["sitecode"],
					);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)
			{
				$DUPLICATE = reset($RESULTS);
				$this->data['error'] .= "ERROR: Found existing datacenter site ID {$DUPLICATE} with the same sitecode!\n";
				return 0;
			}
		}

		return 1;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 50;	// ID
		$this->html_width[$i++] = 150;	// Site Code
		$this->html_width[$i++] = 250;	// Name
		$this->html_width[0]	= array_sum($this->html_width);
	}

    public function html_list_header()
    {
        $COLUMNS = array("ID","Site Code","Name");
        $OUTPUT = $this->html_list_header_template("Datacenter Site Details",$COLUMNS);
        return $OUTPUT;
    }

    public function html_list_row($i = 1)
    {
        $OUTPUT = "";
        $this->html_width();
        $rowclass = "row".(($i % 2)+1);
        $columns = count($this->html_width)-1;  $i = 1;
        $OUTPUT .= <<<END

                <tr class="{$rowclass}">
                    <td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
                    <td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['sitecode']}</a></td>
                    <td class="report" width="{$this->html_width[$i++]}">{$this->data['name']}</td>
                </tr>
END;
        return $OUTPUT;
    }

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();

		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$rowclass = "row1";

        $COLUMNS = array("ID","Site Code","Name");
        $OUTPUT .= $this->html_list_header_template("Datacenter Site List",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= $this->html_list_footer();		

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array("DistributionBlock");
		foreach ($CHILDTYPES as $CHILDTYPE)
		{
			$CHILDTYPEOBJECT = Information::create($CHILDTYPE,$this->category,$this->id);
			$CHILDTYPEOBJECT->html_width();
			$OUTPUT .= <<<END
			<table width="{$CHILDTYPEOBJECT->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->category}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
END;
			$CHILDREN = $this->children($this->id,$CHILDTYPE,$this->category);
			$i = 1;
			if (!empty($CHILDREN))
			{
				$CHILD = reset($CHILDREN);
//				$OUTPUT .= $CHILD->html_list_header();
				foreach ($CHILDREN as $CHILD)
				{
					$OUTPUT .= "			<hr size=1>\n";
					$OUTPUT .= $CHILD->html_detail($i++);
				}
//				$OUTPUT .= $CHILD->html_list_footer();
			}
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_comment("sitecode"    ,"Site Code (Pulled from sharepoint)");

		$OUTPUT .= $this->html_form_field_text	("name"			,"Site Name"							);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}
