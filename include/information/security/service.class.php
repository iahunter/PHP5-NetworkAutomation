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

class Security_Service	extends Information
{
	public $category = "Security";
	public $type = "Security_Service";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'		]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		if (
				( $NEWDATA["protocol"] == "tcp" || $NEWDATA["protocol"] == "udp" || $NEWDATA["protocol"] == "tcp-udp" ) &&
				( intval($NEWDATA["port"]) < 1 || intval($NEWDATA["port"]) > 65534 ) )
		{
			$this->data['error'] .= "ERROR: port number provided is not valid 1-65534!\n";
			return 0;
		}

		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing information with the same name!
					"category"		=> $this->category,
					"type"			=> $this->type,
					"stringfield1"	=> $NEWDATA["name"],
					);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)
			{
				$DUPLICATE = reset($RESULTS);
				$this->data['error'] .= "ERROR: Found duplicate {$this->category}/{$this->type} ID {$DUPLICATE} with {$NEWDATA["name"]}!\n";
				return 0;
			}
		}

		return 1;
	}
/*
	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by stringfield1";
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
/**/
	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 100;	// Name
		$this->html_width[$i++] = 100;	// Protocol
		$this->html_width[$i++] = 300;	// Description
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
			<caption class="report">Service List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Protocol</th>
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
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["protocol"]} / {$this->data["port"]}</td>
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
			<caption class="report">Service Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Protocol</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);

		$rowclass = "row".(($i % 2)+1); $i++;
		$CREATED_BY	 = $this->created_by();
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

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Service Name"					);
		$SELECT = array(
			"tcp" => "TCP",
			"udp" => "UDP",
			"tcp-udp" => "TCP/UDP",
			"icmp" => "ICMP",
		);
		$OUTPUT .= $this->html_form_field_select("protocol"	,"Protocol"			,$SELECT	);
		$OUTPUT .= $this->html_form_field_text("port"		,"Port (1-65534)"				);
		$OUTPUT .= $this->html_form_field_text("description","Description"					);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$OUTPUT .= "  " . Utility::last_stack_call(new Exception);
		$OUTPUT .= "  ! SERVICE {$this->data["id"]} CONFIGURATION: {$this->data["protocol"]}/{$this->data["port"]} {$this->data["description"]}\n";
		$OUTPUT .= "  service-object {$this->data["protocol"]} destination eq {$this->data["port"]}\n";

		return $OUTPUT;
	}

}

?>
