#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

//////////////////////////
$MAXTHREADS = 60;		// Max number of forked processes
$MAXTIME	= 130;		// Allow a 130 second maximum time for a worker
//////////////////////////

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
	SELECT id,stringfield0 as name,stringfield1 as ip,stringfield2 as protocol FROM information
	WHERE category LIKE 'Management'
	AND type LIKE 'Device_Network_Cisco%'
	AND active = 1
	HAVING name != ""
	AND name != "ecdonmilswc01"
	AND name != "kswazphxswp04"
	AND name != "kbgnetdarwa01"
	AND name != "wcdmbdbjvrt01"
	AND name != "tuswyanarfw01"
	AND name != "ecdnlstjrfw01"
	AND name != "khonesdcswi03"
	AND name != "khonesdcswi04"
	AND name != "khonemdcswi03"
	AND name != "khonemdcswi04"
	AND name != "a3nexu5000"
	AND name != "b3nexu5000"
	AND name != "a4nexu5000"
	AND name != "b4nexu5000"
	AND name != "a9nexu5000"
	AND name != "b9nexu5000"
	AND name != "a10nexu5000"
	AND name != "b10nexu5000"
	AND name != "khonemdcswi01"
	AND name != "khonestdrbh01"
	AND protocol != "none"
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
print "Query returned {$RECORDCOUNT} device records.\n";

// DANGER ZONE! // CLOSE ANY CONNECTIONS, DATABASE HANDLES, FILES, ETC! BEFORE CROSSING THIS LINE!
	// FORK will inherit ALL handles and on first worker-thread exit they will be DESTROYED!

unset($DB);	// Double checking safeguard precaution

$THREADS	= array();	// Child process tracking array
$STARTTIME	= microtime(TRUE);	// Starting Timestamp

foreach ($RESULTS as $DEVICE)
{
	if (count($THREADS) >= $MAXTHREADS)		// Limit number of simultanious workers
	{
		$PID = pcntl_waitpid(-1, $STATUS);	// Wait for any PID of a thread to exit
		unset($THREADS[$PID]); 				// Remove PID that exited from the thread list
	}

	usleep(100000);		// Do not start more than 10 new threads per second!
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

		try {
		    $DB = new Database();
		} catch (Exception $E) {
		    $MESSAGE = "Exception: {$E->getMessage()}";
		    trigger_error($MESSAGE);
		    die($MESSAGE);
		}
/**/
//		print "STARTING WORK {$PID}: Device ID {$DEVICE['id']} PROMPT {$DEVICE['name']} IP {$DEVICE['ip']}\n";

		// Execute config download in FORKED memory space
		$SUCCESS = \metaclassing\Cisco::downloadConfig($DEVICE); /**/

		// Execute config download in NEW memory space (more resource intensive)
/*		$COMMAND = "timeout 1m ".BASEDIR."/bin/config-download.php --id={$DEVICE['id']}";
		exec($COMMAND); /**/

		$ELAPSED = microtime(TRUE) - $STARTTIME;						// Calculate elapsed time for total work
		$WORKERTIME = microtime(TRUE) - $THREADS[$PID]["starttime"];	// Calculate elapsed time for this worker!
		$OUTPUT = "WORK ";
		if ($SUCCESS) { $OUTPUT .= "SUCCEEDED"; }else{ $OUTPUT .= "FAILED"; }
		$OUTPUT .= " {$PID}: Device ID {$DEVICE['id']} PROMPT {$DEVICE['name']} IP {$DEVICE['ip']} WORKER TIME: {$WORKERTIME} PROCESS TIME: {$ELAPSED}\n";
		print $OUTPUT;

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
