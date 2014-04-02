<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

if (!isset($_GET['id']))
{
	print "Error, no device id passed\n";
	die();
}else{
	$ID = $_GET['id'];
}

if (!isset($_GET['field']))
{
	print "Error, no device field passed\n";
	die();
}else{
	$FIELD = $_GET['field'];
}

if (isset($_GET['hilight']))
{
	$REGEX = "/.*(" . preg_quote($_GET['hilight'],"/") . ").*/i";
}else{
	$REGEX = "/this-will-never-match-anything/";
}

$DEVICE = Information::retrieve($ID);

$LINES = explode("\n",$DEVICE->data[$FIELD]);
if (count($LINES) < 2) { print "Notice: The 'show {$FIELD}' command did not produce output on this device."; }

foreach($LINES as $LINE)
{
	$LINE = rtrim($LINE);
	if(preg_match($REGEX,$LINE,$REG))
	{
		print "<font style=\"color: red; background-color:yellow;\">{$LINE}</font><br>";
	}else{
		print "{$LINE}<br>";
	}
}

?>
