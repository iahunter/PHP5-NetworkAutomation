#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
require_once "include/libtravis.inc.php";

// List of callmanagers to go talk to
$CALLMANAGERS = array(
	"10.252.11.12",
	"10.252.11.13",
	"10.252.11.14",
	"10.252.22.11",
	"10.252.22.12",
	"10.252.22.13",
	"10.252.22.14",
);

$COUNT = count($CALLMANAGERS);
print "!!!Asking {$COUNT} callmanagers for their active subscribers...\n";

$PHONES = array();	$EXTENSIONS = array();

foreach ($CALLMANAGERS as $CM)
{
	print "!!!Talking to callmanager {$CM} ...\n";
	$PHONELIST = ssh_get_phones($CM);					//print "{$PHONELIST}\n";
	$PHONEARRAY = callmanager_parse_phones($PHONELIST);	//dumper($PHONEARRAY);
	$COUNT = count($PHONEARRAY);
	$PHONES[$CM] = $PHONEARRAY;
	print "!!!\tGot {$COUNT} phones from {$CM}!\n";
}

foreach ($PHONES as $CM => $PHONELIST)
{
	foreach($PHONELIST as $PHONE)
	{
		$PHONE["CallManager"] = $CM;
		$ID = sql_save_phone($PHONE);
		print "Attempted to save phone {$PHONE["DeviceName"]} got id {$ID}\n";
	}
}

/////////////////////////////////////////////////////////////////////////////////////

?>
