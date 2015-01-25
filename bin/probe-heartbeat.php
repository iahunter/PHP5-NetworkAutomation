#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// This app runs the heartbeat for the Kiewality/Probe, it should be called from CRON every minute!

$CATEGORY	= "kiewality";
$TYPE		= "probe";

$SEARCH = array();
$SEARCH["category"	] = $CATEGORY;
$SEARCH["type"		] = $TYPE;

// Using PROBE_ID is untested at this time, I only have name-based probing
/*
if ( defined("PROBE_ID") )					// Using a forced probe ID overrides all other directives, assumes record exists in DB
{
	$SEARCH["id"] = PROBE_ID;				// Use the config.inc.php defined value for our kiewality/probe information object id
	$RESULTS = Information::search($SEARCH);
	$COUNT = count($RESULTS);

	if ( $COUNT == 1 )						// If we got exactly one hit (yay!) do our heartbeat
	{
		$ID = reset($RESULTS);
		$PROBE = Information::retrieve($ID);
		if( $PROBE->heartbeat() ) { $PROBE->update(); }		dumper($PROBE);
		unset($PROBE);
	}else{									// Otherwise chuck an error
		print "ERROR: Got {$COUNT} results for search:\n"; var_dump($SEARCH); var_dump($RESULTS);
	}
}else /**/ if ( defined("PROBE_NAME") )			// Using a NAME will search for / autocreate a new probe object in the DB
{
	$SEARCH["stringfield1"] = PROBE_NAME;	// Use the config.inc.php defined value for our kiewality/probe information object id
	$RESULTS = Information::search($SEARCH);
	$COUNT = count($RESULTS);

	if ( $COUNT == 0 )						// We got ZERO hits, lets create a NEW probe with our name!
	{
		$PROBE = Information::create($TYPE,$CATEGORY);
		$PROBE->data["name"]		= PROBE_NAME;
		$PROBE->data["description"]	= "Auto-created Probe";
		$PROBE->data["status"]		= "down";
		$PROBE->data["interfaces"]	= Utility::ifconfig_interfaces();
		$ID = $PROBE->insert();

		// Log our new object creation
		$MESSAGE = "Information Added ID:{$ID} CATEGORY:{$CATEGORY} TYPE:{$TYPE}";
		global $DB;
		$DB->log($MESSAGE,2);
		$PROBE = Information::retrieve($ID);
		$PROBE->update();
		print "CREATED NEW PROBE ID {$ID}\n";

		// Run the search again and see if we get exactly 1 hits now!
		$RESULTS = Information::search($SEARCH);
		$COUNT = count($RESULTS);
	}

	if ( $COUNT == 1 )						// We got exactly 1 hit, run the heartbeat
	{
		$ID = reset($RESULTS);
		$PROBE = Information::retrieve($ID);
		$STATUS = $PROBE->data["status"];
		print "Found existing probe ID {$ID} STATUS {$STATUS}\n";
		if( $PROBE->heartbeat() )					// IF our heartbeat is successful
		{
			$PROBE->update();						// First Update the probe object
			if ( $STATUS != $PROBE->data["status"])	// Then check if we have a CHANGE in status (down->up)
			{
				$MESSAGE = "PROBE ID:{$ID} changed state {$STATUS} to {$PROBE->data["status"]}";
				global $DB;
				$DB->log($MESSAGE,2);	print "{$MESSAGE}\n";
			}
		}else{
			print "ERROR: Probe heartbeat function returned FAILED!\n";
		}
		unset($PROBE);
	}else{
		print "ERROR: Got {$COUNT} results for search:\n"; var_dump($SEARCH); var_dump($RESULTS);
	}
}else{
	die("ERROR: Neither probe name (autocreate) nor probe ID (forced db infobj id) are defined!\n");
}

  ///////
 //EOF//
///////
