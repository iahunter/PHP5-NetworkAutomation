<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
header('Content-Type: text/javascript');

// Decide if we are going to throw a boomerang or not! FUTURE: use some random criteria for sample of page loads?
if ($_SESSION["DEBUG"] >= 1 || MONITOR_USER_EXPERIENCE)	// Defined globally in config.inc.php and overridden per page...
{
	$BEACON = Information::create("Beacon","Boomerang");
	$ID = $BEACON->insert();				// Add a NEW boomerang information object to the database
	$BEACON = Information::retrieve($ID);	// Pull it back out of the database just to be sure
	print $BEACON->initialize();			// Print out its initialization javascript for the browser
	$BEACON->update();						// Save our newly initialized beacon!
}

?>
