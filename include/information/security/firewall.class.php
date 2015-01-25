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

class Security_Firewall	extends Information
{
	public $category = "Security";
	public $type = "Security_Firewall";
	public $customfunction = "Config";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("link"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['link'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'		]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing information with the same name!
					"category"		=> $this->category,
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
		$this->html_width[$i++] = 200;	// Name
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
			<caption class="report">Firewall List</caption>
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
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
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
			<caption class="report">Firewall Details</caption>
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

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		array_push($CHILDTYPES,"Firewall_Interface","NAT");
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

		$OUTPUT .= $this->html_form_field_text("name"		,"Firewall Name"								);
		$OUTPUT .= $this->html_form_field_text("link"		,"Linked Firewall Management Object ID"			);
		$OUTPUT .= $this->html_form_field_text("description","Description"									);
		if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 0 )
		{
			$OUTPUT .= $this->html_form_field_textarea("customconfig","Custom Config"								);
		}
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$SEARCH = array(
				"category"		=>	"Management",
				"type"			=>	"Device_Network_%",
				"id"			=>	"{$this->data["link"]}",
			);
		$ID_MANAGED = Information::search($SEARCH);
		if ( count($ID_MANAGED) )
		{
			$ID_MANAGED = reset($ID_MANAGED);
			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$this->data["id"]}&action=Audit">Audit Config</a>
						</li>
					</ul>
END;
		}

		$OUTPUT .= "<br><pre>\n";
		$OUTPUT .= Utility::last_stack_call(new Exception);
		$OUTPUT .= "!\n! Firewall ID {$this->data["id"]} Name: {$this->data["name"]} Description: {$this->data["description"]}\n!\n";

		// Configure the NATs for this firewall first
		$SEARCH = array(
					"category"	=> "Security",
					"type"		=> "NAT",				// Look for NAT objects
					"parent"	=> $this->data["id"],	// If they are our children
				);
		$NATS = Information::search($SEARCH);
		foreach ($NATS as $NAT_ID)
		{
			$NAT = Information::retrieve($NAT_ID);
			$OUTPUT .= $NAT->config($ACL);
		}

