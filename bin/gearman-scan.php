#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// Make the errors a little noisy with this tool
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_USER_NOTICE);

// ONLY let this run from the command line, NOT a browser
if (php_sapi_name() != "cli") { die("This is a CLI tool only!"); }

// Collect CLI input from the user to customize the query
if (isset($argc))
{
    $CMD = new CommandLine;
    $ARGS = $CMD->parseArgs($argv);
    $ADD = "";
    foreach($ARGS as $KEY => $VALUE)
    {
        $ADD .= " AND {$KEY} LIKE '{$VALUE}'";
    }
}

// Build our query based on CLI input (if any)
$QUERY = <<<END
	SELECT id,stringfield0 AS name,stringfield1 AS ip FROM information
	WHERE category LIKE 'Management'
	AND type LIKE 'Device_Network_Cisco%'
	AND active = 1
	{$ADD}
	ORDER BY id
END;
//print "QUERY: {$QUERY}\n";

// Start our clock for application runtime
$STARTTIME = microtime(TRUE);  // Starting Timestamp

$DB->query($QUERY);
try {
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

$RECORDCOUNT = count($RESULTS);
$DB->log("SCANNING {$RECORDCOUNT} DEVICES",2);
$ELAPSED = microtime(TRUE) - $STARTTIME;
print "Query returned {$RECORDCOUNT} device records in {$ELAPSED} seconds.\n";

// Process the resulting items one at a time to add to the list table & sandwich them between header and footer
if (count($RESULTS) > 0)
{
	$GEARMAN	= new Gearman_Client;
	$FUNCTION	= "information-action";
	$QUEUE		= "poller";
	$WORK		= "{$FUNCTION}-{$QUEUE}";

	// Loop through all the results and create gearman worker tasks
	foreach ($RESULTS as $RECORD)
	{
		$DATA = array();
		$DATA["id"]		= $RECORD["id"];	// ID of the object to call
		$DATA["method"]	= "scan";			// Member function of object to call
//		$DATA["acl"] = $ACL;				// Any custom data in array form to pass to the member function!
		$GEARMAN->addTask($WORK, $DATA);
//		break;
	}

	// Set a timeout for the job and submit all tasks in parallel!
	$GEARMAN->setTimeout(600000);	// Timeout set (600 seconds = 10 minutes!) for all jobs to complete
	$ELAPSED = microtime(TRUE) - $STARTTIME; print "Job started all tasks at time {$ELAPSED} seconds.\n";
	if ( !$GEARMAN->runTasks() )	// Wait for all to finish!
	{
		$DB->log("GEARMAN ERROR: " . $GEARMAN->error() );
		die("ERROR: " . $GEARMAN->error() . "\n");
	}
	$ELAPSED = microtime(TRUE) - $STARTTIME; print "All jobs ended at time {$ELAPSED} seconds.\n";

	// After the job is done, check all the tasks for output and errors!
	foreach ($GEARMAN->tasks as $HANDLE => $TASKINFO)
	{
		if ( isset($TASKINFO["output"]) )
		{
			print "{$TASKINFO["output"]}";
		}else{
			print "ERROR! " . \metaclassing\Utility::dumperToString($TASKINFO) . "\n";
		}
	}
}

$TOTALTIME = microtime(TRUE) - $STARTTIME;
print "\nAll Jobs Complete! Total time: {$TOTALTIME}\n\n";

?>
