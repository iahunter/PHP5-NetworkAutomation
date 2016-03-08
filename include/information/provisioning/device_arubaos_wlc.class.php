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

require_once "information/provisioning/device_arubaos.class.php";

class Provisioning_Device_ArubaOS_WLC	extends Provisioning_Device_ArubaOS
{
	public $type = "Provisioning_Device_ArubaOS_WLC";

	public function customdata()    // This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		$CHANGED += $this->customfield("mgmtip4"	,"stringfield1");
		$CHANGED += $this->customfield("region"		,"stringfield2");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"    ,$this->data['name'		]);
		$DB->bind("STRINGFIELD1"    ,$this->data['mgmtip4'	]);
		$DB->bind("STRINGFIELD2"    ,$this->data['region'	]);
		if(isset($this->data['newtype']) && $this->data['type'] != $this->data['newtype'])
		{
			print "Device Changed Type To {$this->data['newtype']}<br>
					<b>Please edit this new object and provide additional information!</b><br><br>\n";
			$this->data['type'] = $this->data['newtype'];
			unset($this->data['newtype']);
			$DB->bind("TYPE"            ,$this->data['type'             ]);
		}
		unset($this->data['newtype']); // TODO: remove this line after a while... DB cleanup stuff.
	}

	// This is a dummy class to sit inbetween the OS tier and device specific object tier.

	// I will override specific non-wlc arubaos config here, but keep role specific config closer to the devices.

	public function config()
	{
		$OUTPUT = "<pre>\n";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= "! Found Device ID {$this->data["id"]} of type {$this->data["type"]}";

		$OUTPUT .= "config t\n\n";

		$OUTPUT .= "hostname {$this->data["name"]}\n";

		$OUTPUT .= $this->config_management();
		$OUTPUT .= $this->config_logging();
		$OUTPUT .= $this->config_aaa();
		$OUTPUT .= $this->config_snmp();
		$OUTPUT .= $this->config_spanningtree();
		$OUTPUT .= $this->config_motd();
		$OUTPUT .= $this->config_dns();
		$OUTPUT .= $this->config_tunnels();

		$OUTPUT .= "end\n";
		$OUTPUT .= "</pre>\n";
		return $OUTPUT;
	}

	public function filter_config($CONFIG)
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

			// Ignore a bunch of unimportant often-changing lines that clutter up the config repository
			if (
				( trim($LINE) == ""                                     )   ||  //  Ignore blank and whitespace-only lines
				( trim($LINE) == "exit"                                 )   ||  //  Ignore exit lines (mostly provisioning lines)
				( preg_match('/.*no shut.*/'                ,$LINE,$REG))   ||  //  no shut/no shutdown lines from provisioning tool
				( preg_match('/.*no enable.*/'              ,$LINE,$REG))   ||  //  from provisioning tool
				( preg_match('/.*spanning-tree vlan 1-4094.*/',$LINE,$REG)) ||  //  from provisioning tool
				( preg_match('/.*enable secret.*/'          ,$LINE,$REG))   ||  //  from provisioning tool
				( preg_match('/.*ip domain.lookup.*/'       ,$LINE,$REG))   ||  //  from provisioning tool
				( preg_match('/.*ip domain.name.*/'         ,$LINE,$REG))   ||  //  from provisioning tool
				( preg_match('/.*crypto key generate rsa.*/',$LINE,$REG))   ||  //  from provisioning tool
				( preg_match('/.*log-adjacency-changes.*/'  ,$LINE,$REG))   ||  //  from provisioning tool
				( trim($LINE) == "end"                                  )   ||  //  from provisioning tool
				( trim($LINE) == "wr"                                   )   ||  //  from provisioning tool
				( trim($LINE) == "reload"                               )   ||  //  from provisioning tool
				( trim($LINE) == "switchport"                           )   ||  //  from provisioning tool
				( trim($LINE) == "snmp-server ifindex persist"          )   ||  //  from provisioning tool
				( trim($LINE) == "aaa session-id common"                )   ||  //  from provisioning tool
				( trim($LINE) == "ip routing"                           )   ||  //  from provisioning tool
				( trim($LINE) == "cdp enable"                           )   ||  //  from provisioning tool
				( trim($LINE) == "no ip directed-broadcast"             )   ||  //  from provisioning tool
				( trim($LINE) == "no service finger"                    )   ||  //  from provisioning tool
				( trim($LINE) == "no service udp-small-servers"         )   ||  //  from provisioning tool
				( trim($LINE) == "no service tcp-small-servers"         )   ||  //  from provisioning tool
				( trim($LINE) == "no service config"                    )   ||  //  from provisioning tool
				( trim($LINE) == "no clock timezone"                    )   ||  //  from provisionnig tool
				( trim($LINE) == "end"                                  )   ||  //  skip end, we dont need this yet
				( trim($LINE) == "<pre>" || trim($LINE) == "</pre>"     )   ||  //  skip <PRE> and </PRE> output from html scrapes
				( substr(trim($LINE),0,1) == "!"                        )   ||  //  skip conf t lines
				( substr(trim($LINE),0,4) == "exit"                     )   ||  //  skip conf lines beginning with the word exit
				( preg_match('/.*config t.*/'               ,$LINE,$REG))   ||  //  skip show run
				( preg_match('/.*show run.*/'               ,$LINE,$REG))   ||  //  and show start
                ( preg_match('/.*show startup.*/'           ,$LINE,$REG))   ||  //  show run config topper
                ( preg_match('/^version .*/'                ,$LINE,$REG))   ||  //  version 12.4 configuration format
                ( preg_match('/^boot-\S+-marker.*/'         ,$LINE,$REG))   ||  //  boot start and end markers
                ( preg_match('/^Building configur.*/'       ,$LINE,$REG))   ||  //  ntp clock period in seconds is constantly changing
                ( preg_match('/^ntp clock-period.*/'        ,$LINE,$REG))   ||  //  nvram config last messed up
                ( preg_match('/^Current configuration.*/'   ,$LINE,$REG))   ||  //  current config size
                ( preg_match('/.*NVRAM config last up.*/'   ,$LINE,$REG))   ||  //  nvram config last saved
                ( preg_match('/.*uncompressed size*/'       ,$LINE,$REG))   ||  //  uncompressed config size
                ( preg_match('/^!Time.*/'                   ,$LINE,$REG))       //  time comments
               )
            { continue; }

			// Find the hostname to identify our prompt
			if (preg_match("/^hostname (\S+)/",$LINE,$REG)) { $HOSTNAME = $REG[1]; }
			// Filter out the prompt at the end if it exists
			if ($HOSTNAME != "" && preg_match("/^{$HOSTNAME}.+/",$LINE,$REG)) { continue; }

			// If we have UTC and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* UTC$/'         ,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'     ,$LINE,$REG))
			)
				{ continue; }

			// If we have CST and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* CST$/'         ,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'     ,$LINE,$REG))
			)
			{ continue; }

			// If we have CDT and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/. *CDT$/'         ,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'     ,$LINE,$REG))
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
				$LINE = $REG[1];    // Strip out the KEYS from a server-private line!
			}

			array_push($LINES_OUT, $LINE);
		}

		// REMOVE blank lines from the leading part of the array and REINDEX the array
		while ($LINES_OUT[0] == ""  && count($LINES_OUT) > 2 ) { array_shift    ($LINES_OUT); }

		// REMOVE blank lines from the end of the array and REINDEX the array
		while (end($LINES_OUT) == ""    && count($LINES_OUT) > 2 ) { array_pop  ($LINES_OUT); }

		// Ensure there is one blank line at EOF. Subversion bitches about this for some reason
		array_push($LINES_OUT, "");

		$CONFIG = implode("\n",$LINES_OUT);

		return $CONFIG;
	}

}
