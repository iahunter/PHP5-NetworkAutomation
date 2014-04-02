#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(	// Search for all cisco network devices
				"category"	=> "management",
				"type"		=> "device_network_cisco%",
				);

// Get any command line arguments, add to search criteria
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	foreach($args as $key => $value)
	{
		$SEARCH[$key] = $value;
	}
}

// Get search results
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

foreach ($RESULTS as $DEVICEID)
{
	$DEVICE = Information::retrieve($DEVICEID);
	print $DEVICE->scan();				// Scan AND update the device information, print the results
	unset($DEVICE);						// And save some memory
}
