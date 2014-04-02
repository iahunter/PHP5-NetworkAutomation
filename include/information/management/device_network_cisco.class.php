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

require_once "information/management/device_network.class.php";

class Management_Device_Network_Cisco	extends Management_Device_Network
{
	public $type = "Management_Device_Network_Cisco";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		$CHANGED += $this->customfield("ip"			,"stringfield1");
		$CHANGED += $this->customfield("protocol"	,"stringfield2");
		$CHANGED += $this->customfield("groups"		,"stringfield3");
		$CHANGED += $this->customfield("lastscan"	,"stringfield4");
		$CHANGED += $this->customfield("run"		,"stringfield5");	unset($this->data['stringfield5']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("version"	,"stringfield6");	unset($this->data['stringfield6']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("inventory"	,"stringfield7");	unset($this->data['stringfield7']);	// This gets rid of duplication for very large indexed fields!
		$CHANGED += $this->customfield("model"		,"stringfield8");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['ip'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['protocol'	]);
		$DB->bind("STRINGFIELD3"	,$this->data['groups'	]);
		$DB->bind("STRINGFIELD4"	,$this->data['lastscan'	]);
		$DB->bind("STRINGFIELD5"	,$this->data['run'		]);
		$DB->bind("STRINGFIELD6"	,$this->data['version'	]);
		$DB->bind("STRINGFIELD7"	,$this->data['inventory']);
		$DB->bind("STRINGFIELD8"	,$this->data['model'	]);
	}

	private function jprint($OUTPUT)
	{
//		print "$OUTPUT";
		if (php_sapi_name() != "cli") { print "$OUTPUT"; john_flush(); }
	}

	public function rescan($TRY = 1)
	{
		$OUTPUT = "";
		$this->data['protocol'] = "none";	// Start every SCAN fresh with protocol discovery

		// Start with a ping test, see if we can ping the IP
		$PING = new Ping($this->data['ip']);
		$LATENCY = $PING->ping("exec");
		if (!$LATENCY)
		{
			$this->jprint(" Could not ping, connection may fail.");
			$OUTPUT .= " Could not ping, connection may fail.";
	//		print "\n"; $OUTPUT .= "\n"; return $OUTPUT;
		}else{
			$this->jprint(" Latency: {$LATENCY}ms");
			$OUTPUT .= " Latency: {$LATENCY}ms";
		}
		unset($PING);

		// Then try to get the CLI via any means necessary
		$COMMAND = new Command($this->data);
		$this->jprint(" Connection:");
		$OUTPUT .= " Connection:";
		$CLI = $COMMAND->getcli();
		if ($CLI)
		{
			$this->jprint(" {$CLI->service}");
			$OUTPUT .= " {$CLI->service}";
		}else{
			$this->jprint(" Could not connect!\n");
			$OUTPUT .= " Could not connect!\n";
			$this->update();
			return $OUTPUT;
		}

		// We are connected, lets get the prompt!
		$this->jprint(" Prompt:");
		$OUTPUT .= " Prompt:";
		$PROMPT = strtolower($CLI->prompt);
		if ($PROMPT != "")
		{
			$this->jprint(" {$PROMPT} ");
			$OUTPUT .= " {$PROMPT} ";
			$this->data['name'] = $PROMPT;
			$this->data['protocol'] = $CLI->service;
		}else{
			$this->jprint(" Could not get prompt! Aborting!\n");
			$OUTPUT .= " Could not get prompt! Aborting!\n";
			$this->update();
			return $OUTPUT;
		}

		// Make sure we know what we are connected to!
		$this->jprint("Firewall detection: ");
		$OUTPUT .= "Firewall detection: ";
		$FUNCTION = "";
		$CLI->exec("terminal length 0");
		$SHOW_INVENTORY = $CLI->exec("show inventory | I PID");
		$MODEL = inventory_to_model($SHOW_INVENTORY);
		if ($MODEL == "Unknown")
		{
			$SHOW_VERSION = $CLI->exec("show version | I C");
			$MODEL = version_to_model($SHOW_VERSION);
		}
		if ($MODEL == "Unknown")
		{
			$this->jprint(" Could not detect device type/model! Aborting!\n");
			$OUTPUT .= " Could not detect device type/model! Aborting!\n";
			$this->update();
			return $OUTPUT;
		}

		if (preg_match('/(ASA|FWM|PIX)/',$MODEL,$REG))
		{
			$this->jprint(" YES! Model: {$MODEL}");
			$OUTPUT .= " YES! Model: {$MODEL}";
			$FUNCTION = "Firewall";
		}else{
			$this->jprint(" NO! Model: {$MODEL}");
			$OUTPUT .= " NO! Model: {$MODEL}";
		}

		// Special handling in case we are in a firewall
		if($FUNCTION == "Firewall")
		{
			$this->jprint(" Firewall, sending enable");
			$OUTPUT .= " Firewall, sending enable";
			$COMMAND = "enable\n" . TACACS_ENABLE;	$ENABLE_OUTPUT .= $CLI->exec($COMMAND);			//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			sleep(4);
			$COMMAND = "no pager";					//$OUTPUT .= $CLI->exec($COMMAND);				//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			$COMMAND = "terminal pager 0";			$TERMINAL_PAGER_OUTPUT .= $CLI->exec($COMMAND);	//	$this->jprint("\nCOMMAND: $COMMAND\nOUTPUT: $OUTPUT\n";
			$this->jprint(" Pager disabled");
			$OUTPUT .= " Pager disabled";
			if (cisco_check_input_error($TERMINAL_PAGER_OUTPUT) && cisco_check_input_error($ENABLE_OUTPUT))
			{
				$this->jprint(" Enabled Successfully!");
				$OUTPUT .= " Enabled Successfully!";
			}else{
				$this->jprint(" Error Enabling! ");
				$OUTPUT .= " Error Enabling! ";
				$this->update();
				return $OUTPUT;
			}
		}else{
			$CLI->exec("terminal length 0");
		}

		// Capture the show command output!
		$this->jprint(" version:");
		$OUTPUT .= " version:";
		$SHOW_VERSION	= $CLI->exec("show version");	$LEN_VER = strlen($SHOW_VERSION);
		if (cisco_check_input_error($SHOW_VERSION) && $LEN_VER > 200)	// No errors and >200 bytes
		{
			$this->jprint(" OK({$LEN_VER})");
			$OUTPUT .= " OK({$LEN_VER})";
		}else{
			$SHOW_VERSION = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" inventory:");
		$OUTPUT .= " inventory:";
		$SHOW_INVENTORY	= $CLI->exec("show inventory");	$LEN_INV = strlen($SHOW_INVENTORY);
		if (cisco_check_input_error($SHOW_INVENTORY) && $LEN_INV > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_INV})");
			$OUTPUT .= " OK({$LEN_INV})";
		}else{
			$SHOW_INVENTORY = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" run:");
		$OUTPUT .= " run:";
		$SHOW_RUN	= $CLI->exec("show run");		$LEN_RUN = strlen($SHOW_RUN);
		if (cisco_check_input_error($SHOW_RUN) && $LEN_RUN > 1000)	// No errors and >1000 bytes
		{
			$this->jprint(" OK({$LEN_RUN})");
			$OUTPUT .= " OK({$LEN_RUN})";
		}else{
/*			global $DB;									$LOG = "Scan error 1 - ";
			if ( !cisco_check_input_error($SHOW_RUN) )	{ $LOG .= "input - "; }
			if ( $LEN_RUN < 1000 )						{ $LOG .= "output - "; }
			$LOG .= "'{$SHOW_RUN}'";					$DB->log($LOG,1);
			// We failed so try again!
			$this->jprint(" run retry:");
			$OUTPUT .= " run retry:";
			$SHOW_RUN	= $this->get_running_config();	$LEN_RUN = strlen($SHOW_RUN);
			if (cisco_check_input_error($SHOW_RUN) && $LEN_RUN > 1000)
			{
				$this->jprint(" OK({$LEN_RUN})"); $OUTPUT .= " OK({$LEN_RUN})";
			}else{
/**/				global $DB;									$LOG = "Scan error 2 - ";
				if ( !cisco_check_input_error($SHOW_RUN) )	{ $LOG .= "input - "; }
				if ( $LEN_RUN < 1000 )						{ $LOG .= "output - "; }
				$LOG .= "'{$SHOW_RUN}'";					//$DB->log($LOG,2);
				// We failed again so give up!
				$SHOW_RUN = "";
				$this->jprint(" NO!");
				$OUTPUT .= " NO!";
				$this->data['protocol'] = "none";
//			}
		}

