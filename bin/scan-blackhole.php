#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(	// Search for all cisco network devices
				"category"	=> "blackhole",
				"type"		=> "sensor_%",
				);

// Get any command line arguments, add to search criteria
/*if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	foreach($args as $key => $value)
	{
		$SEARCH[$key] = $value;
	}
}/**/

// Get search results
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

foreach ($RESULTS as $SENSORID)
{
	$SENSOR = Information::retrieve($SENSORID);
	$OUTPUT = $SENSOR->scan();
	unset($SENSOR);
/*
	$LOGTO   = "mr.admin@domain.com";
	$LOGFROM = "network.auto@domain.com";
	$LOGHEADER = "From: NetworkTool <{$LOGFROM}>\r\nX-Mailer: php";
	$LOGSUB  = "Blackhole";
	$LOGBODY = $OUTPUT;
	mail($LOGTO, $LOGSUB, $LOGBODY, $LOGHEADER);/**/
}
