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
	SELECT id, stringfield1 AS ip , stringfield2 AS target , stringfield3 AS port FROM information
	WHERE category LIKE "Blackhole"
	AND type LIKE "Suspect"
	AND active = 1
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

print "SUSPECT TARGET PORT\n";
foreach ($RESULTS as $SUSPECT)
{
//	\metaclassing\Utility::dumper($SUSPECT);
	print "{$SUSPECT["id"]}\t{$SUSPECT["ip"]}\t{$SUSPECT["target"]}\t{$SUSPECT["port"]}\n";
}

?>
