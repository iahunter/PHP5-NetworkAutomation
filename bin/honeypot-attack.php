#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$ATTACKER	= "";
$HONEYPOT	= "";
$SERVICE	= "";

// Get command line arguments!
if (isset($argc))
{
	$cmd = new CommandLine;
	$args = $cmd->parseArgs($argv);

	if ( isset($args["attacker"]) ) { $ATTACKER = $args["attacker"]; }
	if ( isset($args["honeypot"]) ) { $HONEYPOT = $args["honeypot"]; }
	if ( isset($args["service"]	) ) { $SERVICE	= $args["service" ]; }
}

if (!$ATTACKER || !$HONEYPOT || !$SERVICE) { die("ERROR: INCORRECT ARGUMENTS PASSED!\n"); }	// Do not proceed without correct arguments!

//print "Attacker {$ATTACKER} hit honeypot {$HONEYPOT} on port {$SERVICE}!\n";

$CATEGORY   = "Blackhole";
$TYPE       = "Suspect";
$PARENT     = "";

$QUERY = <<<END
	SELECT id AS hits FROM information
	WHERE category LIKE "{$CATEGORY}"
	AND type LIKE "{$TYPE}"
	AND stringfield1 = "{$ATTACKER}"
	AND active = 1
	AND modifiedwhen >= DATE_SUB(NOW(),INTERVAL 1 MINUTE);
END;

$DB->query($QUERY);
try {
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	sleep(3);
	print "No Hacking ;)\n";
	sleep(1);
	die();
}
$COUNT = count($RESULTS);

if ( $COUNT < 10 )
{

	$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);
	$ID = $INFOBJECT->insert();
	$INFOBJECT = Information::retrieve($ID);
	$INFOBJECT->initialize();
	$INFOBJECT->update();

	$INFOBJECT->data["source"]	= $ATTACKER;
	$INFOBJECT->data["target"]	= $HONEYPOT;
	$INFOBJECT->data["port"]	= $SERVICE;

	$INFOBJECT->update();
	//\metaclassing\Utility::dumper($INFOBJECT);
}

sleep(3);
print "No Hacking ;) {$COUNT}\n";
sleep(1);
?>
