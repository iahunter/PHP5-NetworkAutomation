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

class Blackhole_Sensor	extends Information
{
	public $category = "Blackhole";
	public $type = "Blackhole_Sensor";
	public $customfunction = "";

	// Whitelist array for exclusion
	public $WHITELIST = array(
								"10.0.0.0/8"		,	// RFC1918
								"172.16.0.0/12"		,	// RFC1918
								"192.168.0.0/16"	,	// RFC1918
								"123.456.72.0/21"	,	// Corporate IPv4 Block
							);

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
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
	}

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
			<caption class="report">Sensor List</caption>
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
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data["name"]}</a></td>
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
			<caption class="report">Sensor Details</caption>
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
		$DATADUMP = dumper_to_string($this->data);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">{$DATADUMP}</td></tr>
END;

		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	// OVERRIDE THIS IN THE CHILDREN!
	public function html_form()
	{
		$OUTPUT = "ERROR: THIS IS A PURE VIRTUAL FUNCTION! DO NOT USE THIS OBJECT TYPE RAW!\n";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Sensor Name"			);
		$OUTPUT .= $this->html_form_field_text("name"		,"Description"			);
		$OUTPUT .= $this->html_form_field_text("ip"			,"IPv4 Address"			);
		$OUTPUT .= $this->html_form_field_text("port"		,"SSH Port"				);
		$OUTPUT .= $this->html_form_field_text("user"		,"Username"				);
		$OUTPUT .= $this->html_form_field_text("pass"		,"Password"				);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function ssh_command($COMMAND)
	{
		$OUTPUT = "";

		// SSH to the NOVA honeypot host...
		require_once "command/phpseclib/Net/SSH2.php";
		$SSH = new Net_SSH2($this->data["ip"],$this->data["port"]);
		if (!$SSH->login($this->data["user"],$this->data["pass"])) { die("SSH Login Failed!"); }
		$OUTPUT = $SSH->exec($COMMAND);	// Get the output of our command
//		print "$OUTPUT\n";
		unset($SSH);					// Clear our ssh session

		return $OUTPUT;
	}

	public function whitelist_check($ADDRESS)
	{
		foreach($this->WHITELIST as $WHITELISTNET)						// Loop through every whitelisted network and check this address against it
		{
			if ( Net_IPv4::ipInNetwork($ADDRESS, $WHITELISTNET) )		// Check if this IP is within this specific whitelisted CIDR network
			{
				print "{$ADDRESS} WITHIN WHITELIST ENTRY {$WHITELISTNET}\n";
				return 1;												// If the address is whitelisted, return true
			}
		}
		return 0;														// If no whitelist entries were matched, return false
	}

	public function filter_addresses($ADDRESSES)
	{
		$RETURN = array();
		$DEL = array();
		foreach ($ADDRESSES as $ADDRESS)								// Loop through every address we need to validate and filter
		{
			$ADDRESS = trim($ADDRESS);
			if (!$ADDRESS) { continue; }								// Check if the address is blank, if it is skip it
			if ( !filter_var($ADDRESS, FILTER_VALIDATE_IP) )			// Check if the address is a valid IPv4 address
			{
				print "{$ADDRESS} DOES NOT VALIDATE AS IPv4\n";
				continue;												// Dont process this line any more, skip to next element
			}
			if ( $this->whitelist_check($ADDRESS) )						// If this address is in the whitelist
			{
				array_push($DEL,$ADDRESS);								// Add the address to the list of hostiles to remove from the sensor!
				continue;												// Dont process this line any more, skip to next element
			}
			array_push($RETURN,$ADDRESS);								// Add the valid, non-whitelisted IPv4 address to the return array.
		}
		if ( count($DEL) ) { $this->ban_del_honeypot($DEL); }			// IF we found any whitelisted IPs in this sensor, remove them!

		return $RETURN;
	}

	// OVERRIDE THIS IN SPECIFIC CHILDREN!
	public function get_hostile()
	{
		die("ERROR: THIS IS A PURE VIRTUAL FUNCTION!\n");
		$HOSTILES = array();
//		$COMMAND_GET_HOSTILE = "sudo novacli list hostile | cut -d ' ' -f 2";
		$COMMAND_GET_HOSTILE = "/opt/networkautomation/bin/honeypot-list-hostile.php";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_HOSTILE) );
		$HOSTILES = $this->filter_addresses($LINES);
		return $HOSTILES;
	}

	// OVERRIDE THIS IN SPECIFIC CHILDREN!
	public function get_benign()
	{
		die("ERROR: THIS IS A PURE VIRTUAL FUNCTION!\n");
		$BENIGNS = array();
//		$COMMAND_GET_HOSTILE = "sudo novacli list benign | cut -d ' ' -f 2";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_HOSTILE) );
		$BENIGNS = $this->filter_addresses($LINES);
		return $BENIGNS;
	}

	// OVERRIDE THIS IN SPECIFIC CHILDREN!
	public function get_suspect()
	{
		die("ERROR: THIS IS A PURE VIRTUAL FUNCTION!\n");
		$SUSPECTS = array();
//		$COMMAND_GET_SUSPECT = "sudo novacli list all | cut -d ' ' -f 2";
		$COMMAND_GET_SUSPECT = "/opt/networkautomation/bin/honeypot-list-suspects.php";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_SUSPECT) );
		$SUSPECTS = $this->filter_addresses($LINES);
		return $SUSPECTS;
	}

	public function ban_add($ADD)
	{
		$OUTPUT = "";

		//////////////////////////////////////////////////////////////////////
		// Create or update hostile objects for IPs being added
		foreach($ADD as $IP)
		{
			print "ADDING HOSTILE {$IP}\n";

			$SEARCH = array(	// Search existing hostile information with this IP
					"category"		=> $this->category,
					"type"			=> "Hostile",
					"stringfield1"	=> $IP,
					"active"		=> "%",		// Include inactive records in search results!
				);
			$RESULTS = Information::search($SEARCH);
			if ( count($RESULTS) )
			{
				$HOSTILE = Information::retrieve( reset($RESULTS) );
				$HOSTILE->set_active(1);
				$HOSTILE->data["banstart"]	= time();	// Restart the ban timer from now
				$HOSTILE->data["lastseen"]	= time();	// Set the last time we saw him to NOW
				$HOSTILE->data["bantime"]	*= 2;		// Double ban time previously assigned
				$HOSTILE->update();
				print "\t264 FOUND DB HOSTILE ID {$HOSTILE->data["id"]}\n";	//dumper($HOSTILE);
			}else{
				$HOSTILE = Information::create("Hostile",$this->category);
				$HOSTILE->data["ip"]		= $IP;
				$HOSTILE->data["banstart"]	= time();	// Restart the ban timer from now
				$HOSTILE->data["firstseen"]	= time();	// Record the first time we saw him
				$HOSTILE->data["lastseen"]	= time();	// Set the last time we saw him to NOW
				$HOSTILE->data["bantime"]	= 43200;	// Set the initial ban timer (12 hours)
				$ID = $HOSTILE->insert();
				$MESSAGE = "Information Added ID:{$ID} CATEGORY:{$this->category} TYPE:Hostile";
				global $DB;
				$DB->log($MESSAGE,2);
				$HOSTILE = Information::retrieve($ID);
				$HOSTILE->update();
				print "\tCREATED NEW HOSTILE ID {$ID}\n";	//dumper($HOSTILE);
			}
			$RESULTS = Information::search($SEARCH);
			if ( count($RESULTS) > 1 )
			{
				print "ERROR: FOUND >1 RECORD WITH IP {$IP}\n";
				foreach ($RESULTS as $RESULT) { dumper( Information::retrieve($RESULT) ); }
			}
			global $DB;
			$DB->log("Blackhole ADD {$IP} ID {$HOSTILE->data["id"]}",2);
		}

		return $OUTPUT;
	}

	public function ban_del($DEL)
	{
		$OUTPUT = "";

		//////////////////////////////////////////////////////////////////////
		// Deactivate hostile objects for IPs being deleted
		foreach($DEL as $IP)														// Loop through the list of IPs to delete
		{
			print __LINE__ . " DEACTIVATING HOSTILE {$IP}\n";
			$SEARCH = array(														// Search database hostile information with this IP
					"category"		=> $this->category,
					"type"			=> "Hostile",
					"active"		=> "%",
					"stringfield1"	=> $IP,
				);
			$RESULTS = Information::search($SEARCH);
			if ( count($RESULTS) )													// If we found hostiles with this IP address
			{
				foreach($RESULTS as $RESULT)										// Loop through them all (there is a bug here somewhere)
				{
					$HOSTILE = Information::retrieve( $RESULT );					// Get the information object out of the store
					print "\t" . __LINE__ . " FOUND DB HOSTILE ID {$HOSTILE->data["id"]}\n";
					$HOSTILE->set_active(0);										// Deactivate this information
//					dumper($HOSTILE);
					global $DB;
					$DB->log("Blackhole DEL {$IP} ID {$HOSTILE->data["id"]}",2);
				}
			}else{
				print "WARNING: COULD NOT LOCATE HOSTILE WITH IP {$IP} USING QUERY: "; dumper($SEARCH);
			}
			if ( count($RESULTS) > 1)												// This is used to detect the bug for duplicate hostile info
			{
				print "\t" . __LINE__ . " ERROR: FOUND >1 RECORD WITH IP {$IP}\n";
				foreach ($RESULTS as $RESULT) { dumper( Information::retrieve($RESULT) ); }
			}
		}

		//////////////////////////////////////////////////////////////////////
		// Remove hostile objects from all the sensor lists
		$SEARCH = array(															// Search for all the sensor objects
						"category"	=>	$this->category,
						"type"		=>	"Sensor_%",
					);
		$RESULTS = Information::search($SEARCH);
		foreach ($RESULTS as $RESULT)												// Loop through all the sensors
		{
			$SENSOR = Information::retrieve($RESULT);								// Get the sensor information object
			print "\tDELETING IPs FROM SENSOR ID {$SENSOR->data["id"]}\n";
			$SENSOR->ban_del_honeypot($DEL);										// Delete all the removed addresses from it
			$SENSOR->update();														// Save our changes to the sensor? what did we change...
			unset($SENSOR);															// Free up some memory
		}

		return $OUTPUT;
	}

	// OVERRIDE THIS IN SPECIFIC CHILDREN!
	public function ban_del_honeypot($DEL)
	{
		die("THIS IS A PURE VIRTUAL FUNCTION!\n");
		$OUTPUT = "";

		$COMMAND_DELETE_HOSTILE = "";
		foreach ($DEL as $IP)														// Loop through the list of IPs to clear
		{
			print "\t\tDELETING {$IP} FROM SENSOR ID {$this->data["id"]}\n";
			while( is_int( array_search($IP, $this->data["hostiles"]) ) )			// Loop through our list of hostiles in this sensor
			{
				$POSITION = array_search($IP, $this->data["hostiles"]);				// Find the position of the ip in our hostile list
				print "\t\t\tLOCATED {$IP} @ POSITION {$POSITION} IN HOSTILES, REMOVING...\n";
				unset($this->data["hostiles"][$POSITION]);							// Remove this IP from our hostiles in this sensor
			}
			$COMMAND_DELETE_HOSTILE .= "/opt/networkautomation/bin/honeypot-clear-suspect.php --suspect={$IP} ; ";	// Build the one multi-command line to send
//			$COMMAND_DELETE_HOSTILE .= "sudo novacli clear eth0 {$IP} ; ";			// Build the one multi-command line to send
		}
		print "NOVA DEL COMMAND: {$COMMAND_DELETE_HOSTILE}\n";
		$OUTPUT = $this->ssh_command($COMMAND_DELETE_HOSTILE);						// Send the command to the sensor
		print "NOVA DEL OUTPUT: {$OUTPUT}\n";

		return $OUTPUT;
	}

	public function scan()
	{
		$OUTPUT = "";

		if ( !isset($this->data["hostiles"]) ) { $this->data["hostiles"] = array(); }

		//////////////////////////////////////////////////////////////////////
		// Get hostile IPs from this sensor and calculate difference from previous list
		$HOSTILES = $this->get_hostile();

		$ADD	= array_diff($HOSTILES,$this->data["hostiles"]);
		$DEL	= array_diff($this->data["hostiles"],$HOSTILES);

		if ( count($ADD) || count($DEL) )
		{
			print "Got\t" . count($HOSTILES) . " Hostile's from live sensor!\n";
			print "Have\t" . count($this->data["hostiles"]) . " Hostile's from database!\n";
			print "Changes required: ADD " . count($ADD) . " DEL " . count($DEL) . "\n";
		}

		// Update the stored information with the new information!
		$this->data["hostiles"] = $HOSTILES;
		$this->update();	// If we dont update before doing all the ADD's the NEXT timer will kick off and double-add!

		// If we found IPs to add, take care of adding them!
		if (count($ADD)) { $this->ban_add($ADD); }

		/////////////////////////////////////////////////////////////////////////
		// Check all the hostiles to see if the ban timer has expired
		$SEARCH = array(	// Search active hostile information
				"category"		=> $this->category,
				"type"			=> "Hostile",
			);
		$RESULTS = Information::search($SEARCH);			// Search for all ACTIVE hostile objects
		if ( count($RESULTS) )								// If we find some, check them
		{
			foreach($RESULTS as $RESULT)					// Check every hostile object
			{
				$HOSTILE = Information::retrieve($RESULT);	// Get our hostile object
				if ($HOSTILE->bantime_remaining() <= 0)		// check if its ban time has expired
				{
					array_push($DEL,$HOSTILE->data["ip"]);	// If bantime is expired, add to the delete list!
				}
			}
		}

		// If we found IPs to delete, take care of removing them!
		if (count($DEL)) { $this->ban_del($DEL); }

		// If we made any changes add/delete, run the push tool!
		if ( count($ADD) || count($DEL) )
		{
			print "BLACKHOLE CHANGES DETECTED, RUNNING PUSH: ADD " . count($ADD) . " DEL " . count($DEL) . "\n";
			$COMMAND = "timeout 1m php ".BASEDIR."/bin/push-blackhole.php > /dev/null 2>/dev/null &";
//			$COMMAND = "timeout 1m php ".BASEDIR."/bin/push-blackhole.php ";
			exec($COMMAND);
		}

		/////////////////////////////////////////////////////////////////////////
		// Check this sensor for benign hosts cluttering up the results and clean them up!
		$BENIGNS = $this->get_benign();
		$BENIGNCOUNT = count($BENIGNS);
		if ( $BENIGNCOUNT >= 40 )
		{
			print "Found {$BENIGNCOUNT} Benign hosts on sensor id {$this->data["id"]}, running cleanup...\n";
			$CHUNKS = array_chunk($BENIGNS,50);
			$CHUNKCOUNT = count($CHUNKS);
			$i = 0;
			foreach($CHUNKS as $CHUNK)
			{
				$i++; print "\tDeleting benign chunk {$i} of {$CHUNKCOUNT}...\n";
				$this->ban_del_honeypot($CHUNK);	// Remove each chunk 50 IPs at a time to clean up the sensor
			}
		}

		return $OUTPUT;
	}

	public function status()
	{
		$OUTPUT = "";

		if ( !isset($this->data["hostiles"]) ) { $this->data["hostiles"] = array(); }

		//////////////////////////////////////////////////////////////////////
		// Get hostile IPs from this sensor and calculate difference from previous list
		$HOSTILES = $this->get_hostile();

		$ADD	= array_diff($HOSTILES,$this->data["hostiles"]);
		$DEL	= array_diff($this->data["hostiles"],$HOSTILES);

		/////////////////////////////////////////////////////////////////////////
		// Get status for each hostile object
		$SEARCH = array(	// Search active hostile information
				"category"		=> $this->category,
				"type"			=> "Hostile",
			);
		$RESULTS = Information::search($SEARCH);
		$HOSTILE_OUTPUT = "";
		if ( count($RESULTS) )
		{
			foreach($RESULTS as $RESULT)
			{
				$HOSTILE = Information::retrieve($RESULT);	// Get our hostile object
				if ($HOSTILE->bantime_remaining() <= 0)		// check if its ban time has expired
				{
					array_push($DEL,$HOSTILE->data["ip"]);	// If bantime is expired, add to the delete list!
				}
				$HOSTILE_OUTPUT .= $HOSTILE->status();
			}
		}

		$OUTPUT .= "Database Hostile IPs:\n";
		$OUTPUT .= dBug_to_string($this->data["hostiles"]);
		$OUTPUT .= "<hr size=1>";
		$OUTPUT .= "Sensor Hostile IPs:\n";
		$OUTPUT .= dBug_to_string($HOSTILES);
		$OUTPUT .= "<hr size=1>";
		$OUTPUT .= "ADD Hostile IPs:\n";
		$OUTPUT .= dBug_to_string($ADD);
		$OUTPUT .= "<hr size=1>";
		$OUTPUT .= "DEL Hostile IPs:\n";
		$OUTPUT .= dBug_to_string($DEL);
		$OUTPUT .= "<hr size=1>";
		$OUTPUT .= $HOSTILE_OUTPUT;

		return $OUTPUT;
	}

}

?>
