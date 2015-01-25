#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SUSPECT = "";

// Get command line arguments!
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	$SUSPECT = $args["suspect"];
}

if( $SUSPECT && filter_var($SUSPECT, FILTER_VALIDATE_IP) )
{
	$QUERY = <<<END
		UPDATE information
		SET active = 0
		WHERE category LIKE "Blackhole"
		AND type LIKE "Suspect"
		AND active = 1
		AND stringfield1 = "{$SUSPECT}"
END;
	print "QUERY: {$QUERY}\n";
	$COUNT = 0;
	global $DB;

	$DB->query($QUERY);
	try {
		$COUNT = $DB->execute();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE);
	}
	print "DEACTIVATED {$COUNT} RECORDS!\n";
}else{
	print "ERROR: Suspect not provided!\n";
}

?>
