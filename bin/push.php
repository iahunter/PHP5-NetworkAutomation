#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

//////////////////////////////////////////////////////////

	$PUSH_LINES = <<<END
ip access-list standard ACL_SNMP_RW
  permit 172.30.0.246
  permit 10.123.0.0 0.0.255.255
 exit
ip access-list standard ACL_SNMP_RO
  permit 74.126.50.12
  permit 74.126.50.129
  permit 10.0.112.0 0.0.15.255
  permit 10.0.210.0 0.0.1.255
  permit 10.202.0.0 0.0.255.255
  permit 10.250.224.0 0.0.15.255
  permit 172.17.251.0 0.0.0.255
  permit 172.30.0.0 0.0.255.255
 exit

snmp-server community NetworkRO RO ACL_SNMP_RO
snmp-server community NetworkRW RW ACL_SNMP_RW
snmp-server enable traps config

ip access-list standard ACL_REMOTE_MGMT
  permit 74.126.50.0 0.0.0.255
  permit 192.174.72.0 0.0.7.255
  permit 10.0.0.0 0.255.255.255
  permit 172.16.0.0 0.15.255.255
  permit 192.168.0.0 0.0.255.255
 exit

line vty 0 15
  no access-class in
  no access-class ACL_REMOTE_ACCESS in
  no access-class 23 in
  access-class ACL_REMOTE_MGMT in vrf-also
  logging synchronous
  privilege level 15
  exec-timeout 60 0
  transport input ssh
  transport preferred none
 exit
END;

//////////////////////////////////////////////////////////

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_USER_NOTICE);

if (php_sapi_name() != "cli")
{
	die("This is a CLI tool only!");
}

$SEARCH = array();
if (isset($argc))
{
	$CMD = new CommandLine;
	$SEARCH = $CMD->parseArgs($argv);
}
$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);

if ($COUNT)
{
	print "Found {$COUNT} devices matching query.\n";

	foreach($RESULTS as $DEVICEID)
	{
		$DEVICE = Information::retrieve($DEVICEID);
		print "PUSH DEVICE ID {$DEVICE->data['id']} PROMPT {$DEVICE->data['prompt']} IP {$DEVICE->data['ip']}\t";

		// Start with a ping test, see if we can ping the IP
		$PING = new Ping($DEVICE->data['ip']);
		$LATENCY = $PING->ping("exec");
		if (!$LATENCY)
		{
			print " Could not ping, connection may fail.";
		}else{
			print " Latency: {$LATENCY}ms";
		}
		unset($PING);

		// Then try to get the CLI
		$COMMAND = new Command($DEVICE->data);
		print " Connection:";
		$CLI = $COMMAND->getcli();
		if ($CLI)
		{
			print " {$CLI->service}";
		}else{
			print " Could not connect! Aborting!\n"; continue;
		}

		print " Prompt:";
		$PROMPT = strtolower($CLI->prompt);
		if ($PROMPT != "")
		{
			print " {$PROMPT} ";
			$PROTO = $CLI->service;
		}else{
			print " Could not get prompt! Aborting!\n"; continue;
		}

		// Make sure we know what we are connected to!
		print "Firewall detection: ";
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
			print " Could not detect device type/model! Aborting!\n"; continue;
		}

		if (preg_match('/(ASA|FWM|PIX)/',$MODEL,$REG))
		{
			$FUNCTION = "Firewall";
			print " YES! Model: {$MODEL}";
		}else{
			print " NO! Model: {$MODEL}";
		}

		// Special handling in case we are in a firewall
		if($FUNCTION == "Firewall")
		{
			print " Firewall, sending enable";
			$COMMAND = "enable\n" . TACACS_ENABLE;	$OUTPUT = $CLI->exec($COMMAND);
			sleep(4);
			$COMMAND = "no pager";					$OUTPUT = $CLI->exec($COMMAND);
			$COMMAND = "terminal pager 0";			$OUTPUT = $CLI->exec($COMMAND);
			$TERMINAL_PAGER_OUTPUT = $OUTPUT;
			print " Pager disabled";
			if (cisco_check_input_error($TERMINAL_PAGER_OUTPUT))
			{
				print " Enabled Successfully!";
			}else{
				print " Error Enabling! Aborting!"; continue;
			}
		}else{
			$CLI->exec("terminal length 0");
			$CLI->exec("terminal width 500");
		}

		// Dont change firewall configs during our pushes
		if ($FUNCTION == "Firewall") { print " This push should not touch firewalls! Aborting!"; continue; }

		// Build the final configuration for this device to push
		$PUSH = array();
		array_push($PUSH,"config t"					);
		foreach(explode("\n",$PUSH_LINES) as $LINE) { array_push($PUSH,$LINE); }
		array_push($PUSH,"end"						);
		//array_push($PUSH,"copy run start\n"		);

		// Debugging before actually running this to make live config changes
		//dumper($PUSH);	die("CROAK!\n");	// Comment me out

		// Perform the config push
		foreach($PUSH as $COMMAND)
		{
			$COMMAND = trim($COMMAND);
//			print "Running: {$COMMAND}\n";
			$OUTPUT = $CLI->exec($COMMAND);
//			print "{$OUTPUT}\n\n";
		}

		// Do a config save and rescan the device
		print " PUSH COMPLETE, saving config and running scan!\n";
		$COMMAND = "timeout 1m php ".BASEDIR."/bin/save-config.php --id={$DEVICE->data['id']} > /dev/null 2>/dev/null";
		print "please run $COMMAND\n";
		//exec($COMMAND);
		$COMMAND = "timeout 1m php ".BASEDIR."/bin/scan-device.php --id={$DEVICE->data['id']} > /dev/null 2>/dev/null";
		print "please run $COMMAND\n";
		//exec($COMMAND);

		unset($DEVICE);
	}
}else{
	print "No devices found!\n";
}
print "ALL PUSHES COMPLETE!\n";

?>
