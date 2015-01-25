<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports/");
$HTML->breadcrumb("Cost Item Change Report",$HTML->thispage);
print $HTML->header("Cost Item Change Report");


$QUERY = <<<END
	SELECT id FROM information
	WHERE category LIKE 'company'
	AND type like 'jobsite_costitem'
	AND modifiedwhen BETWEEN NOW() - INTERVAL 30 DAY AND NOW()
END;

$DB->query($QUERY);
try {
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE . $HTML->footer());
}

// Now run an information search on our results array from the SQL query!
$RESULTS = Information::multisearch($RESULTS);
$RECORDCOUNT = count($RESULTS);

if (!$RECORDCOUNT) { print "No cost items found modified in the past 30 days.\n"; die($HTML->footer()); }

$FIRSTRECORD = reset($RESULTS);
$FIRSTOBJECT = Information::retrieve($FIRSTRECORD);
$OUTPUT .= $FIRSTOBJECT->html_list_header();
$i = 1;
foreach ($RESULTS as $RESULT)
{
    $INFOBJECT = Information::retrieve($RESULT);
    $OUTPUT .= $INFOBJECT->html_list_row($i++);
    unset ($INFOBJECT); // THIS FREES UP MEMORY WHEN LISTING VERY LARGE SETS!
}
$OUTPUT .= $FIRSTOBJECT->html_list_footer();
unset($FIRSTOBJECT);

print $OUTPUT;

print $HTML->footer();

?>