		/*****************************************************************
		*	Get our attached interfaces and sort them by security level
		*****************************************************************/
		$SEARCH = array(
					"category"		=> "Security",
					"type"			=> "Firewall_Interface",	// Look for firewall interfaces
					"parent"		=> $this->data["id"],		// Attached to this firewall
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for HostGroups ordered by stringfield1 (name)
		$INTERFACES = array();
		foreach ($RESULTS as $RESULT) { array_push( $INTERFACES , Information::retrieve($RESULT) ); }
		usort($INTERFACES, array("Security_Firewall_Interface","sort_by_security_level"));	// Sort from low security level to high!

		/*****************************************************************
		*	Build interface pairs (ordered LOW->HIGH)
		*****************************************************************/
		$INTERFACE_ITERATOR = new ArrayIterator($INTERFACES);
		while( $INTERFACE_ITERATOR->valid() )
		{
			$OUTSIDE = $INTERFACE_ITERATOR->current();
			$INTERFACE_ITERATOR->next();
			if ( $INTERFACE_ITERATOR->valid() )	// If the next element is valid, push the inside,outside pair into the array!
			{
				$INSIDE = $INTERFACE_ITERATOR->current();
				$OUTPUT .= "! Building rules for interface pair: SOURCE:{$OUTSIDE->data["name"]} --to-> DESTINATION:{$INSIDE->data["name"]} \n";
				$ACL = "ACL_{$OUTSIDE->data["name"]}_{$INSIDE->data["name"]}";
				$OUTPUT .= "access-list {$ACL} extended permit icmp any any\n";
				/********************************************************************************************
				*	Find host groups for the OUTSIDE zone													*
				*********************************************************************************************/
				$SEARCH = array(
							"category"		=> "Security",
							"type"			=> "Group_Host",			// Look for host groups
							"stringfield2"	=> $OUTSIDE->data["zone"],	// In the outside security zone
						);
				$OUTSIDE_HOSTGROUPS = Information::search($SEARCH);
				/********************************************************************************************
				*	Find host groups for the INSIDE zone													*
				*********************************************************************************************/
				$SEARCH = array(
							"category"		=> "Security",
							"type"			=> "Group_Host",			// Look for host groups
							"stringfield2"	=> $INSIDE->data["zone"],	// In the outside security zone
						);
				$INSIDE_HOSTGROUPS = Information::search($SEARCH);
				/****************************************************************************************************
				*	Find application components with SRCHOST in INSIDE_HOSTGROUPS and DSTHOST in OUTSIDE_HOSTGROUPS *
				*****************************************************************************************************/
				$OUTSIDE_SEARCH	= implode( ","	,	$OUTSIDE_HOSTGROUPS	);
				$INSIDE_SEARCH	= implode( "," 	,	$INSIDE_HOSTGROUPS	);
				//$OUTPUT .= "OUTSIDE SEARCH LIST: $OUTSIDE_SEARCH\n";	$OUTPUT .= "INSIDE SEARCH LIST: $INSIDE_SEARCH\n";
				global $DB; // Our Database Wrapper Object
				$QUERY = "
					SELECT id FROM information
					WHERE type LIKE :TYPE
					AND category LIKE :CATEGORY
					AND active = 1
					AND stringfield2 IN({$OUTSIDE_SEARCH})
					AND stringfield3 IN({$INSIDE_SEARCH})
					ORDER BY parent";
				$DB->query($QUERY);
				try {
					$DB->bind("TYPE","Application_Component");
					$DB->bind("CATEGORY","Security");
					$DB->execute();
					$RESULTS = $DB->results();
				} catch (Exception $E) {
					$MESSAGE = "Exception: {$E->getMessage()}";
					trigger_error($MESSAGE);
					global $HTML;
					die($MESSAGE . $HTML->footer());
				}
//				$OUTPUT .= "QUERY: $QUERY\n";
//				dumper($RESULTS);	// Hopefully this is an array of application_components with src and dest matching our interface zones!
				$COUNT = count($RESULTS);
				$OUTPUT .= "! Found {$COUNT} application components requiring rules in this firewall\n";
				/************************************************************************************************
				*	We have now found all the interface components applicable to this firewall interface pair	*
				*	So, lets tell those suckers to go configure themselves!										*
				************************************************************************************************/
//				$OUTPUT .= $this->config_appcomponents($RESULTS,$ACL);
				$OUTPUT .= $this->config_appcomponents_gearman($RESULTS,$ACL);
/*				foreach($RESULTS as $RESULT)
				{
					$OUTPUT .= "\n!\n";
					$APPLICATION_COMPONENT = Information::retrieve($RESULT["id"]);
					$OUTPUT .= $APPLICATION_COMPONENT->config($ACL);
				}/**/
			}
		}

		// Last print out any custom configuration
		if ( isset($this->data["customconfig"]) )
		{
			$OUTPUT .= "!CUSTOM CONFIG ADDED BY TOOL:\n";
			$OUTPUT .= "{$this->data["customconfig"]}\n";
		}
		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

	function config_appcomponents($RESULTS,$ACL)
	{
		$OUTPUT = "";
		foreach($RESULTS as $RESULT)
		{
			$OUTPUT .= "\n!\n";
			$APPLICATION_COMPONENT = Information::retrieve($RESULT["id"]);
			$OUTPUT .= $APPLICATION_COMPONENT->config($ACL);
		}
		return $OUTPUT;
	}

	function config_appcomponents_gearman($RESULTS,$ACL)
	{
		$OUTPUT = "";

		// Process the resulting items one at a time to add to the list table & sandwich them between header and footer
		if (count($RESULTS) > 0)
		{
			// Loop through all the remaining items IN PARALLEL WITH GEARMAN!!!
			$GEARMAN	= new Gearman_Client;
			$FUNCTION	= "information-action";
			$QUEUE		= "web";
			$WORK		= "{$FUNCTION}-{$QUEUE}";
			$i = 1;
			foreach ($RESULTS as $RECORD)
			{
				$DATA = array();
				$DATA["id"] = $RECORD["id"];
				$DATA["method"] = "config_gearman";
				$DATA["acl"] = $ACL;
				$GEARMAN->addTask($WORK, $DATA);
			}
			$GEARMAN->setTimeout(12000);// Make sure that we have a timeout set (12 seconds)
			if (! $GEARMAN->runTasks()) // Now run all those tasks in parallel!
			{
				global $DB; $DB->log("GEARMAN ERROR: " . $GEARMAN->error() );
				return "<b>ERROR:</b> " . $GEARMAN->error() . " <b>Attempting to load the page without gearman...</b><br>\n" . $this->config_appcomponents($RESULTS,$ACL);
			}
			foreach ($GEARMAN->tasks as $HANDLE => $TASKINFO)
			{
				if ( isset($TASKINFO["output"]) )
				{
					$OUTPUT .= "\n!\n";
					$OUTPUT .= $TASKINFO["output"];
				}else{
					$OUTPUT .= "ERROR! " . dumper_to_string($TASKINFO);
				}
			}
		}

		return $OUTPUT;
	}

	function filter_config($CONFIG)
	{
		$LINES_IN = preg_split( '/\r\n|\r|\n/', $CONFIG );
		$LINES_OUT = array();
		$SKIP = "";
		$HOSTNAME = "";
		foreach($LINES_IN as $LINE)
		{
			// Filter out the BANNER MOTD lines
			if (preg_match("/banner \S+ (\S+)/",$LINE,$REG))   // If we encounter a banner motd or banner motd line
			{
				$SKIP = $REG[1];                  continue;     // Skip until we see this character
			}
			if ($SKIP != "" && trim($LINE) == $SKIP)            // If $SKIP is set AND we detect the end of our skip character
			{
				$SKIP = "";                       continue;     // Stop skipping and unset the character
			}
			if ($SKIP != "")                    { continue; }   // Skip until we stop skipping

			// Find the hostname to identify our prompt
			if (preg_match("/^hostname (\S+)/",$LINE,$REG)) { $HOSTNAME = $REG[1]; }
			// Filter out the prompt at the end if it exists
			if ($HOSTNAME != "" && preg_match("/^{$HOSTNAME}.+/",$LINE,$REG)) { continue; }
			if ($HOSTNAME != "" && preg_match("/.+\/{$HOSTNAME}.+/",$LINE,$REG)) { continue; } // ASA prompt in a context...

			// Ignore a bunch of unimportant often-changing lines that clutter up the config repository
			if (
				( trim($LINE) == ""										)	||	//	Ignore blank and whitespace-only lines
				( trim($LINE) == "exit"									)	||	//	Ignore exit lines (mostly provisioning lines)
				( preg_match('/.*no shut.*/'				,$LINE,$REG))	||	//	no shut/no shutdown lines from provisioning tool
				( preg_match('/.*no enable.*/'				,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*spanning-tree vlan 1-4094.*/',$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*enable secret.*/'			,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*ip domain.lookup.*/'		,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*ip domain.name.*/'			,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*crypto key generate rsa.*/',$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*log-adjacency-changes.*/'	,$LINE,$REG))	||	//	from provisioning tool
				( trim($LINE) == "end"									)	||	//	from provisioning tool
				( trim($LINE) == "wr"									)	||	//	from provisioning tool
				( trim($LINE) == "reload"								)	||	//	from provisioning tool
				( trim($LINE) == "switchport"							)	||	//	from provisioning tool
				( trim($LINE) == "snmp-server ifindex persist"			)	||	//	from provisioning tool
				( trim($LINE) == "aaa session-id common"				)	||	//	from provisioning tool
				( trim($LINE) == "ip routing"							)	||	//	from provisioning tool
				( trim($LINE) == "cdp enable"							)	||	//	from provisioning tool
				( trim($LINE) == "no ip directed-broadcast"				)	||	//	from provisioning tool
				( trim($LINE) == "no service finger"					)	||	//	from provisioning tool
				( trim($LINE) == "no service udp-small-servers"			)	||	//	from provisioning tool
				( trim($LINE) == "no service tcp-small-servers"			)	||	//	from provisioning tool
				( trim($LINE) == "no service config"					)	||	//	from provisioning tool
				( trim($LINE) == "no clock timezone"					)	||	//	from provisionnig tool
	//			( trim($LINE) == "end"									)	||	//	skip end, we dont need this yet
				( trim($LINE) == "<pre>" || trim($LINE) == "</pre>"		)	||	//	skip <PRE> and </PRE> output from html scrapes
				( substr(trim($LINE),0,1) == "!"						)	||	//	skip conf t lines
				( substr(trim($LINE),0,4) == "exit"						)	||	//	skip conf lines beginning with the word exit
				( preg_match('/.*config t.*/'				,$LINE,$REG))	||	//	skip show run
				( preg_match('/.*show run.*/'				,$LINE,$REG))	||	//	and show start
				( preg_match('/.*show startup.*/'			,$LINE,$REG))	||	//	show run config topper
				( preg_match('/^version .*/'				,$LINE,$REG))	||	//	version 12.4 configuration format
				( preg_match('/^boot-\S+-marker.*/'			,$LINE,$REG))	||	//	boot start and end markers
				( preg_match('/^Building configur.*/'		,$LINE,$REG))	||	//	ntp clock period in seconds is constantly changing
				( preg_match('/^ntp clock-period.*/'		,$LINE,$REG))	||	//	nvram config last messed up
				( preg_match('/^Current configuration.*/'	,$LINE,$REG))	||	//	current config size
				( preg_match('/.*NVRAM config last up.*/'	,$LINE,$REG))	||	//	nvram config last saved
				( preg_match('/.*uncompressed size*/'		,$LINE,$REG))	||	//	uncompressed config size
				( preg_match('/^:.*/'						,$LINE,$REG))	||	//	lines starting with : on ASA's
				( preg_match('/<.+>/'						,$LINE,$REG))	||	//	HTML
				( preg_match('/^Cryptochecksum:.*/'			,$LINE,$REG))	||	//	ASA
				( preg_match('/^ASA Version.*/'				,$LINE,$REG))	||	//	ASA version line
				( preg_match('/^!Time.*/'					,$LINE,$REG))		//	time comments
			   )
			{ continue; }

			// Ignore a bunch of unrelated config in the ASA's (we only provision the access-list and access objects!
			if (
				( preg_match('/^command-alias .*/'			,$LINE,$REG))	||	//	
				( preg_match('/^hostname .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^enable .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^no .*/'						,$LINE,$REG))	||	//	
				( preg_match('/^interface .*/'				,$LINE,$REG))	||	//	
				( preg_match('/nameif .*/'					,$LINE,$REG))	||	//	
				( preg_match('/security-level .*/'			,$LINE,$REG))	||	//	
				( preg_match('/ip address .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^dns .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^pager .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^logging .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^mtu .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^monitor-interface .*/'		,$LINE,$REG))	||	//	
				( preg_match('/^icmp .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^arp .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^access-group .*/'			,$LINE,$REG))	||	//	
				( preg_match('/^route .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^timeout .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^aaa-server .*/'				,$LINE,$REG))	||	//	
				( preg_match('/key .*/'						,$LINE,$REG))	||	//	
				( preg_match('/^user-identity .*/'			,$LINE,$REG))	||	//	
				( preg_match('/^aaa .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^http .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^snmp-server .*/'			,$LINE,$REG))	||	//	
				( preg_match('/^crypto .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^ssh .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^telnet .*/'					,$LINE,$REG))	||	//	
				( preg_match('/^threat-detection .*/'		,$LINE,$REG))	||	//	
				( preg_match('/^username .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^class-map .*/'				,$LINE,$REG))	||	//	
				( preg_match('/^policy-map .*/'				,$LINE,$REG))	||	//	
				( preg_match('/flow-export .*/'				,$LINE,$REG))	||	//	
				( preg_match('/parameters.*/'				,$LINE,$REG))	||	//	
				( preg_match('/match .*/'					,$LINE,$REG))	||	//	
				( preg_match('/message-length .*/'			,$LINE,$REG))	||	//	
				( preg_match('/class .*/'					,$LINE,$REG))	||	//	
				( preg_match('/inspect .*/'					,$LINE,$REG))	||	//	
				( preg_match('/user-statistics .*/'			,$LINE,$REG))	||	//	
				( preg_match('/set .*/'						,$LINE,$REG))	||	//	
				( preg_match('/^service-policy .*/'			,$LINE,$REG))	||	//	
				( preg_match('/^terminal .*/'				,$LINE,$REG))	||	//	
				( preg_match('/domain-name .*/'				,$LINE,$REG))		//	
			   )
			{ continue; }

			// If we have UTC and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* UTC$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we have CST and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* CST$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we have CDT and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/. *CDT$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we find a control code like ^C replace it with ascii ^C
			$LINE = str_replace(chr(3),"^C",$LINE);

			// If we find the prompt, break out of this function, end of command output detected
			if (isset($DELIMITER) && preg_match($DELIMITER,$LINE,$REG))
			{
				break;
			}

			// If we find a line with a tacacs key in it, HIDE THE KEY!
			if ( preg_match('/(\s*server-private 10.252.12.10. timeout . key) .*/',$LINE,$REG) )
			{
				$LINE = $REG[1];	// Strip out the KEYS from a server-private line!
			}


			array_push($LINES_OUT, $LINE);
		}

		// REMOVE blank lines from the leading part of the array and REINDEX the array
		while ($LINES_OUT[0] == ""	&& count($LINES_OUT) > 2 ) { array_shift	($LINES_OUT); }

		// REMOVE blank lines from the end of the array and REINDEX the array
		while (end($LINES_OUT) == ""	&& count($LINES_OUT) > 2 ) { array_pop	($LINES_OUT); }

		// Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
		array_push($LINES_OUT, "");

		$CONFIG = implode("\n",$LINES_OUT);

		return $CONFIG;
	}

	public function audit()
	{
		$OUTPUT = "";

		$ID_PROVISIONED = $this->data["id"];

		$SEARCH = array(
				"category"		=>	"Management",
				"type"			=>	"Device_Network_%",
				"id"			=>	"{$this->data["link"]}",
			);
		$ID_MANAGED = Information::search($SEARCH);
		if ( count($ID_MANAGED) )
		{
			$ID_MANAGED = reset($ID_MANAGED);

			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$ID_MANAGED}&action=Scan">Rescan Device</a>
						</li>
					</ul>
END;

		}else{
			$OUTPUT .= "Error: No linked firewall management object could be retrieved!\n";
			return $OUTPUT;
		}

		$DEVICE_MANAGED = Information::retrieve($ID_MANAGED);

		$CONFIG_PROVISIONED	=	$this->config();
		$CONFIG_MANAGED		=	$DEVICE_MANAGED->data["run"];     // Get the actual show run contents from the device

		$STRUCTURE_PROVISIONED	=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_PROVISIONED));
		$STRUCTURE_MANAGED		=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_MANAGED)	);

		$MISSING	=	array_diff_assoc_recursive($STRUCTURE_PROVISIONED	,$STRUCTURE_MANAGED		);
		$EXTRA		=	array_diff_assoc_recursive($STRUCTURE_MANAGED		,$STRUCTURE_PROVISIONED	);

			$OUTPUT .= "
				<table>
					<tr>
						<th colspan=2>FOUND MATCHED DEVICES Provisioned ID {$ID_PROVISIONED} Managed ID {$ID_MANAGED}<br><hr size=1>
					</tr>
					<tr>
						<th colspan=2 align=left>Configuration Missing From Provisioned Base</th>
					</tr>
					<tr>
						<td colspan=2>\n";
		if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 0 )
		{
			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$this->data["id"]}&action=Deploy">Deploy Missing Config To Firewall (DANGER!)</a>
						</li>
					</ul><br>
