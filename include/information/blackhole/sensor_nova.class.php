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

	public function html_form()
	{
		$OUTPUT = "";
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

	public function get_hostile()
	{
		$HOSTILES = array();
		$COMMAND_GET_HOSTILE = "sudo novacli list hostile | cut -d ' ' -f 2";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_HOSTILE) );
		$HOSTILES = $this->filter_addresses($LINES);
		return $HOSTILES;
	}

	public function get_benign()
	{
		$BENIGNS = array();
		$COMMAND_GET_HOSTILE = "sudo novacli list benign | cut -d ' ' -f 2";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_HOSTILE) );
		$BENIGNS = $this->filter_addresses($LINES);
		return $BENIGNS;
	}

	public function get_suspect()
	{
		$SUSPECTS = array();
		$COMMAND_GET_SUSPECT = "sudo novacli list all | cut -d ' ' -f 2";
		$LINES = explode("\n", $this->ssh_command($COMMAND_GET_SUSPECT) );
		$SUSPECTS = $this->filter_addresses($LINES);
		return $SUSPECTS;
	}

	public function ban_del_honeypot($DEL)
	{
		$OUTPUT = "";

		$COMMAND_DELETE_HOSTILE = "";
		foreach ($DEL as $IP)														// Loop through the list of IPs to clear
		{
			print "\t\tDELETING {$IP} FROM SENSOR ID {$this->data["id"]}\n";
			while( array_search($IP, $this->data["hostiles"]) )						// Loop through our list of hostiles in this sensor
			{
				$POSITION = array_search($IP, $this->data["hostiles"]);				// Find the position of the ip in our hostile list
				print "\t\t\tLOCATED {$IP} @ POSITION {$POSITION} IN HOSTILES, REMOVING...\n";
				unset($this->data["hostiles"][$POSITION]);							// Remove this IP from our hostiles in this sensor
			}
			$COMMAND_DELETE_HOSTILE .= "sudo novacli clear eth0 {$IP} ; ";			// Build the one multi-command line to send
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

}

?>
