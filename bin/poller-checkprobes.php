#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// This app checks the pulse of Kiewality/Probes, it should be called from a CRON job every minute!

$CATEGORY	= "kiewality";
$TYPE		= "probe";

$SEARCH = array();
$SEARCH["category"		] = $CATEGORY;
$SEARCH["type"			] = $TYPE;
$SEARCH["stringfield5"	] = "up";	// Only take the pulse of probes with status UP

$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);

print "Found {$COUNT} Kiewality Probes alive\n";

foreach ($RESULTS as $ID)
{
	$PROBE = Information::retrieve($ID);
	$STATUS = $PROBE->data["status"];
	$PULSE = $PROBE->check_pulse();
	if ( !$PULSE )	// Check the pulse of every probe that was up at last check
	{
		$MESSAGE = "PROBE ID:{$ID} changed state {$STATUS} to {$PROBE->data["status"]}";
		global $DB;
		$DB->log($MESSAGE,2);	print "{$MESSAGE}\n";
		$PROBE->update();
	}else{
		print "Probe {$PROBE->data["id"]} still alive for another {$PULSE} seconds\n";
	}
	unset($PROBE);
}

  ///////
 //EOF//
///////