		$this->jprint(" diag:");
		$OUTPUT .= " diag:";
		$SHOW_DIAG	= $CLI->exec("show diag");		$LEN_DIAG = strlen($SHOW_DIAG);
		if (cisco_check_input_error($SHOW_DIAG) && $LEN_DIAG > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_DIAG})");
			$OUTPUT .= " OK({$LEN_DIAG})";
		}else{
			$SHOW_DIAG = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" module:");
		$OUTPUT .= " module:";
		$SHOW_MOD	= $CLI->exec("show module");	$LEN_MOD = strlen($SHOW_MOD);
		if (cisco_check_input_error($SHOW_MOD) && $LEN_MOD > 100)	// No errors and >100 bytes
		{
			$this->jprint(" OK({$LEN_MOD})");
			$OUTPUT .= " OK({$LEN_MOD})";
		}else{
			$SHOW_MOD = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->jprint(" interface:");
		$OUTPUT .= " interface:";
		$SHOW_INT	= $CLI->exec("show interface");	$LEN_INT = strlen($SHOW_INT);
		if (cisco_check_input_error($SHOW_INT) && $LEN_INT > 200)	// No errors and >200 bytes
		{
			$this->jprint(" OK({$LEN_INT})");
			$OUTPUT .= " OK({$LEN_INT})";
		}else{
			$SHOW_INT = "";
			$this->jprint(" NO!");
			$OUTPUT .= " NO!";
		}

		$this->data['lastscan'] = date("Y-m-d H:i:s"); // Need to test the shit out of this one lol
		$this->data['version']	= $SHOW_VERSION;
		$this->data['inventory']= $SHOW_INVENTORY;
		$this->data['run']		= $SHOW_RUN;
		$this->data['diag']		= $SHOW_DIAG;
		$this->data['module']	= $SHOW_MOD;
		$this->data['interface']= $SHOW_INT;
		$this->data['model']	= $MODEL;
		$this->data['pattern']	= sprintf($CLI->pattern['match'],$CLI->prompt);

		$this->update();
		$this->jprint(" Scan complete, database updated!\n");
		$OUTPUT .= " Scan complete, database updated!\n";
		return $OUTPUT;
	}

	public function get_running_config($TRY = 1)
	{
		$COMMAND = new Command($this->data);	// Start every attempt with a fresh connection!
		$CLI = $COMMAND->getcli();					sleep(1);
		$CLI->exec("terminal length 0");
		$SHOW_RUN   = $CLI->exec("show run");       $LEN_RUN = strlen($SHOW_RUN);
		if ($TRY > 3) { return $SHOW_RUN; }		// Give up after 3 tries
		if (cisco_check_input_error($SHOW_RUN) && $LEN_RUN > 1000)  // No errors and >1000 bytes
		{
			return $SHOW_RUN;					// We got a good running config
		}else{
			global $DB;
			$DB->log("get_running_config error, try $TRY",1);
			return $this->get_running_config(++$TRY);	// Try harder next time
        }
	}

}

?>
