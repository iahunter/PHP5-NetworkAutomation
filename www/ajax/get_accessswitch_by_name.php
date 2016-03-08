<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

if ( isset($_REQUEST['debug'])	)	{ $DEBUG = $_REQUEST['debug']; } else { $DEBUG = ""; }
if ( isset($_REQUEST['term'])	)	{ $DEVICE = $_REQUEST['term']; } else { exit; }

$i=0;

$DEVICE = "%".$DEVICE."%";

$QUERY = <<<END
	SELECT DISTINCT id,stringfield0,stringfield1 FROM information
	WHERE stringfield0 LIKE :DEVICE
	AND (
		stringfield0 LIKE '%swc%' OR
		stringfield0 LIKE '%swd%' OR
		stringfield0 LIKE '%swa%' OR
		stringfield0 LIKE '%swp%' OR
		stringfield0 LIKE '%swi%'
		)
	AND stringfield0 IS NOT NULL
	AND (
		stringfield2 LIKE '%ssh%' OR
		stringfield2 LIKE '%telnet%'
		)
	AND active = 1
	ORDER BY stringfield0
	LIMIT 30
END;
global $DB;
$DB->query($QUERY);
try {
	$DB->bind("DEVICE",$DEVICE);
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

$RETURN = array();
foreach($RESULTS as $RESULT)
{
	$ID		= $RESULT['id'];
	$PROMPT	= $RESULT['stringfield0'];
	$IP		= $RESULT['stringfield1'];
	$IMAGE	= "<img src=\"/ajax/probe.png.php?id={$ID}\" height=\"13\" width=\"13\">";

	$ROW = array();
	$ROW['id'] = $ID;
	$ROW['label'] = $PROMPT;
	$ROW['label'] = "{$PROMPT}{$IMAGE}";
	$ROW['label'] = "{$IMAGE}{$PROMPT}";
	$ROW['value'] = $PROMPT;

	array_push($RETURN,$ROW);
}

$JSON = json_encode($RETURN);

if ($DEBUG)
{
	\metaclassing\Utility::dumper($_REQUEST);
	\metaclassing\Utility::dumper($RETURN);
	\metaclassing\Utility::dumper($JSON);
}else{
	print "$JSON";
}

\metaclassing\Utility::flush();

?>
