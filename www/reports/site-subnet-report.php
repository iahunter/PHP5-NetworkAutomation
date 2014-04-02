<?php
define(NO_AUTHENTICATION,1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

/*
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports");
$HTML->breadcrumb("IPv4 Subnet",$THISPAGE);
print $HTML->header("IPv4 Subnet Report");
/**/

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
			if (is_object($error)) { print "Input Error: $error->getMessage()\n"; }
            $network = $ip_calc->network."/".$ip_calc->bitmask;
			// print "{$INFOBJECT->data["name"]} has $ip_calc->ip + $ip_calc->netmask = $network\n";
			if(!is_array($networks[$network])) { $networks[$network] = array(); }
			array_push($networks[$network],$INFOBJECT->data["name"]);

			// Super hacky testing
//			preg_match('/(\d+).(\d+).(\d+).*/',$network,$reg); // dumper($reg);
/*			$OCTET1 = intval($reg[1]);
			$OCTET2 = intval($reg[2]);
			$OCTET3 = intval($reg[3]);
			$NET = $OCTET1 . "." . $OCTET2 . "." . $OCTET3;

			$INTERNET[$OCTET1][$OCTET2][$OCTET3]++;
			ksort($INTERNET[$OCTET1][$OCTET2]);
			ksort($INTERNET[$OCTET1]);
			ksort($INTERNET);
/**/
		}
    }
	unset($INFOBJECT);
}

ksort($networks);
$netcount = sizeof($networks);
//print "Found {$RECORDCOUNT} devices with $netcount unique IPv4 networks.<br>\n";

header("Content-Type: text/plain");
$OUTPUTARRAY = array();
foreach ($networks as $PREFIX => $DEVICEARRAY)
{
	foreach($DEVICEARRAY as $DEVICE)
	{
		$PATTERN = "([a-z]{5}[a-z0-9]{3})";
		if ( preg_match($PATTERN,$DEVICE,$REG) )
		{
			$NET = Net_IPv4::parseAddress($PREFIX);
			if ($NET->bitmask < 28)
			{
				array_push($OUTPUTARRAY,"{$PREFIX},{$REG[0]}");
			}
		}
	}
}
$OUTPUTARRAY = array_unique($OUTPUTARRAY);

//print "Found " . count($OUTPUTARRAY) . " site-subnet pairs:\n";
print implode($OUTPUTARRAY,"\r\n");

//print "<pre>\n"; dumper($networks); print "</pre>\n";

/*
$i=0;
print "<table class=\"report\"><tbody class=\"report\">\n";
foreach($INTERNET as $OCTET1 => $SUBNETS1)
{
	foreach($SUBNETS1 as $OCTET2 => $SUBNETS2)
	{
		foreach ($SUBNETS2 as $OCTET3 => $SUBNETCOUNT)
		{
			print "<tr class=\"row" . ++$i%2 . "\">
				<td class=\"report\">$OCTET1</td>
				<td class=\"report\">.</td>
				<td class=\"report\">$OCTET2</td>
				<td class=\"report\">.</td>
				<td class=\"report\">$OCTET3</td>
				<td class=\"report\">.0</td>
				<td class=\"report\"></td>
				<td class=\"report\"></td>
				<td class=\"report\"></td>
				<td class=\"report\"></td>
				<td class=\"report\">Instances: $SUBNETCOUNT</td>
				</tr>\n";
		}
	}
}
print "</table>\n";
/**/
/*
print "<pre>\n";
dumper($INTERNET);
print "</pre>\n";
/**/
//print $HTML->footer();
?>
