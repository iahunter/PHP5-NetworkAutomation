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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once "information/blackhole/sensor.class.php";

class Blackhole_Sensor_Hon3yPot	extends Blackhole_Sensor
{
	public $category = "Blackhole";
	public $type = "Blackhole_Sensor_Hon3yPot";
	public $customfunction = "";

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Sensor Name"			);
		$OUTPUT .= $this->html_form_field_text("name"		,"Description"			);
		$OUTPUT .= $this->html_form_field_text("ip"			,"IPv4 Address"			);
		$OUTPUT .= $this->html_form_field_text("port"		,"MYSQL Port"			);
		$OUTPUT .= $this->html_form_field_text("user"		,"Username"				);
		$OUTPUT .= $this->html_form_field_text("pass"		,"Password"				);
		$OUTPUT .= $this->html_form_field_text("database"	,"Database Name"		);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function get_hostile()
	{
		$HOSTILES = array();

		// Connect To the HONEYPOT's MYSQL Database
		try {
			$DATASOURCE = "mysql:host={$this->data["ip"]};port={$this->data["port"]};dbname={$this->data["database"]}";
			$DB = new Database($DATASOURCE,$this->data["user"],$this->data["pass"]);
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}\n";
			trigger_error($MESSAGE);
			// If we couldnt connect to the database, just return the hostiles we already know about...
			return $this->data["hostiles"];
		}

		$QUERY = <<<END
	SELECT stringfield1 AS ip , count(stringfield1) AS hits FROM information
	WHERE category LIKE "Blackhole"
	AND type LIKE "Suspect"
	AND active = 1
	GROUP BY stringfield1
	HAVING hits >= 3
	ORDER BY stringfield1
END;
		// Run our query
		$DB->query($QUERY);
		try {
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			// If we couldnt execute our query, just return the hostiles we already know about...
			return $this->data["hostiles"];
		}

		// Process our query results of hostile suspects
		$HOSTILES = array();
		foreach ($RESULTS as $SUSPECT)
		{
			// Only use suspects that are valid IPv4 addresses!
			if( $SUSPECT["ip"] && filter_var($SUSPECT["ip"], FILTER_VALIDATE_IP) )
			{
				array_push($HOSTILES,$SUSPECT["ip"]);
			}
		}
		// Make sure none of the new hostiles are in the whitelist!
		$HOSTILES = $this->filter_addresses($HOSTILES);

		return $HOSTILES;
	}

	public function get_hostile_details( $COUNT = 500 )
	{
		$HOSTILES = array();
		if ( $COUNT < 1 || $COUNT > 1000 ) { return $HOSTILES; }	// Prevent any fuckery.
		// Connect To the HONEYPOT's MYSQL Database
		try {
			$DATASOURCE = "mysql:host={$this->data["ip"]};port={$this->data["port"]};dbname={$this->data["database"]}";
			$DB = new Database($DATASOURCE,$this->data["user"],$this->data["pass"]);
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}\n";
			trigger_error($MESSAGE);
			// If we couldnt connect to the database, just return the hostiles we already know about...
			return $HOSTILES;	// Return an empty array...
		}
		/*
			category: Blackhole
			type: Suspect
			modifiedby: knehnyalp001
			modifiedwhen: 2015-03-26 11:46:24
			stringfield1: 192.99.245.249
			stringfield2: 123.456.72.15
			stringfield3: 23
		*/
		$QUERY = <<<END
	SELECT
			modifiedwhen AS date,
			stringfield1 AS source,
			stringfield2 AS target,
			stringfield3 AS protocol
		FROM information
	WHERE category LIKE "Blackhole"
	AND type LIKE "Suspect"
	AND active = 1
	ORDER BY id DESC
	LIMIT {$COUNT}
END;
		// Run our query
		$DB->query($QUERY);
		try {
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			// If we couldnt execute our query, just return the hostiles we already know about...
			return $HOSTILES;
		}
		$HOSTILES = $RESULTS;	// Return this raw without any processing for now...
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

		$QUERY_DEL_ARRAY = implode("','",$DEL);

		// Connect To the HONEYPOT's MYSQL Database
		try {
			$DATASOURCE = "mysql:host={$this->data["ip"]};port={$this->data["port"]};dbname={$this->data["database"]}";
			$DB = new Database($DATASOURCE,$this->data["user"],$this->data["pass"]);
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}\n";
			trigger_error($MESSAGE);
			// If we couldnt connect to the database, just return the hostiles we already know about...
			return;
		}

		$QUERY = <<<END
			UPDATE information
			SET active = 0
			WHERE category LIKE "Blackhole"
			AND type LIKE "Suspect"
			AND active = 1
			AND stringfield1 IN('{$QUERY_DEL_ARRAY}')
END;
		print "QUERY: {$QUERY}\n";
		// Run our query
		$CHANGED = 0;
		$DB->query($QUERY);
		try {
			$DB->execute();
			$CHANGED = intval($DB->DB_STATEMENT->rowCount());
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
		}
		print "\t\tSQL UPDATED {$CHANGED} RECORDS IN HONEYPOT DATABASE!\n";

		return;
	}

}

?>
