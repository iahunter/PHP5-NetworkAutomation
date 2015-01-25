<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
require_once "information/bgp/asn.class.php";

if (isset($_GET['id']))
{
	$ID = $_GET['id'];
	$DETAILQUERY = "AND id = :ID";
	$HTML->breadcrumb("Home","/");
	$HTML->breadcrumb("BGP Monitor","/monitoring/bgp.php");
	$HTML->breadcrumb("BGP Update");
	print $HTML->header("BGP Update Details");
}elseif (isset($_GET['search']))
{
	$SEARCH = $_GET['search'];
	$DETAILQUERY = "AND bgpxml like :SEARCH";
	$HTML->breadcrumb("Home","/");
	$HTML->breadcrumb("BGP Monitor","/monitoring/bgp.php");
	$HTML->breadcrumb("BGP Search");
	print $HTML->header("BGP Update Search");
}else{
	$DETAILQUERY = "AND id = :ID";
	$DETAILQUERY = "";
}

$QUERY = <<<END
	SELECT * FROM bgpmon
	WHERE bgpxml LIKE '%UPDATE%'
	{$DETAILQUERY}
	ORDER BY id
	DESC LIMIT 100
END;

global $DB;
$DB->query($QUERY);
try {
	if (isset($ID))		{ $DB->bind("ID"	,$ID			); }
	if (isset($SEARCH))	{ $DB->bind("SEARCH","%{$SEARCH}%"	); }
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

$RECORDCOUNT = count($RESULTS);

if (isset($ID) || isset($SEARCH))
{ print <<<END
	<form action="{$HTML->thispage}" method="get">Search BGP Message Contents: <input type="text" name="search"> <input type="submit" value="Search!"></form><br>
END;
}

$WIDTH = array();	$i = 1;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 70;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 500;
$WIDTH[$i++]	= 50;
$WIDTH[0]		= array_sum($WIDTH);


$i = 0;
print <<<END
	<table class="report" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<thead>
		<caption>BGP Update Messages ({$RECORDCOUNT} Updates - 100 Max)</caption>
		<tr>
			<th class="report">Date</th>
			<th class="report">Type</th>
			<th class="report">Prefix</th>
			<th class="report">AS Path</th>
			<th class="report">Details</th>
		</tr>
	</thead>
END;

$i = 0;
$BGPINFO = BGP_ASN::array_bgp_by_asn();
//dumper($BGPINFO);

//libxml_use_internal_errors(true);

foreach($RESULTS as $RESULT)
{
	$ID				= $RESULT['id'		];
	$XML			= $RESULT['bgpxml'	];
	try {
		$BGPXML			= @new SimpleXMLElement($XML);
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()} BGPMON RECORD ID {$ID}";
		$DB->log($MESSAGE,2);
		continue;
	}
//	$BGPTIME		= date("Y-m-d g:i a", (int) $BGPXML->OBSERVED_TIME->TIMESTAMP);
	date_default_timezone_set('America/Chicago');
	$BGPTIME		= new DateTime(gmdate("@".$BGPXML->OBSERVED_TIME->TIMESTAMP));	// The timestamp is in UTC
	$BGPTIME->setTimezone(new DateTimeZone('America/Chicago'));						// Conver it to central time
	$BGPTIME		= $BGPTIME->format('Y-m-d g:i a');
	$BGPUPDATE		= $BGPXML->children("bgp",true);

	$PREFIXES = array();
	foreach($BGPUPDATE->UPDATE->WITHDRAW as $PREFIX)
	{
		$TYPE = "WITHDRAW";
		array_push($PREFIXES,(string)$PREFIX);
	}
	foreach($BGPUPDATE->UPDATE->NLRI as $PREFIX)
	{
		$TYPE = "UPDATE";
		array_push($PREFIXES,(string)$PREFIX);
	}

	if ($TYPE == "UPDATE")
	{
		$ASPATH		= array();
		$ASPATH_OUTPUT = "";
		if (isset($BGPUPDATE->UPDATE->AS_PATH->AS_SEQUENCE->ASN2)) { $ASNS = $BGPUPDATE->UPDATE->AS_PATH->AS_SEQUENCE->ASN2; }else{ $ASNS = array(); }
		foreach($ASNS as $ASN)
		{
			$ASN = (string)$ASN;	// God only knows wtf datatype this xml object spits out so always force typecast it before using!
			array_push($ASPATH	,$ASN);
			if (isset($BGPINFO[$ASN]))
			{
				$ASPATH_OUTPUT .= <<<END
					<a href="/information/information-view.php?id={$BGPINFO[$ASN]['id']}" title="{$BGPINFO[$ASN]['stringfield2']}">$ASN</a> 
END;
			}else{
				$ASPATH_OUTPUT .= "$ASN ";
			}
		}
	}else{
		$ASPATH_OUTPUT = "-";
	}
	foreach($PREFIXES as $PREFIX)
	{
		$ROWCLASS = "row".(($i++ % 2)+1);
		print <<<END
			<tr class="{$ROWCLASS}">
				<td class="report">{$BGPTIME}		</td>
				<td class="report">{$TYPE}			</td>
				<td class="report">{$PREFIX}		</td>
				<td class="report">{$ASPATH_OUTPUT}	</td>
				<td class="report"><a href="{$HTML->thispage}?id={$ID}">Detail</a></td>
			</tr>
END;
	}
	if (isset($_GET['id']))
	{
		$COLUMNCOUNT = count($WIDTH) - 1;
		$ROWCLASS = "row".(($i++ % 2)+1);
		$BGPSTRING = dumper_to_string($BGPUPDATE);
		print <<<END
		<tr class="{$ROWCLASS}">
			<td class="report" colspan="{$COLUMNCOUNT}">{$BGPSTRING}</td>
		</tr>
END;
	}
}
print "</table>";
$size = memory_get_usage(true);
$unit=array('b','kb','mb','gb','tb','pb');
$MEMORYUSED = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
print "<br>Loaded in " . $HTML->timer_diff() . " seconds, " . count($DB->QUERIES) . " SQL queries, " . $MEMORYUSED . " of memory";
if (isset($_GET['id']) || isset($SEARCH)) { print $HTML->footer(); }

?>
