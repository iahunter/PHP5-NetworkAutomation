#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// This app runs the heartbeat for the Kiewality/Probe, it should be called from CRON every minute!

$CATEGORY	= "kiewality";
$TYPE		= "probe";

$SEARCH = array();
$SEARCH["category"	] = $CATEGORY;
$SEARCH["type"		] = $TYPE;

// Using PROBE_ID is untested at this time, I only have name-based probing
/*
if ( defined("PROBE_ID") )					// Using a forced probe ID overrides all other directives, assumes record exists in DB
{
	$SEARCH["id"] = PROBE_ID;				// Use the config.inc.php defined value for our kiewality/probe information object id
	$RESULTS = Information::search($SEARCH);
	$COUNT = count($RESULTS);

	if ( $COUNT == 1 )						// If we got exactly one hit (yay!) do our heartbeat
	{
		$ID = reset($RESULTS);
		$PROBE = Information::retrieve($ID);
		if( $PROBE->heartbeat() ) { $PROBE->update(); }		dumper($PROBE);
		unset($PROBE);
	}else{									// Otherwise chuck an error
		print "ERROR: Got {$COUNT} results for search:\n"; var_dump($SEARCH); var_dump($RESULTS);
	}
}else /**/ if ( defined("PROBE_NAME") )			// Using a NAME will search for / autocreate a new probe object in the DB
{
	$SEARCH["stringfield1"] = PROBE_NAME;	// Use the config.inc.php defined value for our kiewality/probe information object id
	$RESULTS = Information::search($SEARCH);
	$COUNT = count($RESULTS);

	if ( $COUNT == 1 )						// We got exactly 1 hit, run the heartbeat
	{
		$ID = reset($RESULTS);
		$PROBE = Information::retrieve($ID);
		$STATUS = $PROBE->data["status"];
		print "Found existing probe ID {$ID} STATUS {$STATUS} - Running Tests:";
		$PROBE->run_tests();
		unset($PROBE);
	}else{
		print "ERROR: Got {$COUNT} results for search:\n"; var_dump($SEARCH); var_dump($RESULTS);
	}

	// Flush out any noisy logs our testing created!
    $QUERY = <<<END
        DELETE
        FROM log
        WHERE user = :LDAPUSER
END;

	$RECORDCOUNT = $DB->RECORDCOUNT;
	$DB->query($QUERY);
	try {
		$DB->bind("LDAPUSER",LDAP_USER);
		$DB->execute();
	} catch (Exception $E) {
            $MESSAGE = "Exception: {$E->getMessage()}";
            trigger_error($MESSAGE);
            die("\n\n" . $MESSAGE . "\n\n");
    }
    $RECORDCOUNT = $DB->RECORDCOUNT - $RECORDCOUNT;	// Find the number of records we changed in this query!
    print "Flushed {$RECORDCOUNT} log records.\n";

	// Flush out OLD RESULTS
    $QUERY = <<<END
        DELETE
		FROM information
		WHERE  category = "Kiewality"
		AND type like "result%"
		AND modifiedwhen <= NOW() - INTERVAL 1 WEEK

END;

	$RECORDCOUNT = $DB->RECORDCOUNT;
	$DB->query($QUERY);
	try {
		$DB->execute();
	} catch (Exception $E) {
            $MESSAGE = "Exception: {$E->getMessage()}";
            trigger_error($MESSAGE);
            die("\n\n" . $MESSAGE . "\n\n");
    }
    $RECORDCOUNT = $DB->RECORDCOUNT - $RECORDCOUNT;	// Find the number of records we changed in this query!
    print "Flushed {$RECORDCOUNT} result records.\n";

}else{
	die("ERROR: Neither probe name (autocreate) nor probe ID (forced db infobj id) are defined!\n");
}

  ///////
 //EOF//
///////
