#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

////////////////////////////////////////
$MAXTHREADS = 20;		// Max number of forked processes
$MAXTIME	= 130;		// Allow a <-- second maximum time for a worker
////////////////////////////////////////

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_USER_NOTICE);
//error_reporting(E_ALL);

if (php_sapi_name() != "cli")
{
	die("This is a CLI tool only!");
}

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

$QUERY = <<<END
	SELECT id FROM information
	WHERE category LIKE "Management"
	AND type LIKE "Device_Network_Cisco"
	AND stringfield0 != ""
	AND stringfield2 != "none"
	AND stringfield5 LIKE "%snmp-server community%"
	{$ADD}
	ORDER BY id
END;
//print "QUERY: {$QUERY}\n";

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
$DB->log("PUSHING {$RECORDCOUNT} DEVICES",2);
print "Query returned {$RECORDCOUNT} device records.\n";

// DANGER ZONE! // CLOSE ANY CONNECTIONS, DATABASE HANDLES, FILES, ETC! BEFORE CROSSING THIS LINE!
	// FORK will inherit ALL handles and on first worker-thread exit they will be DESTROYED!

unset($DB); // Extra safety precaution

$THREADS	= array();	// Child process tracking array
$STARTTIME	= microtime(TRUE);	// Starting Timestamp

foreach ($RESULTS as $DEVICE)
{
	if (count($THREADS) >= $MAXTHREADS)		// Limit number of simultanious workers
	{
		$PID = pcntl_waitpid(-1, $STATUS);	// Wait for any PID of a thread to exit
		unset($THREADS[$PID]); 				// Remove PID that exited from the thread list
	}

	usleep(100000);			// Do not start more than 10 new threads per second!
	$PID = pcntl_fork();	// Fork the process

	if ($PID)
	{
		////////////////////////////
		// WE ARE STILL THE PARENT!/
		////////////////////////////
		if ($PID < 0) {
			// Unable to fork process, handle error here
			continue;
		} else {
			$THREADS[$PID] = array();						// Add the PID to the thread tracking array
			$THREADS[$PID]["pid"] = $PID;					// make sure the associative array has a pid element
			$THREADS[$PID]["starttime"] = microtime(TRUE);	// capture a start time for the worker thread
//var_dump($pids);
		}

	} else {
		////////////////////////////
		// WE ARE NOW THE CHILD !!!/
		////////////////////////////
		$PID = getmypid();								// We are now a different PID, lets get that!

		$THREADS[$PID] = array();						// Add the PID to the thread tracking array
		$THREADS[$PID]["pid"] = $PID;					// make sure the associative array has a pid element
		$THREADS[$PID]["starttime"] = microtime(TRUE);	// capture a start time for the worker thread

//		print "STARTING WORK {$PID}: Device ID {$DEVICE['id']} PROMPT {$DEVICE['prompt']} IP {$DEVICE['ip']}\n";

		// Execute config download in FORKED memory space
//		$SUCCESS = \metaclassing\Cisco::downloadConfig($DEVICE); /**/

		// Execute config download in NEW memory space (more resource intensive)
		$COMMAND = "timeout 1m ".BASEDIR."/bin/push.php --id={$DEVICE['id']}";
		exec($COMMAND); /**/

		$ELAPSED = microtime(TRUE) - $STARTTIME;						// Calculate elapsed time for total work
		$WORKERTIME = microtime(TRUE) - $THREADS[$PID]["starttime"];	// Calculate elapsed time for this worker!
		print "WORK ";
//		if ($SUCCESS) { print "SUCCEEDED"; }else{ print "FAILED"; }
		print " {$PID}: Device ID {$DEVICE['id']}\tWORKER TIME: {$WORKERTIME} PROCESS TIME: {$ELAPSED}\n";

		exit(0);
	}
}

// Now wait for the child processes to exit. This approach may seem overly
// simple, but because of the way it works it will have the effect of
// waiting until the last process exits and pretty much no longer

while(count($THREADS) > 0)
{
	foreach($THREADS as $KEY => $VALUE)						// Search the array for workers taking too long
	{
		$ID = pcntl_waitpid(-1, $STATUS, WNOHANG);			// Check if any workers died
		if (isset($THREADS[$ID])) { unset($THREADS[$ID]); }	// Remove dead workers
	}
	foreach($THREADS as $KEY => $VALUE)						// Search the array for workers taking too long
	{
		$ELAPSED = microtime(TRUE) - $THREADS[$KEY]["starttime"];
		if( $ELAPSED > $MAXTIME )							// If they are too old, kill them
		{
			print "CHILD TIMED OUT! KILLING HUNG CHILD {$KEY} !\n";
			posix_kill($KEY,SIGKILL);
			unset($THREADS[$KEY]);
		}else{
//			print "Thread {$KEY} elapsed time {$ELAPSED}\n";
		}
	}
	usleep(100);
//	sleep(1);
}

$TOTALTIME = microtime(TRUE) - $STARTTIME;
print "\nAll Jobs Complete! Total time: {$TOTALTIME}\n\n";

// Now the parent process can do it's cleanup of the results

?>
