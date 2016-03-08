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

require_once "information/management/device.class.php";

class Management_Device_Network	extends Management_Device
{
	public $type = "Management_Device_Network";

	public function rescan()
	{
		$this->data['newtype'] = "Management_Device_Network_Cisco";
		$this->update();	// Always run an update after a scan!
		$OUTPUT = "Changed type to {$this->data['newtype']}\n";
		return $OUTPUT;
	}

	public function model()
	{
		$LINES = array();
		if ( isset($this->data["inventory"	]) && $this->data["inventory"	] )
		{
			$LINES = array_merge($LINES, preg_split( "/\r\n|\r|\n/", $this->data["inventory"	] ) );
		}
		if ( isset($this->data["version"	]) && $this->data["version"		] )
		{
			$LINES = array_merge($LINES, preg_split( "/\r\n|\r|\n/", $this->data["version"	] ) );
		}
		$REGEX = array( "/.*PID:\s(\S+)\s.*/",		// Traditional Cisco show inventory output
						"/.*SC Model.*: (\S+)/",	// New Aruba show inventory output
						"/.*isco\s+(WS-\S+)\s.*/",	// Cisco show ver 1
						"/.*isco\s+(OS-\S+)\s.*/",	// Cisco show ver 2
						"/.*ardware:\s+(\S+),.*/",	// Cisco show ver 3
						"/.*ardware:\s+(\S+).*/",	// Cisco show ver 4
						"/^cisco\s+(\S+)\s+.*/",	// Cisco show ver 5...
						"/.*\(MODEL: (\S+)\).*/",	// New Aruba show ver output
						);
		if ( count($LINES) )
		{
			foreach ($LINES as $LINE)
			{
				foreach ($REGEX as $REG)
				{
					if ( preg_match($REG,$LINE,$MATCHES) )
					{
						return $MATCHES[1];
					}
				}
			}
		}
		return "unknown";
	}

	public function vendor()
	{
		$LINES = array();
		if ( isset($this->data["inventory"	]) && $this->data["inventory"	] )
		{
			$LINES = array_merge($LINES, preg_split( "/\r\n|\r|\n/", $this->data["inventory"	] ) );
		}
		if ( isset($this->data["version"	]) && $this->data["version"		] )
		{
			$LINES = array_merge($LINES, preg_split( "/\r\n|\r|\n/", $this->data["version"	] ) );
		}
		$REGEX = array( "cisco" => "/.*cisco.*/i",	// Look for cisco information in output
						"aruba" => "/.*aruba.*/i");	// And look for aruba in the output
		$HITS = array();
		$HITS["unknown"] = 1;	// So if we dont have >1 hit in all that output, return unknown.
		if ( count($LINES) )
		{
			foreach ($LINES as $LINE)
			{
				foreach ($REGEX as $KEY => $REG)
				{
					if ( preg_match($REG,$LINE,$MATCHES) )
					{
						if ( !isset($HITS[$KEY]) ) { $HITS[$KEY] = 0; }
						$HITS[$KEY]++;
					}
				}
			}
		}
		arsort($HITS);
		return reset( array_keys( $HITS ) );
	}

}
