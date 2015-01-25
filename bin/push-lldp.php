#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// What we want to push to the devices we find
$PUSH_LINES = array("lldp run");

// Make the errors a little noisy with this tool
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_USER_NOTICE);

// ONLY let this run from the command line, NOT a browser
if (php_sapi_name() != "cli")
{
    die("This is a CLI tool only!");
}

// Use the object (information store) search function to find the devices we WANT to push to
$SEARCH = array();

// what we want to find
$SEARCH = array(    // Search for all cisco network devices
                "category"		=> "Management",
                "type"			=> "Device_Network_Cisco",
				"stringfield2"	=> "ssh%",
				"stringfield8"	=> "WS-C%",
                );

// Do the actual search
$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);

print "Found {$COUNT} devices matching search criteria\n";

// Debugging
//dumper($RESULTS);

// Loop through all the ID numbers we get back from the search
foreach($RESULTS as $OBJECTID)
{
	// Get the information for the device matching the specific ID
	$DEVICE = Information::retrieve($OBJECTID);

	print "DEVICE ID {$OBJECTID}\tNAME {$DEVICE->data["name"]}\tIP {$DEVICE->data["ip"]}   \tPROTO {$DEVICE->data["protocol"]}\tMODEL {$DEVICE->data["model"]}";
	// debugging
	//die( dumper($DEVICE) );

	// Lets search the running config to see if it contains something
	$PATTERN = "/lldp run/";
	if ( preg_match($PATTERN,$DEVICE->data["run"],$REG) )
	{
		print "\tLLDP RUNNING";
	}else{
		print "\tLLDP MISSING";
		// since LLDP is missing, lets do something to fix it!
		$DEVICE->push($PUSH_LINES);
	}

	// Print a newline at the end of each record we process
	print "\n";
	// free up some memory for the device we pulled out of the DB
	unset($DEVICE);
}

?>