END;
		}
			$OUTPUT .= dBug_to_string($MISSING);
//					new dBug($MISSING);

			$OUTPUT .= "<br><hr size=1><br>
					</tr>
					<tr>
						<th colspan=2 align=left>Extra Config Not From Provisioning Tool</th>
					</tr>
					<tr>
						<td colspan=2>\n";
		if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 0 )
		{
			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$this->data["id"]}&action=Cleanup">Remove Extra Config From The Firewall (DANGER!)</a>
						</li>
					</ul><br>
END;
		}
			$OUTPUT .= dBug_to_string($EXTRA);
//					new dBug($EXTRA);
/**/
			$OUTPUT .= "<br><hr size=1><br>
					</tr>

					<tr>
						<th>Provisioned Config</th>
						<th>Managed Config</th>
					</tr>
					<tr>
						<td valign=top>" . dumper_to_string($STRUCTURE_PROVISIONED) . "</td>
						<td valign=top>" . dumper_to_string($STRUCTURE_MANAGED)     . "</td>
					</tr>
			</table>\n";

		return $OUTPUT;
	}

	public function deploy()
	{
		$OUTPUT = "";

		$ID_PROVISIONED = $this->data["id"];

		$SEARCH = array(
				"category"		=>	"Management",
				"type"			=>	"Device_Network_%",
				"id"			=>	"{$this->data["link"]}",
			);
		$ID_MANAGED = Information::search($SEARCH);
		if ( count($ID_MANAGED) )
		{
			$ID_MANAGED = reset($ID_MANAGED);
		}else{
			$OUTPUT .= "Error: No linked firewall management object could be retrieved!\n";
			return $OUTPUT;
		}

		$DEVICE_MANAGED = Information::retrieve($ID_MANAGED);

		$CONFIG_PROVISIONED	=	$this->config();
		$CONFIG_MANAGED		=	$DEVICE_MANAGED->data["run"];     // Get the actual show run contents from the device

		$STRUCTURE_PROVISIONED	=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_PROVISIONED));
		$STRUCTURE_MANAGED		=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_MANAGED)	);

		$MISSING	=	array_diff_assoc_recursive($STRUCTURE_PROVISIONED	,$STRUCTURE_MANAGED		);
		$EXTRA		=	array_diff_assoc_recursive($STRUCTURE_MANAGED		,$STRUCTURE_PROVISIONED	);

		// Extract keys from associative array:
		$PUSH = $this->recursive_assoc_keys($MISSING);
