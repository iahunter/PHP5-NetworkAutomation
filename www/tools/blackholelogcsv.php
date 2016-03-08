<?php
define("NO_AUTHENTICATION",1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

header("Content-Type: text/plain");

global $DB;
$QUERY = <<<END
SELECT date,
    @total := @total + derived.action AS `total`
    FROM (
        SELECT date,
               REPLACE(
                   REPLACE(
                       SUBSTR(
                           description,11,3
                       ), "ADD", 1
                   ), "DEL", -1
               ) AS action
        FROM log
        WHERE tool = 'scan-blackhole.php'
        AND description LIKE 'Blackhole %'
    ) AS derived,
    (SELECT @total := 0) AS initialization;
END;

$DB->query($QUERY);
try {
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	die($MESSAGE);
}

$COUNT = count($RESULTS);

$DELIMITER = ",";
print "DateTime,Total\n";
foreach ($RESULTS as $RESULT)
{
	print "{$RESULT["date"]}"	. $DELIMITER;
	print "{$RESULT["total"]}";
	print "\n";
}
