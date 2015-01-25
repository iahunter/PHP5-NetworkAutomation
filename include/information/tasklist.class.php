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

class TaskList		extends Information
{
	public $data;
	public $category = "";
	public $type = "TaskList";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		$CHANGED += $this->customfield("owner"	,"stringfield2");
		$CHANGED += $this->customfield("tasks"	,"stringfield3");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function validate($NEWDATA)
	{
		if (!$NEWDATA['name'])
		{
			$this->data['error'] .= "ERROR: Task list name is required!\n";
			return 0;
		}
		if (!$NEWDATA['owner'])
		{
			$this->data['error'] .= "ERROR: Task list owner is required!\n";
			return 0;
		}
		return 1;
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
		$DB->bind("STRINGFIELD2"	,$this->data['owner'		]);
		$DB->bind("STRINGFIELD3"	,$this->data['tasks'		]);
	}

	public function children($ID = 0, $TYPE = "", $CATEGORY = "")
	{
		if ($ID == 0) { $ID = $this->data['id']; }
		$QUERY = "select id from information where parent = :ID";
		if ($TYPE != "") { $QUERY .= " and type like :TYPE"; }
		if ($CATEGORY != "") { $QUERY .= " and category like :CATEGORY"; }
		$QUERY .= " order by stringfield2,stringfield1";

		global $DB;
		$DB->query($QUERY);
		try {
			$DB->bind("ID",$ID);
			if ($TYPE 		!= "") { $DB->bind("TYPE"		,$TYPE);	}
			if ($CATEGORY	!= "") { $DB->bind("CATEGORY"	,$CATEGORY);}
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
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
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 600;	// List Name
		$this->html_width[$i++] = 150;	// Last Updated
		$this->html_width[$i++] = 150;	// Owner
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","List Name","Last Modified","Owner");
		$OUTPUT = $this->html_list_header_template("Task Lists",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$i = 1;
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['modifiedwhen']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['owner']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function recursive_assoc_array_to_ordered_list($ARRAY,$DEPTH = 0)
	{
		$OUTPUT = "";
		$OUTPUT .= "<ol class=\"nested size15 indent25\">\n";
		foreach ($ARRAY as $KEY => $ELEMENTS)
		{
			if ($DEPTH == 0) { $OUTPUT .= "<br>\n"; }
			if ($KEY[0] == "<")
			{
				$OUTPUT .= "$KEY\n";
			}else{
				$OUTPUT .= "<li class=\"nested size15\"><font style=\"font-weight: bold\">{$KEY}</font>\n";
			}
			if (is_array($ELEMENTS))
			{
				$OUTPUT .= $this->recursive_assoc_array_to_ordered_list($ELEMENTS,$DEPTH + 1);
			}
			$OUTPUT .= "</li>\n";
		}
		$OUTPUT .= "</ol>\n";
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		if ($_SESSION["AAA"]["username"] == $this->data['owner'])
		{
			$OUTPUT .= $this->html_detail_buttons();
		}
		$COLUMNS = array("ID","List Name","Last Updated","Owner");
		$COLUMNCOUNT = count($this->html_width)-1;	$i = 1;
		$OUTPUT .= $this->html_list_header_template("Task List Details",$COLUMNS);
		$OUTPUT .= $this->html_list_row();

		$TASKARRAY	= cisco_parse_nested_list_to_array($this->data['tasks']);
		$TASKDUMP	= dumper_to_string($TASKARRAY);
		$TASKLIST	= $this->recursive_assoc_array_to_ordered_list($TASKARRAY);
		$rowclass = "row1";
		$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$COLUMNCOUNT}">
						{$TASKLIST}<br>
					</td>
				</tr>
END;
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_toggle_active_button();    // Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_form_field_text("name"			,"Task List name"							);
		$SELECT = array(
			"{$_SESSION["AAA"]["username"]}"	=> "{$_SESSION["AAA"]["username"]}"
		);
		$OUTPUT .= $this->html_form_field_select("owner"		,"List Owner (Active Directory Username)",$SELECT);
		$OUTPUT .= $this->html_form_field_textarea("tasks"		,"Tasks, add SPACES for orderd list indentation",	25,100);
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