//		dumper($PUSH);
		print "<pre>";
		$DEVICE_MANAGED->push($PUSH);	// PUSH THE CONFIG!
		print "</pre>";

		return $OUTPUT;
	}

	public function recursive_assoc_keys($ARRAY,$DEPTH = "")
	{
		$RETURN = array();
		foreach($ARRAY as $KEY => $VALUE)
		{
			array_push($RETURN,$DEPTH . $KEY);
			if ( is_array($VALUE) )
			{
				$VALUE = $this->recursive_assoc_keys($VALUE,$DEPTH . "  ");
				foreach($VALUE as $ELEMENT)
				{
					array_push($RETURN,$ELEMENT);
				}
			}
		}
		if ( $DEPTH != "" ) { array_push($RETURN,"exit"); }	// If we are deeper than 0 spaces, send up an exit!
		return $RETURN;
	}

	public function cleanup()
	{
		$OUTPUT = "";

		$ID_PROVISIONED = $this->data["id"];

		$SEARCH = array(
				"category"		=>	"Management",
				"type"			=>	"Device_Network_%",
				"id"			=>	"{$this->data["link"]}",
			);
		$ID_MANAGED = Information::search($SEARCH);
		if ( count($ID_MANAGED) )
		{
			$ID_MANAGED = reset($ID_MANAGED);
		}else{
			$OUTPUT .= "Error: No linked firewall management object could be retrieved!\n";
			return $OUTPUT;
		}

		$DEVICE_MANAGED = Information::retrieve($ID_MANAGED);

		$CONFIG_PROVISIONED	=	$this->config();
		$CONFIG_MANAGED		=	$DEVICE_MANAGED->data["run"];     // Get the actual show run contents from the device

		$STRUCTURE_PROVISIONED	=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_PROVISIONED));
		$STRUCTURE_MANAGED		=	$this->parse_nested_list_to_array( $this->filter_config($CONFIG_MANAGED)	);

		$MISSING	=	array_diff_assoc_recursive($STRUCTURE_PROVISIONED	,$STRUCTURE_MANAGED		);
		$EXTRA		=	array_diff_assoc_recursive($STRUCTURE_MANAGED		,$STRUCTURE_PROVISIONED	);

		// Extract keys from associative array:
		$PUSH = $this->recursive_assoc_no_keys($EXTRA);
//		dumper($PUSH);
		print "<pre>";
		$DEVICE_MANAGED->push($PUSH);	// PUSH THE CONFIG!
		print "</pre>";

		return $OUTPUT;
	}

	public function recursive_assoc_no_keys($ARRAY,$DEPTH = "")
	{
		$RETURN = array();
		foreach($ARRAY as $KEY => $VALUE)
		{
			if ( is_array($VALUE) )
			{
				array_push($RETURN,$DEPTH . $KEY);
				$VALUE = $this->recursive_assoc_no_keys($VALUE,$DEPTH . "  ");
				foreach($VALUE as $ELEMENT)
				{
					array_push($RETURN,$ELEMENT);
				}
			}else{
				array_push($RETURN,$DEPTH . "no " . $KEY);
			}
		}
		if ( $DEPTH != "" ) { array_push($RETURN,"exit"); }	// If we are deeper than 0 spaces, send up an exit!
		return $RETURN;
	}

}

?>
