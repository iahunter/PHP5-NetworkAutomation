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

class Checklist_Server	extends Information
{
	public $category = "Checklist";
	public $type = "Checklist_Server";
	public $customfunction = "";
	public $html_list_page_items = 50;

/*	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by ABS(stringfield0),id";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","Server%");
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
/**/
	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where ( type LIKE 'server_%' AND type NOT LIKE 'server_decom' ) AND category = 'checklist' AND active = 1 ORDER BY stringfield1";
		$DB->query($QUERY);
		try {
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

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 100;	// Ticket
		$this->html_width[$i++] = 150;	// Site
		$this->html_width[$i++] = 100;	// Contact
		$this->html_width[$i++] = 150;	// BPO
		$this->html_width[$i++] = 250;	// Hostname
		$this->html_width[$i++] = 100;	// Creator
		$this->html_width[$i++] = 300;	// Modified date/by
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Ticket","Site","Contact","BPO","Hostname","Created By","Modified");
		$OUTPUT = $this->html_list_header_template("Server Build Checklist",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$CREATOR = $this->created_by();
		$ROWCLASS = "row".(($i % 2)+1); $i = 1;
		$OUTPUT .= <<<END

				<tr class="{$ROWCLASS}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['ticket']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['site']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['contact']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['bpo']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['name']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$CREATOR}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['modifiedwhen']} by {$this->data['modifiedby']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		$OUTPUT .= $this->html_detail_buttons();
		$COLUMNS = array("ID","Ticket","Site","Contact","BPO","Hostname","Created By","Modified");
		$OUTPUT .= $this->html_list_header_template("Server Build Checklist",$COLUMNS);
		$OUTPUT .= $this->html_list_row($i++);
		$OUTPUT .= $this->html_detail_rows();
		$COLUMNCOUNT = count($COLUMNS);
/*		$DUMP = trim(dumper_to_string($this->data));
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$COLUMNCOUNT}">
						Device Details:
						<pre>{$DUMP}</pre>
					</td>
				</tr>
END;	/**/
		$OUTPUT .= $this->html_list_footer();

		$CHILDREN = $this->children($this->id,"Item%","Checklist");
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

		return $OUTPUT;
	}

	public function html_detail_rows($i)
	{
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("ticket"		,"Ticket Number"				);
		$OUTPUT .= $this->html_form_field_text("site"		,"Site"					);
		$OUTPUT .= $this->html_form_field_text("contact"	,"Contact"				);
		$OUTPUT .= $this->html_form_field_text("bpo"		,"BPO"					);
		$OUTPUT .= $this->html_form_field_text("name"		,"Hostname"				);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function html_form_extended()
	{
		$OUTPUT = "";
		if ($this->data['type'] == "Server")	// This is important. Only change device type if this is the first base instance!
		{
			$SELECT = array(
				"Server_Windows"	=> "Windows",
//				"Server_VMware"		=> "ESXi",
				"Server_Linux"		=> "Linux",
			);
			$OUTPUT .= $this->html_form_field_select("newtype","Server Type",$SELECT);
		}
		return $OUTPUT;
	}

	public function checklist_extended()
	{
		// This can be overridden on child-of-child types for extending the checklist items!
	}

	public function addtasks($TASKS)
	{
		foreach($TASKS as $TASK)
		{
			$TYPE		= "ItemValidated";
			$CATEGORY	= $this->data['category'];
			$PARENT		= $this->data['id'];
			$ITEM		= Information::create($TYPE,$CATEGORY,$PARENT);
			$ITEM->data["task"] = $TASK;
			$ITEM->data["completed"] = "No";
			$ITEM->data["validated"] = "No";
			$ID = $ITEM->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			global $DB;
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$ITEM = Information::retrieve($ID);
			$ITEM->update();
		}
	}

}

?>
