#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
require_once "include/libtravis.inc.php";

// Variables for this scanner to use

$PORT = 80;			// TCP port number to scan
$FIELD = "TCP80";	// Database field to update

/////////////////////////////////////////////////

$PHONES = sql_get_phones();	// Get an array of all our phone records!
$COUNT = count($PHONES);
print "!!!Probing TCP port {$PORT} on {$COUNT} phones...\n";

foreach ($PHONES as $PHONE)
{
	if ($PHONE[$FIELD] == "LISTENING") { continue; }	// If we scanned the phone already and it was listening, we dont care anymore...
	$IP = $PHONE["Ipaddr"];
	if(!filter_var($IP, FILTER_VALIDATE_IP))	// use PHP's built in data validation to ensure $IP is REALLY an IPv4 address
	{
		print "Error: {$IP} does not validate as an IPv4 dotted decimal! Skipping...\n";
		continue;								// Break out of this instance of the foreach loop and move on to the next record!
	}else{
		print "TESTING\t{$IP}\t";
		if ( testconnect($IP,$PORT) )			// Testconnect is a function in libjohn i use for "stuff and things"
		{
			$PHONE[$FIELD] = "LISTENING";
		}else{
			$PHONE[$FIELD] = "CLOSED";
		}
		print "{$PHONE[$FIELD]}\n";				// Print the state we discovered
		sql_save_phone($PHONE);					// Save the phone record back to the database
    }
}

/////////////////////////////////////////////////////////////////////////////////////

?>
