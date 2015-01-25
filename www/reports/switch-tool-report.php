<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","");
$HTML->breadcrumb("Switch Viewer Utilization Report",$HTML->thispage);
print $HTML->header("Switch Viewer Utilization Report");

$QUERY = <<<END
	SELECT id,date,tool FROM log
	WHERE tool LIKE '%switch-view%'
	OR tool LIKE '%switch-edit%'
END;

$DB->query($QUERY);
try {
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	global $HTML;
	die($MESSAGE . $HTML->footer());
}

$DATA = array();
foreach ($RESULTS as $RESULT)
{
	if (!isset($DATA[$RESULT['tool']])) { $DATA[$RESULT['tool']] = array(); }
	$DATE = date_parse($RESULT['date']);
	$YEAR = $DATE['year'];
	$MONTH = str_pad($DATE['month'], 2, "0", STR_PAD_LEFT);	// Always format our month as 07 instead of just 7.
	$YEARMONTH = $YEAR . '-' . $MONTH;
	if (!isset($DATA[$RESULT['tool']][$YEARMONTH])) { $DATA[$RESULT['tool']][$YEARMONTH] = 0; }
	$DATA[$RESULT['tool']][$YEARMONTH]++;
}

print "<img src=\"/reports/switch-tool-report.png.php\"><br>";

print "<pre>";
foreach($DATA as $TOOL => $STATISTICS)
{
	print "$TOOL:\n";
	foreach($STATISTICS as $YEARMONTH => $COUNT)
	{
		print "\t{$YEARMONTH} -> {$COUNT}\n";
	}
	print "\n";
}
print "</pre>\n";
//dumper($DATA);

print $HTML->footer();
?>

