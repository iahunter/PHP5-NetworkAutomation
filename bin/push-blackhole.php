#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

////////////////////////////////////////////////////////////////////////////////
// Get routes that were provisioned by hostile objects
$ROUTES_PROVISIONED = array();
$SEARCH = array(	// Search for hostile attackers
				"category"	=> "blackhole",
				"type"		=> "hostile",
				);
$RESULTS = Information::search($SEARCH);
foreach ($RESULTS as $RESULT)
{
	$HOSTILE = Information::retrieve($RESULT);
	if ($HOSTILE->bantime_remaining() >= 0)
	{
		array_push( $ROUTES_PROVISIONED , trim( $HOSTILE->route_add() ) );
	}else{
		print "WARNING: HOSTILE {$HOSTILE->data["id"]} IP {$HOSTILE->data["ip"]} ACTIVE BUT BANTIME " . $HOSTILE->bantime_remaining() . "!\n";
	}
	unset($HOSTILE);
}

////////////////////////////////////////////////////////////////////////////////
// Get routes that are on managed blackhole routers
$SEARCH = array(	// Search for all cisco network devices
				"category"	=> "blackhole",
				"type"		=> "link_router",
				);
$RESULTS = Information::search($SEARCH);
foreach ($RESULTS as $RESULT)
{
	$OUTPUT = "";
	$LINK_ROUTER = Information::retrieve($RESULT);
	$ROUTER = Information::retrieve($LINK_ROUTER->data["link"]);
/*	$OUTPUT .= "Scanning router for latest configuration...\n";
	$ROUTER->scan();
	$OUTPUT .= "Scan complete!\n";
/**/
	$LINES_IN = preg_split( '/\r\n|\r|\n/', $ROUTER->data["run"] );
	$ROUTES_MANAGED = array();
	foreach($LINES_IN as $LINE)
	{
		if (preg_match("/ip route vrf V999:INTERNET \S+ 255.255.255.255 Null0/",$LINE,$REG))
		{
			array_push($ROUTES_MANAGED,$LINE);
		}
	}
	//\metaclassing\Utility::dumper($ROUTES_PROVISIONED);
	$ADD = array_diff($ROUTES_PROVISIONED	,$ROUTES_MANAGED		);
	$DEL = array_diff($ROUTES_MANAGED		,$ROUTES_PROVISIONED	);
	foreach ($DEL as $KEY => $VALUE)
	{ $DEL[$KEY] = "no " . $VALUE; }

	$OUTPUT .= "BLACKHOLE ROUTER ID {$ROUTER->data["id"]} NAME {$ROUTER->data["name"]}\n";
	$OUTPUT .= "PROVISIONED ". count($ROUTES_PROVISIONED	) . " ROUTES:\n";
//	$OUTPUT .= \metaclassing\Utility::dumperToString($ROUTES_PROVISIONED);
	$OUTPUT .= "MANAGED "	. count($ROUTES_MANAGED		) . " ROUTES:\n";
//	$OUTPUT .= \metaclassing\Utility::dumperToString($ROUTES_MANAGED);
	$OUTPUT .= "ADD ROUTES:\n";
	$OUTPUT .= \metaclassing\Utility::dumperToString($ADD);
	$OUTPUT .= "REMOVE ROUTES:\n";
	$OUTPUT .= \metaclassing\Utility::dumperToString($DEL);

	// Created a single push variable for both add and del commands!
	$PUSH = array_merge($ADD,$DEL);
	$OUTPUT .= "PUSHING:\n";
	$OUTPUT .= \metaclassing\Utility::dumperToString($PUSH);
	// This was inefficient and slow as it resulted in 2 separate pushes!
/*	if ( count($ADD) ) { $ROUTER->push($ADD); }
	if ( count($DEL) ) { $ROUTER->push($DEL); }/**/
	if ( count($PUSH) ) { $ROUTER->push($PUSH); }
/*	$OUTPUT .= "AUDIT COMPLETE, rescanning config...\n";
	// Rescanning the device ASYNCHRONOUSLY is now part of the push member function!
	$ROUTER->scan();
	$OUTPUT .= "RESCAN COMPLETE!\n";/**/
	if ( count($PUSH) ) { print "{$OUTPUT}\n"; }
}
