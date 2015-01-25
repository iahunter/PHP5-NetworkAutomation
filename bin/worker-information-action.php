#!/usr/bin/php
<?php
require_once("/etc/networkautomation/networkautomation.inc.php");
// ONLY let this run from the command line, NOT a browser
if (php_sapi_name() != "cli") { die("This is a CLI tool only!\n"); }
// Get command line arguments
if ( isset($argc) ) { $CMD = new CommandLine; $ARGS = $CMD->parseArgs($argv); }
if ( !isset($ARGS["workerid"]) || $ARGS["workerid"	] == "" ) { die("ERROR: Worker not uniquely identified with --workerid=123\n");	}
if ( !isset($ARGS["queue"	]) || $ARGS["queue"		] == "" ) { die("ERROR: Queue not specified with --queue=abc\n");				}
if ( !isset($ARGS["lifetime"]) || $ARGS["queue"		] == "" ) { die("ERROR: Worker lifetime not specified with --lifetime=50\n");	}

$WORKERDATA = array();	// Workerdata is passed by reference to the callback function for intra-worker communication
$WORKERDATA["hostname"]	= HOSTNAME;
$WORKERDATA["workerid"]	= $ARGS["workerid"];
$WORKERDATA["task"]		= "information-action";
$WORKERDATA["function"]	= "information_action_worker";
$WORKERDATA["queue"]	= $ARGS["queue"];
$WORKERDATA["work"]		= "{$WORKERDATA["task"]}-{$WORKERDATA["queue"]}";
$WORKERDATA["print"]	= "Worker {$WORKERDATA["hostname"]} / {$WORKERDATA["workerid"]}";
$WORKERDATA["lifetime"]	= $ARGS["lifetime"];	// Run a maximum of X jobs before self termination and respawn

print "{$WORKERDATA["print"]} starting...\n";
sleep(1); // IMPORTANT! Put in a sleep 1 so that we can differentiate between a major error / php fatal exit	and a NORMAL exit thats too quick!
$WORKER = new GearmanWorker();
$WORKER->setId("{$WORKERDATA["hostname"]} / {$WORKERDATA["workerid"]}");	// Does not work...
$WORKER->addServer(GEARMAN_SERVER,GEARMAN_PORT);							// I suppose this COULD fail, but i havent been able to cause that...
$WORKER->addFunction($WORKERDATA["work"], $WORKERDATA["function"], $WORKERDATA);		// Same with this...

print "{$WORKERDATA["print"]} online! Waiting for work...\n";
$i = 0;
do
{
	$WORKERDATA["status"]	= "running"; $WORKERDATA["error"]   = ""; // Reset status and error codes for next job
	$WORKERDATA["iteration"]++;
	$WORKER->work();
	print "{$WORKERDATA["print"]} finished iteration {$WORKERDATA["iteration"]} of {$WORKERDATA["lifetime"]} status {$WORKERDATA["status"]} {$WORKERDATA["error"]}\n";
}while( $WORKER->returnCode() == GEARMAN_SUCCESS && $WORKERDATA["iteration"] < $WORKERDATA["lifetime"] );
print "{$WORKERDATA["print"]} terminating!\n";
exit(0);

///////////////////////////////
// Function hit by callback! //
function information_action_worker($JOB)
{
	global $WORKERDATA;	// The pass by reference is not ^--worker readable as of 11/08/2014...
	$JOBDATA = $JOB->workload(); $JOBDATA = json_decode($JOBDATA,true); // Decode JSON job data into an array
//	dumper($JOBDATA);
	if ( !isset($JOBDATA["id"]) || !intval($JOBDATA["id"]) || !isset($JOBDATA["method"]) )
	{
		$WORKERDATA["status"]	= "failure"; $WORKERDATA["error"]	= "Info Object ID & Method Not Passed!";
		$JOB->sendException("{$WORKERDATA["status"]} {$WORKERDATA["error"]}"); $JOB->sendFail();
		return 0;
	}
	$INFOBJECT = Information::retrieve($JOBDATA["id"]);
	if( !method_exists($INFOBJECT, $JOBDATA["method"]) )
	{
		$WORKERDATA["status"]	= "failure"; $WORKERDATA["error"]	= "Function {$JOBDATA["method"]} not a method of Object ID {$JOBDATA["id"]}!";
		$JOB->sendException("{$WORKERDATA["status"]} {$WORKERDATA["error"]}"); $JOB->sendFail();
		return 0;
	}
	$OUTPUT = $INFOBJECT->$JOBDATA["method"]($JOBDATA);
	$WORKERDATA["status"] = "success";
//	print "{$WORKERDATA["print"]} result: {$OUTPUT}\n";
	return $OUTPUT;
}

?>
