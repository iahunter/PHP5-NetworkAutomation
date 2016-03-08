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

require_once "information/security/application.class.php";

class Security_Application_Component	extends Security_Application
{
	public $type = "Security_Application_Component";
//	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("link"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		$CHANGED += $this->customfield("srchostgroup"	,"stringfield2");
		$CHANGED += $this->customfield("dsthostgroup"	,"stringfield3");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['link'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['srchostgroup']);
		$DB->bind("STRINGFIELD3"	,$this->data['dsthostgroup']);
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 300;	// Name
		$this->html_width[$i++] = 300;	// Source HostGroup
		$this->html_width[$i++] = 300;	// Destination HostGroup
		$this->html_width[$i++] = 300;	// ServiceGroup
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
			<caption class="report">Application Component List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Users</th>
					<th class="report" width="{$this->html_width[$i++]}">Servers</th>
					<th class="report" width="{$this->html_width[$i++]}">Services</th>
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
		$datadump = \metaclassing\Utility::dumperToString($this->data);
		if ($this->data["srchostgroup"])
		{
			$SRC_HOSTGROUP	= Information::retrieve($this->data["srchostgroup"]);
			$SRC_HOSTCELL	= "<a href=\"/information/information-view.php?id={$SRC_HOSTGROUP->data["id"]}\">{$SRC_HOSTGROUP->data["name"]}</a>";
		}else{ $SRC_HOSTCELL = "None"; }
		if ($this->data["dsthostgroup"])
		{
			$DST_HOSTGROUP	= Information::retrieve($this->data["dsthostgroup"]);
			$DST_HOSTCELL	= "<a href=\"/information/information-view.php?id={$DST_HOSTGROUP->data["id"]}\">{$DST_HOSTGROUP->data["name"]}</a>";
		}else{ $DST_HOSTCELL = "None"; }
		if ($this->data["servicegroup"])
		{
			$SERVICEGROUP	= Information::retrieve($this->data["servicegroup"]);
			$SERVICECELL	= "<a href=\"/information/information-view.php?id={$SERVICEGROUP->data["id"]}\">{$SERVICEGROUP->data["name"]}</a>";
		}else{ $SERVICECELL = "None"; }/**/
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$SRC_HOSTCELL}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$DST_HOSTCELL}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$SERVICECELL}</td>
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
			<caption class="report">Application Component Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Users</th>
					<th class="report" width="{$this->html_width[$i++]}">Servers</th>
					<th class="report" width="{$this->html_width[$i++]}">Services</th>
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
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		if (!isset($this->data["name"])) { $this->data["name"] = $this->parent()->data["name"] . " "; }
		$OUTPUT .= $this->html_form_field_text("name"		,"Application Component Name"								);
		$OUTPUT .= $this->html_form_field_text("description","Description"				);

		$SEARCH = array(			// Search existing information for all hostgroups
					"category"		=> "Security",
					"type"			=> "Group_Host",
					"parent"		=> $this->data["parent"],
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for HostGroups ordered by stringfield1 (name)
		$OUTPUT .= $this->html_form_field_select("srchostgroup","User Host Group",$this->assoc_select_name($RESULTS));
		$OUTPUT .= $this->html_form_field_select("dsthostgroup","Server Host Group",$this->assoc_select_name($RESULTS));
		$SEARCH = array(			// Search existing information for all hostgroups
					"category"		=> "Security",
					"type"			=> "Group_Service",
					"parent"		=> $this->data["parent"],
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for ServiceGroups ordered by stringfield1 (name)
		$OUTPUT .= $this->html_form_field_select("servicegroup","Port/Protocol Service Group",$this->assoc_select_name($RESULTS));

		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config_gearman($DATA)
	{
		$OUTPUT = "";
		$OUTPUT = $this->config($DATA["acl"]);
		return $OUTPUT;
	}

	public function config($ACL)
	{
		$OUTPUT = "";
		if ( !isset($ACL) || $ACL == "" ) { return "ERROR! ACL NOT PASSED!\n"; }

		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= "! Application Component ID {$this->data["id"]} Name: {$this->data["name"]} Description: {$this->data["description"]}\n";

		// Configure our object groups for service, source, and destination
		$SVSGROUP = Information::retrieve($this->data["servicegroup"]);	$OUTPUT .= $SVSGROUP->config();	unset($SVSGROUP);
		$SRCGROUP = Information::retrieve($this->data["srchostgroup"]);	$OUTPUT .= $SRCGROUP->config();	unset($SRCGROUP);
		$DSTGROUP = Information::retrieve($this->data["dsthostgroup"]);	$OUTPUT .= $DSTGROUP->config();	unset($DSTGROUP);

		$REMARK .= "access-list {$ACL} remark ID {$this->data["id"]} NAME {$this->data["name"]} DESCRIPTION {$this->data["description"]}";
		$MAXLEN = 143;
		if ( strlen($REMARK) > $MAXLEN ) { $REMARK = substr($REMARK , 0 , $MAXLEN) . "..."; }	// Limit remark lines to $MAXLEN total characters!
		/*
		access-list ACL_V999:INTERNET_V901:DMZ
		access-list ACL_V901:DMZ_V101:DATACENTER remark (47 characters)
		ID 53855 NAME Cylance Syslog DMZ VIP to Flow Replicator DESCRIPTION DMZ VIP to internal flow replicator
		1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890
                 1         2         3         4         5         6         7         8         9         0         1         2         3         4         5         6
                                                                                                           1         1         1         1         1         1         1
		/**/
		$OUTPUT .= "{$REMARK}\n";	// Add our remark with a newline to the output!
		$OUTPUT .= "access-list {$ACL} extended permit object-group OBJ_SVS_{$this->data["servicegroup"]} object-group OBJ_NET_{$this->data["srchostgroup"]} object-group OBJ_NET_{$this->data["dsthostgroup"]}\n";

		return $OUTPUT;
	}

	public function html_spreadsheet()
	{
		$OUTPUT = "";

		// Configure our object groups for service, source, and destination
		$SVSGROUP = Information::retrieve($this->data["servicegroup"]);	$SVSCELL = $SVSGROUP->html_children("Link_Service");	unset($SVSGROUP);
		$SRCGROUP = Information::retrieve($this->data["srchostgroup"]);	$SRCCELL = $SRCGROUP->html_children("Link_Host");		unset($SRCGROUP);
		$DSTGROUP = Information::retrieve($this->data["dsthostgroup"]);	$DSTCELL = $DSTGROUP->html_children("Link_Host");		unset($DSTGROUP);

		$OUTPUT .= $this->html_list_header_template("Application Component ID {$this->data["id"]} Name: {$this->data["name"]} Description: {$this->data["description"]}",array("Clients","Servers","Services") );
		$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td class="report"><br>{$SRCCELL}</td>
					<td class="report"><br>{$DSTCELL}</td>
					<td class="report"><br>{$SVSCELL}</td>
				</tr>
END;
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

}
