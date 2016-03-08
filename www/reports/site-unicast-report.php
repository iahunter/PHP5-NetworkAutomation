<?php
define("NO_AUTHENTICATION",1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

$MESSAGE = "SITE SUBNET REPORT";
$DB->log($MESSAGE);

$SEARCH = array(
				"category"      => "Management",
				"type"          => "device_network_%",
				);
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

$INTERNET = array();
foreach ($RESULTS as $DEVICEID)
{
	$INFOBJECT = Information::retrieve($DEVICEID);
	$RUNLINES = explode("ip addr",$INFOBJECT->data["run"]);
    foreach ($RUNLINES as $LINE)
    {
		if(preg_match('/^ess\s+(\d+.\d+.\d+.\d+)\s+(255.\d+\.\d+\.\d+).*/',$LINE,$REG))
		{
			$ip_calc = new Net_IPv4();
			$ip_calc->ip = $REG[1];
			$ip_calc->netmask = $REG[2];
			$error = $ip_calc->calculate();
			if ( is_object($error) ) { print "Input Error: $error->getMessage()\n"; }
			$NETWORK = $ip_calc->network."/".$ip_calc->bitmask;

			// Super hacky testing
			preg_match('/(\d+).(\d+).(\d+).*/',$NETWORK,$REG); // \metaclassing\Utility::dumper($reg);
			$OCTET1 = intval($REG[1]);
			$OCTET2 = intval($REG[2]);
			$OCTET3 = intval($REG[3]);

			if ( !isset($INTERNET[$OCTET1])						) { $INTERNET[$OCTET1]						= array(); }
			if ( !isset($INTERNET[$OCTET1][$OCTET2])			) { $INTERNET[$OCTET1][$OCTET2]				= array(); }
			if ( !isset($INTERNET[$OCTET1][$OCTET2][$OCTET3])	) { $INTERNET[$OCTET1][$OCTET2][$OCTET3]	= array(); }

			array_push($INTERNET[$OCTET1][$OCTET2][$OCTET3],$NETWORK);

			ksort($INTERNET[$OCTET1][$OCTET2]);
			ksort($INTERNET[$OCTET1]);
			ksort($INTERNET);
/**/
		}
    }
	unset($INFOBJECT);
}

header("Content-Type: text/plain");
unset($INTERNET[1]);		// Bogus space
unset($INTERNET[10]);		// RFC1918 space
unset($INTERNET[192][168]);
unset($INTERNET[172][16]);
unset($INTERNET[172][17]);
unset($INTERNET[172][18]);
unset($INTERNET[172][19]);
unset($INTERNET[172][20]);
unset($INTERNET[172][21]);
unset($INTERNET[172][22]);
unset($INTERNET[172][23]);
unset($INTERNET[172][24]);
unset($INTERNET[172][25]);
unset($INTERNET[172][26]);
unset($INTERNET[172][27]);
unset($INTERNET[172][28]);
unset($INTERNET[172][29]);
unset($INTERNET[172][30]);
unset($INTERNET[172][31]);

//die(\metaclassing\Utility::dumper($INTERNET));

$OUTPUTARRAY = array();

$i=0;
foreach($INTERNET as $OCTET1 => $SUBNETS1)
{
	foreach($SUBNETS1 as $OCTET2 => $SUBNETS2)
	{
		foreach ($SUBNETS2 as $OCTET3 => $NETWORKS)
		{
			foreach ($NETWORKS as $NETWORK)
			{
				print "{$NETWORK}\n";
			}
		}
	}
}
/**/
/*
print "<pre>\n";
\metaclassing\Utility::dumper($INTERNET);
print "</pre>\n";
/**/
//print $HTML->footer();
?>
