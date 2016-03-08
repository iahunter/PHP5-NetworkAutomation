<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// Get current blackholed hostile guys
$ACTIVE_HOSTILE_SEARCH = array(
								"category"      => "Blackhole",
								"type"          => "Hostile",
							);
$ACTIVE_HOSTILE_RESULTS = Information::search($ACTIVE_HOSTILE_SEARCH);
$HOSTILECOUNT = count($ACTIVE_HOSTILE_RESULTS);

// Search for our hon3ypot sensors (not nova or manual)
$SEARCH = array(	// Search existing honeypot sensors
		"category"      => "Blackhole",
		"type"          => "Sensor_Hon3yPot",
	);
$SENSORIDS = Information::search($SEARCH);

$SUSPECTS = array();
foreach ($SENSORIDS as $SENSORID)
{
	$SENSOR = Information::retrieve($SENSORID);
	$SUSPECTS = array_merge( $SUSPECTS , $SENSOR->get_hostile_details(120) );
}

$COUNT = count($SUSPECTS);

$i = 0;

require_once "reliabilitygraph.class.php";
$GRAPH = new ReliabilityGraph();
$NODE = $GRAPH->Add_Node("Honeypot");
$GRAPH->graph->setAttribute("graphviz.graph.label"		,"Blackholed {$HOSTILECOUNT} Hostiles");
$GRAPH->graph->setAttribute("graphviz.graph.labelloc"	,"t");
$GRAPH->graph->setAttribute("graphviz.graph.labeljust"	,"l");
$GRAPH->graph->setAttribute("graphviz.graph.size"		,"10");
$GRAPH->graph->setAttribute("graphviz.graph.ratio"		,"fill");
$GRAPH->graph->setAttribute("graphviz.graph.layout"		,"twopi");
$GRAPH->graph->setAttribute("graphviz.node.fontsize"	,"10");

$COUNTRIES = 2;
foreach($SUSPECTS as $SUSPECT)
{
	$DATE	= date("m/d/y H:i:s", strtotime($SUSPECT["date"]));
	$GEOIP = geoip_record_by_name( $SUSPECT["source"] );

	if ( $SUSPECT["source"] && $GEOIP["country_name"] )
	{
		$NODE = $GRAPH->Get_Node( $GEOIP["country_name"] );
		if ( !$NODE )
		{
			$COUNTRIES++;
			$GRAPH->Add_Nodes( array($GEOIP["country_name"]) );
		    $EDGE = $GRAPH->Add_Link($GEOIP["country_name"], "Honeypot", 1 , 1 );
			$EDGE->setAttribute("graphviz.fontsize" ,9);
			$EDGE->setAttribute("graphviz.color"	,"blue");
		}else{
			$EDGE = $GRAPH->Find_Edge($GEOIP["country_name"],"Honeypot");
			if ( $EDGE )
			{
				$EDGE->setAttribute("graphviz.label" , $EDGE->getAttribute("graphviz.label") + 1 );
				if ( $EDGE->getAttribute("graphviz.label") > 3 )
				{
					$NODE->setAttribute("graphviz.fillcolor", "red");
					$NODE->setAttribute("graphviz.style", "filled");
					$EDGE->setAttribute("graphviz.color", "red");
				}
			}
		}
		$NODE = $GRAPH->Get_Node( $SUSPECT["source"] );
		if ( !$NODE )
		{
			$NODE = $GRAPH->Add_Node( $SUSPECT["source"] );
			$EDGE = $GRAPH->Add_Link($SUSPECT["source"], $GEOIP["country_name"], 1 );
			$EDGE->setAttribute("graphviz.color"	,"blue");
		}else{
			$EDGE = $GRAPH->Find_Edge($SUSPECT["source"], $GEOIP["country_name"]);
			if ( $EDGE )
			{
				$EDGE->setAttribute("graphviz.label" , $EDGE->getAttribute("graphviz.label") + 1 );
				if ( $EDGE->getAttribute("graphviz.label") > 3 )
				{
					$NODE->setAttribute("graphviz.color", "red");
					$EDGE->setAttribute("graphviz.color", "red");
				}
			}
			// Check to see if we blackholed the assholes...
			$SEARCH = array(    // Search existing honeypot sensors
					"category"		=> "Blackhole",
					"type"			=> "Hostile",
					"stringfield1"	=> $SUSPECT["source"],
				);
			$SENSORIDS = Information::search($SEARCH);
			$NODE->setAttribute("graphviz.fillcolor", "purple");
			$NODE->setAttribute("graphviz.style"	, "filled");
			$EDGE->setAttribute("graphviz.color"	, "purple");
		}
	}
}
$X = $COUNTRIES; $Y = $COUNTRIES / 2;
//$GRAPH->graph->setAttribute("graphviz.graph.size"	,$COUNTRIES);
$GRAPH->graph->setAttribute("graphviz.graph.size"	,"{$X},{$Y}");

$GRAPHVIZ = new Graphp\GraphViz\GraphViz();
$PNG = $GRAPHVIZ->createImageData($GRAPH->graph);

header("Content-Type: image/png");
print $PNG;

?>
