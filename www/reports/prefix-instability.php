<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports");
$HTML->breadcrumb("BGP Prefix Instability Report");
print $HTML->header("BGP Prefix Instability Report");

$QUERY = <<<END
	SELECT * FROM bgpmon
	WHERE bgpxml LIKE '%UPDATE%'
	ORDER BY id
	DESC
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

$WIDTH = array();	$i = 1;
$WIDTH[$i++]	= 250;
$WIDTH[$i++]	= 80;
$WIDTH[0]		= array_sum($WIDTH);

$i = 0;
print <<<END
	<table class="report" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<thead>
		<caption>Most Active BGP Prefixes ({$RECORDCOUNT} BGP Topology Changes)</caption>
		<tr>
			<th class="report">Prefix</th>
			<th class="report">Changes</th>
		</tr>
	</thead>
END;

$PREFIXES = array();
$i=0;
foreach($RESULTS as $RESULT)
{
	$ID				= $RESULT['id'		];
	$XML			= $RESULT['bgpxml'	];
	try {
		$BGPXML			= new SimpleXMLElement($XML);
	} catch (Exception $E) {
		$MESSAGE = "BGP Message ID {$ID} Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE);
	}
	$BGPUPDATE		= $BGPXML->children("bgp",true);

	foreach($BGPUPDATE->UPDATE->WITHDRAW as $PREFIX)
	{
		$PREFIXES[(string)$PREFIX]++;
	}
	foreach($BGPUPDATE->UPDATE->NLRI as $PREFIX)
	{
		$PREFIXES[(string)$PREFIX]++;
	}
}

arsort($PREFIXES);

$i = 0;
$TOTAL = 0;
foreach($PREFIXES as $PREFIX => $COUNT)
{
	$rowclass = "row".(($i++ % 2)+1);
	print <<<END
		<tr class="{$rowclass}">
			<td class="report">{$PREFIX}</td>
			<td class="report">{$COUNT}</td>
		</tr>
END;
	$TOTAL += $COUNT;
}

$rowclass = "row".(($i++ % 2)+1);
print <<<END
	<tr class="{$rowclass}">
		<td class="report"><b>Total Updates</b></td>
		<td class="report"><b>{$TOTAL}</b></td>
	</tr>
</table>
END;
print $HTML->footer();
?>
