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

class Group	extends Information
{
	public $category = "";
	public $type = "Group";
	public $customfunction = "";

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 400;	// Name
		$this->html_width[$i++] = 200;	// Description
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">{$this->data["type"]} List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$columns = count($this->html_width)-1;	$i = 1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["id"]}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data["id"]}">{$this->data["name"]}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["description"]}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$columns = count($this->html_width)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Group Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);

		$rowclass = "row".(($i % 2)+1); $i++;
		$CREATED_BY		= $this->created_by();
		$CREATED_WHEN	= $this->created_when();
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Created by {$CREATED_BY} on {$CREATED_WHEN}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);

		$OUTPUT .= $this->html_list_footer();

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		array_push($CHILDTYPES,"Link");
		foreach ($CHILDTYPES as $CHILDTYPE)
		{
			$OUTPUT .= <<<END

			<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
END;
			$CHILDREN = $this->children($this->id,$CHILDTYPE . "%",$this->category);

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
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		if (!isset($this->data["name"])) { $this->data["name"] = $this->parent()->data["name"] . " "; }
		$OUTPUT .= $this->html_form_field_text("name"		,"Group Name"							);
		$OUTPUT .= $this->html_form_field_text("description","Description"								);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
