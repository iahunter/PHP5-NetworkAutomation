#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// Get command line arguments!
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);
	// This app doesnt use any but worth having this blurb for all CLI apps...
}

$QUERY = <<<END
	SELECT stringfield1 AS ip , count(stringfield1) AS hits FROM information
	WHERE category LIKE "Blackhole"
	AND type LIKE "Suspect"
	AND active = 1
	GROUP BY stringfield1
	HAVING hits >= 3
	ORDER BY stringfield1
END;

global $DB;

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

foreach ($RESULTS as $SUSPECT)
{
//	dumper($SUSPECT);
	// Only print out suspects that are valid IPv4 addresses!
	if( $SUSPECT["ip"] && filter_var($SUSPECT["ip"], FILTER_VALIDATE_IP) )
	{
		print "{$SUSPECT["ip"]}\n";
	}
}

?>
