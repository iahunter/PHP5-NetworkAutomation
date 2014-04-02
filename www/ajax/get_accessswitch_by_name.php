<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

if ( isset($_REQUEST['debug'])	)	{ $DEBUG = $_REQUEST['debug']; } else { $DEBUG = ""; }
if ( isset($_REQUEST['term'])	)	{ $DEVICE = $_REQUEST['term']; } else { exit; }

$i=0;
//$RESULT = mysql_query("select switch_name,switch_id,switch_ip from switch where switch_name like '%$DEVICE%' and switch_ip is not null order by switch_name limit 30",$SQLCONNECTION);
/*$RESULT = mysql_query("select distinct device.prompt,device.id,device.ip from device where prompt like '%$DEVICE%' and (
 prompt like '%swc%' or
 prompt like '%swi%' or
 prompt like '%swa%' or
 prompt like '%swp%'
) and prompt is not null order by prompt limit 30",$SQLCONNECTION);
/**/

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
	dumper($_REQUEST);
	dumper($RETURN);
	dumper($JSON);
}else{
	print "$JSON";
}

john_flush();

?>
