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

require_once "information/datacenter/distributionblock.class.php";

class Datacenter_Vlan	extends Datacenter_DistributionBlock
{
	public $type = "Datacenter_Vlan";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$this->data["vlan"] = intval($this->data["vlan"]);
		$CHANGED = 0;
		$CHANGED += $this->customfield("vlan"			,"stringfield0");
		$CHANGED += $this->customfield("name"			,"stringfield1");
		$CHANGED += $this->customfield("description"	,"stringfield2");
		$CHANGED += $this->customfield("gateway"		,"stringfield3");
		$CHANGED += $this->customfield("otv"			,"stringfield4");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['vlan'			]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
		$DB->bind("STRINGFIELD2"	,$this->data['description'	]);
		$DB->bind("STRINGFIELD3"	,$this->data['gateway'		]);
		$DB->bind("STRINGFIELD4"	,$this->data['otv'			]);
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by ABS(stringfield0),id";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type']);
			$DB->bind("CATEGORY",$this->data['category']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		return $RESULTS;
	}

	public function validate($NEWDATA)
	{
		$VLAN = intval($NEWDATA["vlan"]);
		if ( $VLAN < 1 || $VLAN > 4094 )
		{
			$this->data['error'] .= "ERROR: VLAN ID is not valid, 1-4094!\n";
			return 0;
		}

		if ( $NEWDATA["name"] == "" )
		{
			$this->data['error'] .= "ERROR: VLAN name must be set!\n";
			return 0;
		}

		return 1;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 50;	// ID
		$this->html_width[$i++] = 100;	// VLAN
		$this->html_width[$i++] = 150;	// Name
		$this->html_width[$i++] = 200;	// Description
		$this->html_width[$i++] = 120;	// Gateway
		$this->html_width[$i++] = 50;	// OTV
		$this->html_width[0]	= array_sum($this->html_width);
	}

    public function html_list_header()
    {
        $COLUMNS = array("ID","VLAN","Name","Description","Gateway","OTV");
        $OUTPUT = $this->html_list_header_template("Datacenter VLAN List",$COLUMNS);
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
                    <td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['vlan']}</a></td>
                    <td class="report" width="{$this->html_width[$i++]}">{$this->data['name']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['description']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['gateway']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['otv']}</td>
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

        $COLUMNS = array("ID","VLAN","Name","Description","Gateway","OTV");
        $OUTPUT .= $this->html_list_header_template("Datacenter VLAN Detail",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= $this->html_list_footer();		

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
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
				$OUTPUT .= $CHILD->html_list_header();
				foreach ($CHILDREN as $CHILD)
				{
					$OUTPUT .= $CHILD->html_list_row($i++);
				}
				$OUTPUT .= $CHILD->html_list_footer();
			}
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		if (!$this->data["vlan"]) { unset($this->data["vlan"]); }
		$SELECT = \metaclassing\Utility::assocRange(1,4094);
		$OUTPUT .= $this->html_form_field_select("vlan"			,"Vlan"			,$SELECT	);
		$OUTPUT .= $this->html_form_field_text	("name"			,"VLAN Name"				);
		$OUTPUT .= $this->html_form_field_text	("description"	,"Description"				);
		$OUTPUT .= $this->html_form_field_text	("gateway"		,"Gateway/mask"				);
		$SELECT = array(
							"no" => "no",
							"yes"=> "yes",
						);
		$OUTPUT .= $this->html_form_field_select("otv"			,"OTV?"			,$SELECT	);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}
