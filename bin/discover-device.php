#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$LOG = "DISCOVER ";

// Look for and process all the command line arguments passed by SNMPTT
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	$IP = $args["ip"];

	$CLIARGS = "KEY-VAL:";
	foreach($args as $key => $value)
	{
		$CLIARGS .= " " . $key . '=' . $value;
	}

	$LOG .= "$CLIARGS ";

	if (!filter_var($IP, FILTER_VALIDATE_IP))
	{
		$LOG .= "$IP does not look like an IP address, attempting to resolve... ";
		$IP = gethostbyname($IP);
		if(!filter_var($IP, FILTER_VALIDATE_IP))
		{
			$LOG .= "Error, no IPv4 address passed!";	$DB->log($LOG);
			die("Error: No valid IPv4 address specified!\n");
		}
		$LOG .= "Resolved to $IP ";
	}
}else{
	$LOG .= "Error, no CLI ARGS passed!";			$DB->log($LOG);
	die("Error: Did not recieve commandline arguements!\n");
}

// Checking for duplicate devices already in the database
$SEARCHES = array(
				array(									  // Search for devices with that management IP
					"category"	  => "management",
					"type"		  => "device_network_%",
					"stringfield1"	=> $IP,
					),
				array(									  // Search for devices with that IP in its config
					"category"	  => "management",
					"type"		  => "device_network_%",
					"stringfield5"  => "%ip address {$IP} %",
					),
				);
$RESULTS = Information::multisearch($SEARCHES);

if (count($RESULTS))
{
	$OUTPUT = implode(",",$RESULTS);
	$LOG .= "Aborted, {$IP} in database ID$OUTPUT";	//	$DB->log($LOG); // DEBUGGING ONLY, this logs for every attempted device re-discover!
	die("Aborted: Found $IP in database ID$OUTPUT\n");
}

// The device is in fact new, insert it into the database and launch a scan!

$LOG .= "New device detected, inserting! ";

$CATEGORY   = "Management";
$TYPE       = "Device_Network_Cisco";
$PARENT     = "";

$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);
$INFOBJECT->data['ip'] = $IP;
$ID = $INFOBJECT->insert();

$INFOBJECT = Information::retrieve($ID);
print $INFOBJECT->initialize();
$INFOBJECT->update();

$LOG .= "ID {$ID} Created, launching scan";
$DB->log($LOG);

print "$LOG\n";

$LOG = shell_exec(BASEDIR."/bin/scan-device.php --id={$ID}");
$DB->log($LOG);


  ///////
 //EOF//
///////
