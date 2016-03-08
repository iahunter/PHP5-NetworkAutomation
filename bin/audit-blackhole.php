#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(	// Search for all cisco network devices
				"category"	=> "blackhole",
				"type"		=> "sensor_%",
				);
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

$SENSOR_HOSTILES = array();

foreach ($RESULTS as $SENSORID)
{
	$SENSOR = Information::retrieve($SENSORID);
//	$SENSOR_HOSTILES = $SENSOR->get_hostile();
	$SENSOR_HOSTILES = array_merge( $SENSOR_HOSTILES , $SENSOR->get_hostile() );
//	$SENSOR_SUSPECTS = $SENSOR->get_suspect();
//	$SENSOR_BENIGN	 = $SENSOR->get_benign();

	/////////////////////////////////////////////////////////////////////////
	// Check this sensor for benign hosts cluttering up the results and clean them up!
/*	$BENIGNCOUNT = count($SENSOR_BENIGN);
	if ( $BENIGNCOUNT >= 40 )
	{
		print "Found {$BENIGNCOUNT} Benign hosts on sensor id {$SENSOR->data["id"]}, running cleanup...\n";
		$CHUNKS = array_chunk($SENSOR_BENIGN,50);
		$CHUNKCOUNT = count($CHUNKS);
		$i = 0;
		foreach($CHUNKS as $CHUNK)
		{
			$i++; print "\tDeleting benign chunk {$i} of {$CHUNKCOUNT}...\n";
			$SENSOR->ban_del_nova($CHUNK);    // Remove each chunk 50 IPs at a time to clean up the sensor
		}
	}
/**/
	unset($SENSOR);
}
$SENSOR = Information::retrieve(8632);	// Get the one and only Hon3ypot sensor...

//print "LIVE SENSOR\t"	. count($SENSOR_SUSPECTS)								. "\tTOTAL SUSPECTS\n";
//print "LIVE SENSOR\t"	. (count($SENSOR_SUSPECTS) - count($SENSOR_HOSTILES))	. "\tBENIGN SUSPECTS (CALCULATED)\n";
//print "LIVE SENSOR\t"	. count($SENSOR_BENIGN)									. "\tBENIGN SUSPECTS (ACTUAL)\n";
print "LIVE SENSOR\t"	. count($SENSOR_HOSTILES)								. "\tHOSTILE SUSPECTS\t!-SHOULD MATCH!\n";
//print "DB SENSOR\t"		. count($SENSOR->data["hostiles"])						. "\tHOSTILE SUSPECTS\t!-SHOULD MATCH!\n";

	$INACTIVE_HOSTILE_SEARCH = array(
										"category"      => "Blackhole",
										"type"          => "Hostile",
										"active"        => 0,
									);
	$INACTIVE_HOSTILE_RESULTS = Information::search($INACTIVE_HOSTILE_SEARCH);
//	print "DB HOSTILES\t"	. count($INACTIVE_HOSTILE_RESULTS)						. "\tINACTIVE HOSTILE RECORDS\n";

	$ACTIVE_HOSTILE_SEARCH = array(
										"category"      => "Blackhole",
										"type"          => "Hostile",
									);
	$ACTIVE_HOSTILE_RESULTS = Information::search($ACTIVE_HOSTILE_SEARCH);
	print "DB HOSTILES\t"	. count($ACTIVE_HOSTILE_RESULTS)						. "\tACTIVE HOSTILE RECORDS\t!-SHOULD MATCH!\n";
	$ACTIVE_HOSTILE_IPS = array();
	foreach ($ACTIVE_HOSTILE_RESULTS as $RECORD)
	{
		$HOSTILE = Information::retrieve($RECORD);
		array_push($ACTIVE_HOSTILE_IPS,$HOSTILE->data["ip"]);
		unset($HOSTILE);
	}
	print "DB HOSTILE IPs\t". count($ACTIVE_HOSTILE_IPS)						. "\tACTIVE HOSTILE RECORDS\t!-SHOULD MATCH!\n";

	$SEARCH = array(	// Search for all cisco network devices
					"category"  => "blackhole",
					"type"      => "link_router",
				);
	$RESULTS = Information::search($SEARCH);
	foreach ($RESULTS as $RESULT)
	{
		$LINK_ROUTER = Information::retrieve($RESULT);
		$ROUTER = Information::retrieve($LINK_ROUTER->data["link"]);
		$LINES_IN = preg_split( '/\r\n|\r|\n/', $ROUTER->data["run"] );
		$ROUTES_MANAGED = array();
		foreach($LINES_IN as $LINE)
		{
			if (preg_match("/ip route vrf V999:INTERNET (\S+) 255.255.255.255 Null0/",$LINE,$REG))
			{
				array_push($ROUTES_MANAGED,$REG[1]);
			}
		}
		print "BLACKHOLE\t"	. count($ROUTES_MANAGED)								. "\tACTIVE ROUTES\t\t!-SHOULD MATCH!\n";
	}

//	sort($SENSOR->data["hostiles"]);
	sort($SENSOR_HOSTILES);
	sort($ACTIVE_HOSTILE_IPS);

