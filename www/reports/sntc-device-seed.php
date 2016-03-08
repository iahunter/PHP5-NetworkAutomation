<?php
define("NO_AUTHENTICATION",1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(
				"category"      => "Management",
				"type"          => "device_network_%",
				);
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

header("Content-Type: text/plain");
print "IP\tHOSTNAME\tFOLDER\n";
foreach ($RESULTS as $DEVICEID)
{
	$INFOBJECT = Information::retrieve($DEVICEID);
	$DEVICENAME		= $INFOBJECT->data["name"];
	$DEVICEIP		= $INFOBJECT->data["ip"];
	$SITE           = substr($DEVICENAME,0,8);

	print "{$DEVICEIP}\t";
	print "{$DEVICENAME}\t";
	print "{$SITE}\n";

	unset($INFOBJECT);
}

?>
