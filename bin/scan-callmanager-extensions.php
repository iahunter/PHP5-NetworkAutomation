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
	$EXTLIST = ssh_get_extensions($CM);					//print "{$EXTLIST}\n";
	$EXTARRAY = callmanager_parse_extensions($EXTLIST);	//dumper($EXTARRAY);
	$COUNT = count($EXTARRAY);
	$EXTENSIONS[$CM] = $EXTARRAY;
	print "!!!\tGot {$COUNT} extensions from {$CM}!\n";
}

foreach ($EXTENSIONS as $CM => $EXTENSIONLIST)
{
	foreach($EXTENSIONLIST as $EXTENSION)
	{
		$EXTENSION["CallManager"] = $CM;
		$ID = sql_save_phone($EXTENSION);
		print "Attempted to save extension {$EXTENSION["DeviceName"]} got id {$ID}\n";
	}
}

?>
