#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(	// Search for all cisco network devices
				"category"	=> "management",
				"type"		=> "device_network_cisco%",
				);

// Get any command line arguments, add to search criteria
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	foreach($args as $key => $value)
	{
		$SEARCH[$key] = $value;
	}
}

// Get search results
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

foreach ($RESULTS as $DEVICEID)
{
	$DEVICE = Information::retrieve($DEVICEID);
	print "PUSH DEVICE ID {$DEVICE->data['id']} PROMPT {$DEVICE->data['prompt']} IP {$DEVICE->data['ip']}\t";

	// Start with a ping test, see if we can ping the IP
	$PING = new \JJG\Ping($DEVICE->data['ip']);
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
	$MODEL = \metaclassing\Cisco::inventoryToModel($SHOW_INVENTORY);
	if ($MODEL == "Unknown")
	{
		$SHOW_VERSION = $CLI->exec("show version | I C");
		$MODEL = \metaclassing\Cisco::versionToModel($SHOW_VERSION);
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
		if (\metaclassing\Cisco::checkInputError($TERMINAL_PAGER_OUTPUT))
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
	array_push($PUSH,"wr\n"		);

	// Debugging before actually running this to make live config changes
	//\metaclassing\Utility::dumper($PUSH);	die("CROAK!\n");	// Comment me out

	print " ";
	$CLI->settimeout(20);	// Set our timeout HIGH for copy run start stuff, else after 10seconds it fucks up!
	// Perform the config push
	foreach($PUSH as $COMMAND)
	{
		$COMMAND = trim($COMMAND);
		print "Running: $COMMAND\n";
		print $CLI->exec($COMMAND) . "\n\n";
	}

	unset($DEVICE);						// And save some memory
}
