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

require_once "information/datacenter/site.class.php";

class Datacenter_DistributionBlock	extends Datacenter_Site
{
	public $type = "Datacenter_DistributionBlock";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("blocktype"	,"stringfield0");
		$CHANGED += $this->customfield("name"		,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['blocktype'	]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		return 1;
	}

	// This is needed to override the default order of children by ID and instead order it by VLAN number
	public function children($ID = 0, $TYPE = "", $CATEGORY = "", $ACTIVE = -1)
	{
		if ($ACTIVE < 0) { $ACTIVE = intval($this->data['active']); }
		if ($ID == 0) { $ID = $this->data['id']; }
		$QUERY = "SELECT id FROM information WHERE parent = :ID AND active = :ACTIVE";
		if ($TYPE != "") { $QUERY .= " and type like :TYPE"; }
		if ($CATEGORY != "") { $QUERY .= " and category like :CATEGORY"; }
		$QUERY .= " order by category,type,ABS(stringfield0),id";

		global $DB;
		$DB->query($QUERY);
		try {
			$DB->bind("ID",$ID);
			$DB->bind("ACTIVE",$ACTIVE);
			if ($TYPE 		!= "") { $DB->bind("TYPE"		,$TYPE);	}
			if ($CATEGORY	!= "") { $DB->bind("CATEGORY"	,$CATEGORY);}
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}

		$CHILDREN = array();
		foreach ($RESULTS as $CHILD)
		{
			array_push($CHILDREN, Information::retrieve($CHILD['id']));
		}
		return $CHILDREN;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 50;	// ID
		$this->html_width[$i++] = 250;	// Name
		$this->html_width[$i++] = 150;	// BlockType
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Name","Type");
		$OUTPUT = $this->html_list_header_template("Datacenter Distribution Block List",$COLUMNS);
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
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['blocktype']}</td>
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

		$COLUMNS = array("ID","Name","Type");
		$OUTPUT .= $this->html_list_header_template("Datacenter Distribution Block Details",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= $this->html_list_footer();		

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array("Vlan");
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
		$OUTPUT = "";\metaclassing\Utility::dumper($this);
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$SELECT = array(
							"Datacenter" => "Datacenter",
							"Office" => "Office",
							"Internet Gateway" => "Internet Gateway",
							"Management" => "Management",
							"Other" => "Other",
						);
		$OUTPUT .= $this->html_form_field_select("blocktype","Type",$SELECT				);
		$OUTPUT .= $this->html_form_field_text	("name"		,"Name"						);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}
