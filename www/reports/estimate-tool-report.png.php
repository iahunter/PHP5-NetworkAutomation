<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$QUERY = <<<END
	SELECT id,date,tool FROM log
	WHERE description LIKE '%Category:Company%'
	AND tool LIKE '%information-add%'
	OR  tool LIKE '%information-edit%'
	ORDER BY date
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
	$MONTH = str_pad($DATE['month'], 2, "0", STR_PAD_LEFT); // Always format our month as 07 instead of just 7.
	$DAY =  str_pad($DATE['day'], 2, "0", STR_PAD_LEFT);
	$YEARMONTH = $YEAR . '-' . $MONTH;
	$YEARMONTHDAY = $YEAR . '-' . $MONTH . '-' . $DAY;
	if (!isset($DATA[$RESULT['tool']][$YEARMONTH])) { $DATA[$RESULT['tool']][$YEARMONTH] = 0; }
	$DATA[$RESULT['tool']][$YEARMONTH]++;
//	if (!isset($DATA[$RESULT['tool']][$YEARMONTHDAY])) { $DATA[$RESULT['tool']][$YEARMONTHDAY] = 0; }
//	$DATA[$RESULT['tool']][$YEARMONTHDAY]++;
}

// Graphing inclusions
include("pChart/pData.class");
include("pChart/pChart.class");

// Dataset definition
$DataSet = new pData;

$AXIS = array();
foreach($DATA as $TOOL => $STATISTICS)
{
	$TEMP = array();
	foreach($STATISTICS as $YEARMONTH => $COUNT)
	{
		array_push($TEMP,$COUNT);
	}
	$DataSet->AddPoint($TEMP,$TOOL);
	$DataSet->AddSerie($TOOL);
	$DataSet->SetSerieName($TOOL,$TOOL);
	$AXIS = array_keys($STATISTICS);
}
$DataSet->AddPoint($AXIS,"Axis");
$DataSet->SetAbsciseLabelSerie("Axis");
$DataSet->SetYAxisName("Uses");

// Initialise the graph
$Test = new pChart(900,500);

$FONT = BASEDIR . "/font/tahoma.ttf";
$Test->setFontProperties($FONT,13); // Font size for the X and Y axis count/date numbers
$Test->setGraphArea(50,10,860,440);
$Test->drawGraphArea(255,255,255,TRUE);
//$Test->setLineStyle(1,0);
$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,45,2); // 45 is text rotation angle for date!
$Test->drawGrid(5,TRUE,230,230,230,50); // 5 is dotted line width on background 230 is grey and 50 is alternating gradient transparency

// Draw the line graph
$Test->setLineStyle(2,0);
$Test->setColorPalette(0,	0	,	0	,	255	);	// Make our first line blue
$Test->setColorPalette(1,	255	,	120	,	0	);	// Make our second line orange
$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),5,2,255,255,255); // 5 is dot size on the line, 3 is dot background

// Finish the graph
$Test->setFontProperties($FONT,14); // Font size of the axis labels
$Test->drawLegend(60,20,$DataSet->GetDataDescription(),255,255,255);
$Test->stroke();
?>
