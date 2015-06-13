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

require_once "information/blackhole/sensor.class.php";

class Blackhole_Sensor_Manual	extends Blackhole_Sensor
{
	public $category = "Blackhole";
	public $type = "Blackhole_Sensor_Manual";
	public $customfunction = "";

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"				,"Sensor Name"					);
		$OUTPUT .= $this->html_form_field_text("description"		,"Description"					);
		$OUTPUT .= $this->html_form_field_textarea("hostileiplist"	,"Hostile IP's, one per line"	);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function get_hostile()
	{
		$SUSPECTIPS = explode("\n", $this->data["hostileiplist"] );

		$HOSTILES = array();
		foreach ($SUSPECTIPS as $SUSPECTIP)
		{
			$SUSPECTIP = trim($SUSPECTIP);
			if ( !$SUSPECTIP ) { continue; }	// Skip empty lines in the list
			// Only use suspects that are valid IPv4 addresses!
			if( $SUSPECTIP && filter_var($SUSPECTIP, FILTER_VALIDATE_IP) )
			{
				array_push($HOSTILES,$SUSPECTIP);
			}else{
				print "MANUAL IP FAILED VALIDATION: {$SUSPECTIP}\n";
			}
		}
		// Make sure none of the new hostiles are in the whitelist!
		$HOSTILES = $this->filter_addresses($HOSTILES);

		return $HOSTILES;
	}

	public function get_benign()
	{
		$BENIGNS = array();
		// Need to write this eventually...
		$BENIGNS = $this->filter_addresses($LINES);
		return $BENIGNS;
	}

	public function get_suspect()
	{
		$SUSPECTS = array();
		// Need to write this eventually...
		$SUSPECTS = $this->filter_addresses($LINES);
		return $SUSPECTS;
	}

	public function ban_del_honeypot($DEL)
	{
		$OUTPUT = "";

		// This only removes them from the hostiles array, NOT the hostile IP list manually specified
		// The result of this is that the IP will be immediately re-added on the next scan, with bantime doubled...

		foreach ($DEL as $IP)														// Loop through the list of IPs to clear
		{
			print "\t\tDELETING {$IP} FROM SENSOR ID {$this->data["id"]}\n";
			while( is_int( array_search($IP, $this->data["hostiles"]) ) )			// Loop through our list of hostiles in this sensor
			{
				$POSITION = array_search($IP, $this->data["hostiles"]);				// Find the position of the ip in our hostile list
				print "\t\t\tLOCATED {$IP} @ POSITION {$POSITION} IN HOSTILES, REMOVING...\n";
				unset($this->data["hostiles"][$POSITION]);							// Remove this IP from our hostiles in this sensor
			}
		}

		return $OUTPUT;
	}

}

?>