//	print "LIVE SENSOR HOSTILES:\n"	; \metaclassing\Utility::dumper($SENSOR_HOSTILES);
//	print "DB   SENSOR HOSTILES:\n"	; foreach($SENSOR->data["hostiles"]	as $IP) { print "{$IP}\n"; }
//	print "DB   HOSTILE OBJECTS:\n"	; foreach($ACTIVE_HOSTILE_IPS		as $IP) { print "{$IP}\n"; }
//	print "BLACKHOLE ROUTES:\n"		; \metaclassing\Utility::dumper($ROUTES_MANAGED);

// This will find hostiles in the sensor that ARE in the DB but NOT active and re-activate them.

	print "Hostiles in hostile list but NOT pulled from database:\n";
//	$ASDF = array_diff($SENSOR->data["hostiles"],$ACTIVE_HOSTILE_IPS); \metaclassing\Utility::dumper($ASDF);
	$ASDF = array_diff($SENSOR_HOSTILES,$ACTIVE_HOSTILE_IPS); \metaclassing\Utility::dumper($ASDF);

//	$SENSOR->ban_del($ASDF);
//	die("fix it yourself.\n");

//	$SENSOR->ban_add($ASDF);	// Fix it...
/*	$ADDME = array();
	foreach($ASDF as $IP)
	{
		$SEARCH = array(
							"type" => "Hostile",
							"stringfield1" => $IP,
							"active"        => "%",
						);
		$RESULTS = Information::search($SEARCH);
		if ( count($RESULTS) )
		{
			foreach($RESULTS as $ID)
			{
				$INFOBJ = Information::retrieve($ID);
				print "Setting INFOBJ ID {$ID} IP {$IP} to ACTIVE\n";
				$INFOBJ->set_active(1);
				$INFOBJ->update();
				unset($INFOBJ);
			}
		}else{
			print "Did not find a hostile record for {$IP} to activate! Creating one...\n";
		}
	}
	if ( count($ADDME) ) { $SENSOR->ban_add($ADDME); }
/**/

/*
	print "Hostiles in the live sensor but NOT the object hostile list:\n";
	$ASDF	= array_diff($SENSOR_HOSTILES,$SENSOR->data["hostiles"]);	\metaclassing\Utility::dumper($ASDF);
/*
	print "Hostiles in the object hostile list but NOT the live sensor:\n";
	$ASDF	= array_diff($SENSOR->data["hostiles"],$SENSOR_HOSTILES);
/*
	print "Hostile IPs in LIVE sensor but NOT the database (hostile records):\n";
	$ASDF	= array_diff($SENSOR_HOSTILES,$ACTIVE_HOSTILE_IPS); \metaclassing\Utility::dumper($ASDF);
/*
	print "Hostile IPs in database but NOT the live sensor:\n";
	$ASDF	= array_diff($ACTIVE_HOSTILE_IPS,$SENSOR_HOSTILES); \metaclassing\Utility::dumper($ASDF);
/*
	print "Hostiles in the blackhole router but NOT the hostile list:\n";
	$ASDF	= array_diff($ROUTES_MANAGED,$SENSOR_HOSTILES);	\metaclassing\Utility::dumper($ASDF);
	$SENSOR->ban_del($ASDF);
/*
	print "Hostiles in the hostile list but NOT the blackhole router:\n";
	$ASDF	= array_diff($SENSOR_HOSTILES,$ROUTES_MANAGED);	\metaclassing\Utility::dumper($ASDF);

	// This is old code to remediate the data after a bug that failed to delete hostiles from nova cli when deactivated
/*
	$INACTIVE_HOSTILE_SEARCH = array(
										"category"      => "Blackhole",
										"type"          => "Hostile",
										"active"        => 0,
									);
	$INACTIVE_HOSTILE_RESULTS = Information::search($INACTIVE_HOSTILE_SEARCH);
	foreach($INACTIVE_HOSTILE_RESULTS as $INACTIVE_HOSTILE_RESULT)
	{
		$INACTIVE_HOSTILE = Information::retrieve($INACTIVE_HOSTILE_RESULT);
		print "Found inactive hostile {$INACTIVE_HOSTILE->data["ip"]}... ";
		$SENSOR->ban_del(array($INACTIVE_HOSTILE->data["ip"]));
		print "Deleted!\n";
		unset($INACTIVE_HOSTILE);
	}
/**/

	// This is for printing out the bantime for all the hostiles
/*
	$SEARCH = array(    // Search active hostile information
					"category"      => "Blackhole",
					"type"          => "Hostile",
					);

	$RESULTS = Information::search($SEARCH);            // Search for all ACTIVE hostile objects
	if ( count($RESULTS) )                              // If we find some, check them
	{
		print "FOUND " . count($RESULTS) . " HOSTILE RECORDS:\n";
		print "ID\tIP\t\tBANTIME\tREMAINING\n";
		foreach($RESULTS as $RESULT)                    // Check every hostile object
		{
			$HOSTILE = Information::retrieve($RESULT);  // Get our hostile object
			print "{$HOSTILE->data["id"]}\t{$HOSTILE->data["ip"]}\t{$HOSTILE->data["bantime"]}\t" . $HOSTILE->bantime_remaining() . "\n";
			unset($HOSTILE);
		}
	}
/**/


