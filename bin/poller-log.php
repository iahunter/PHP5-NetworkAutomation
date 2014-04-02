#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$LOG = "POLLER ";

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
}else{
	$LOG .= "Error, no CLI ARGS passed!";			$DB->log($LOG);
	die("Error: Did not recieve commandline arguements!\n");
}

$DB->log($LOG,1);
?>

