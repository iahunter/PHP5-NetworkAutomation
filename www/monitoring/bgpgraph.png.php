<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEEDS = array(
				"khonemdcrrr01",
				"khonestdrrr01",
//				"",
				);


$ASPATHLINES = array();
foreach ($SEEDS as $SEED)
{
	$SEARCH = array(	// Search for all cisco network devices
				"category"		=> "management",
				"type"			=> "device_network_cisco%",
				"stringfield0"	=> "{$SEED}",
				);

	$DEVICEID = reset( Information::search($SEARCH) );
	if ( !$DEVICEID ) { die("ERROR: COULD NOT FIND SEED ROUTER {$SEED}!\n"); }

	$COMMAND = new command($DEVICEID);
	$CLI = $COMMAND->getcli();
	if (!$CLI->connected) { die("ERROR: COULD NOT CONNECT TO SEED ROUTER {$SEED}!\n"); }

	$PROMPT = $CLI->prompt;
	if (!$PROMPT) { die("ERROR: COULD NOT GET SEED DEVICE PROMPT {$SEED}!\n"); }

	$CLI->exec("terminal length 0");
//	$PATHS = $CLI->exec("show ip bgp vpnv4 all paths | E Address");
	$PATHS = $CLI->exec("show ip bgp vpnv4 all");

	if (!$PATHS) { die("ERROR: COULD NOT GET SEED BGP PATH DATA {$SEED}!\n"); }

	$PATHARRAY = preg_split( "/\r\n|\r|\n/", $PATHS );
//	print "GOT " . count($PATHARRAY) . " PATHS FROM {$SEED}<br>\n";
	foreach ($PATHARRAY as $LINE)
	{
		$LENGTH = strlen($LINE);
		if ( $LENGTH > 61 )
		{
			$LINE = substr( $LINE, 61, $LENGTH );
			array_push($ASPATHLINES,$LINE);
		}
	}
}

// Sort our lines to deduplicate them...
sort($ASPATHLINES);

$ASPATHSET = array();
$LASTPATH = "";
foreach ($ASPATHLINES as $ASPATH)
{
	if ($ASPATH == $LASTPATH) { continue; }	// Deduplicate results...
	$LASTPATH = $ASPATH;
	$ASHOPS = explode(" ",$ASPATH);
	$TEMP = array();
	$LASTHOP = "";
	foreach ($ASHOPS as $HOP)
	{
		if ( $HOP == $LASTHOP ) { continue; } $LASTHOP = $HOP; // deduplication
		if ( !is_numeric($HOP) || $HOP < 1 || $HOP > 65535  ) { break; }
		if ( $HOP == 3 || $HOP == 100) { continue; }
		array_push($TEMP,$HOP);
	}
	if ( count($TEMP) ) { array_push($ASPATHSET,$TEMP); }
}

// This is a list of our ASN to site name mapping...
require_once "information/bgp/asn.class.php";
$BGPINFO = BGP_ASN::array_bgp_by_asn();

require_once "reliabilitygraph.class.php";
$GRAPH = new ReliabilityGraph();
$SEED = 65101;
$NODE = $GRAPH->Add_Node( $SEED );
if ( $NODE && isset($BGPINFO[$SEED]) )
{
	$NODE->setAttribute("graphviz.label"	, $SEED . "\n" . $BGPINFO[$SEED]["stringfield2"]);
	$NODE->setAttribute("graphviz.shape"	, "box");
}
//$GRAPH->graph->setAttribute("graphviz.graph.layout"	,"neato");
$GRAPH->graph->setAttribute("graphviz.graph.layout"	,"twopi");
$GRAPH->graph->setAttribute("graphviz.graph.ranksep","1.25");

$GRAPH->graph->setAttribute("graphviz.graph.ratio"  ,"fill");
$GRAPH->graph->setAttribute("graphviz.node.fontsize","8");

$EDGECOLOR = array(
	209		=> "green",	// Centurylink
	13979	=> "blue",	// ATT
	8034	=> "blue",
	21326	=> "blue",
	65101	=> "purple",// KPN
	65102	=> "purple",
	55028	=> "purple",
	64998	=> "orange",// FlexVPN
	64999	=> "orange",
	852		=> "red",	// TelusUCK
	1691	=> "red",
	6500	=> "red",
	65000	=> "red",
	65001	=> "red",
	65002	=> "red",
	65003	=> "red",
	65004	=> "red",
	"*"		=> "black",// Everybody else
);

foreach ($ASPATHSET as $ASPATH)
{
	$NETWORKS++;
	$LASTASN = $SEED;
	foreach ($ASPATH as $ASN)
	{
		if ( is_numeric($ASN) )
		{
			if ($ASN == $LASTASN) { continue; }	// Skip duplicated ASNs in a single path...
			$NODE = $GRAPH->Get_Node( $ASN );
			if ( !$NODE )
			{
				$NODE = $GRAPH->Add_Node( $ASN );
				$NODE->setAttribute("graphviz.shape"	, "box");
				if (isset($BGPINFO[$ASN]))
				{
					$NODE->setAttribute("graphviz.label"	, $ASN . "\n" . $BGPINFO[$ASN]["stringfield2"]);
				}
				$EDGE = $GRAPH->Add_Link($ASN, $LASTASN, 1		);
				$EDGE->setAttribute("graphviz.fontsize" ,9		);
				$EDGE->setAttribute("graphviz.color"    ,$EDGECOLOR["*"] );
				if ( isset($EDGECOLOR[$ASN		]))	{ $EDGE->setAttribute("graphviz.color",$EDGECOLOR[$ASN		]); }
				if ( isset($EDGECOLOR[$LASTASN	])) { $EDGE->setAttribute("graphviz.color",$EDGECOLOR[$LASTASN	]); }
				$EDGE->setAttribute("graphviz.label"	, ""	);
			}else{
				$EDGE1 = $GRAPH->Find_Edge($ASN,$LASTASN);
				$EDGE2 = $GRAPH->Find_Edge($LASTASN,$ASN);
				if ( !$EDGE1 && !$EDGE2 )
				{
					$EDGE = $GRAPH->Add_Link($ASN, $LASTASN, 1		);
					$EDGE->setAttribute("graphviz.fontsize" ,9		);
					$EDGE->setAttribute("graphviz.color"    ,$EDGECOLOR["*"] );
					if ( isset($EDGECOLOR[$ASN		]))	{ $EDGE->setAttribute("graphviz.color",$EDGECOLOR[$ASN		]); }
					if ( isset($EDGECOLOR[$LASTASN	])) { $EDGE->setAttribute("graphviz.color",$EDGECOLOR[$LASTASN	]); }
					$EDGE->setAttribute("graphviz.label"	,""		);
				}
//				if ( $EDGE1 ) { $EDGE1->setAttribute("graphviz.label" , $EDGE1->getAttribute("graphviz.label") + 1 ); }
//				if ( $EDGE2 ) { $EDGE2->setAttribute("graphviz.label" , $EDGE2->getAttribute("graphviz.label") + 1 ); }
			}
			$LASTASN = $ASN;
		}
	}
}
$NETWORKS = 50;
$X = $NETWORKS * 2; $Y = $NETWORKS;
$GRAPH->graph->setAttribute("graphviz.graph.size"   ,"{$X},{$Y}");
$GRAPHVIZ = new Graphp\GraphViz\GraphViz();
$PNG = $GRAPHVIZ->createImageData($GRAPH->graph);

header("Content-Type: image/png");
print $PNG;
