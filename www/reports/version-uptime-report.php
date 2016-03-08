<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports/");
$HTML->breadcrumb("VPN Router IOS Version and Uptime Report",$HTML->thispage);
print $HTML->header("VPN Router IOS Version and Uptime Report");

// Function for mapping show version output to an actual software version string
function CISCO_VERSION($showver)
{
	if(preg_match('/.*image\ file\ is\ \".*:[\/]?(.*)\".*/',$showver,$reg))
		{ $version = $reg[1]; }
	if($version == "flash")
		{ $version = ""; }
	if(preg_match('/.*system\ image\ file\ is:\ .*:[\/]?(\S+).*/',$showver,$reg))
		{ $version = $reg[1]; }
	if($version == "" && preg_match('/.*Adaptive\ Security\ Appliance\ Software\ Version\ (.+)\ .*/',$showver,$reg))
		{ $version = "asa".$reg[1]; }
	if($version == "" && preg_match('/.*Firewall\ Version\ (.+)\ .*/',$showver,$reg))
		{ $version = "fwsm".$reg[1]; }
	if($version == "" && preg_match('/.*Version\ (\d+.+),\ .*/',$showver,$reg))
		{ $version = "ios".$reg[1]; }
	if($version != "" && preg_match('/.+\/(.+)/',$version,$reg))
		{ $version = $reg[1]; }
	if($version=="")
		{ $version = "unknown"; }
	return $version;
}

$SEARCH = array(    // Search for all cisco network devices with FELX in their output
                "category"      => "management",
				"type"          => "device_network_cisco%",
				"custom"		=> "%FLEX_IKE2%",
				);

$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

if (!$RECORDCOUNT) { print "No devices found matching the query.\n"; die($HTML->footer()); }

print <<<END
		<table class="report">
			<caption class="report">IOS Version Report (Found {$RECORDCOUNT} devices)</caption>
				<thead>
					<tr>
						<th class="report">ID</th>
						<th class="report">Name</th>
						<th class="report">IP</th>
						<th class="report">Protocol</th>
						<th class="report">Model</th>
						<th class="report">IOS Version</th>
						<th class="report">Uptime</th>
					</tr>
				</thead>
			<tbody class="report">
END;

$IOS_VERSION = array();
$MODEL = array();

$i=0;
foreach($RESULTS as $DEVICEID)
{
	$DEVICE = Information::retrieve($DEVICEID);

	$VERSION		= CISCO_VERSION($DEVICE->data["version"]);
	$VERSIONCOLOR	= \metaclassing\Cisco::checkIosVersion($DEVICE->data["model"], $VERSION);
	$PROTOCOLCOLOR	= ($DEVICE->data["protocol"] == "ssh2") ? "green" : "red";
	$IOS_VERSION[$VERSION]++;
	$MODEL[$DEVICE->data["model"]]++;
	$ROWCLASS = "row".(($i++ % 2)+1);

	if( preg_match('/.*uptime\ is(.+\ minute[s]?).*/',$DEVICE->data["version"],$REG) )
	{
		$UPTIME = $REG[1];
		if( preg_match('/.*\s+(\d+)\ year.*/'	,$UPTIME,$REG)){ $YEARS		= $REG[1]; }else{ $YEARS	= 0; }
		if( preg_match('/.*\s+(\d+)\ week.*/'	,$UPTIME,$REG)){ $WEEKS		= $REG[1]; }else{ $WEEKS	= 0; }
		if( preg_match('/.*\s+(\d+)\ day.*/' 	,$UPTIME,$REG)){ $DAYS		= $REG[1]; }else{ $DAYS		= 0; }
		if( preg_match('/.*\s+(\d+)\ hour.*/' 	,$UPTIME,$REG)){ $HOURS		= $REG[1]; }else{ $HOURS	= 0; }
		if( preg_match('/.*\s+(\d+)\ minute.*/'	,$UPTIME,$REG)){ $MINUTES	= $REG[1]; }else{ $MINUTES	= 0; }
	}else{
		$UPTIME = "unknown";
	}
	$OUTPUT = <<<END
				<tr class="$ROWCLASS">
					<td class="report"><a href="/information/information-view.php?id={$DEVICE->data["id"]}">{$DEVICE->data["id"]}</a></td>
					<td class="report">{$DEVICE->data["name"]}</td>
					<td class="report">{$DEVICE->data["ip"]}</td>
					<td class="report ${PROTOCOLCOLOR}">{$DEVICE->data["protocol"]}</td>
					<td class="report">{$DEVICE->data["model"]}</td>
					<td class="report">{$VERSION}</td>
					<td class="report">{$UPTIME}</td>
				</tr>
END;

	if ( !is_array($RECORDS											) )	{ $RECORDS											= array(); }
	if ( !is_array($RECORDS[$YEARS]									) )	{ $RECORDS[$YEARS]									= array(); }
	if ( !is_array($RECORDS[$YEARS][$WEEKS]							) )	{ $RECORDS[$YEARS][$WEEKS]							= array(); }
	if ( !is_array($RECORDS[$YEARS][$WEEKS][$DAYS]					) )	{ $RECORDS[$YEARS][$WEEKS][$DAYS]					= array(); }
	if ( !is_array($RECORDS[$YEARS][$WEEKS][$DAYS][$HOURS]			) )	{ $RECORDS[$YEARS][$WEEKS][$DAYS][$HOURS]			= array(); }
	if ( !is_array($RECORDS[$YEARS][$WEEKS][$DAYS][$HOURS][$MINUTES]) )	{ $RECORDS[$YEARS][$WEEKS][$DAYS][$HOURS][$MINUTES]	= array(); }
	array_push($RECORDS[$YEARS][$WEEKS][$DAYS][$HOURS][$MINUTES],$OUTPUT);
}

function recursive_sort_print($RECORDS)
{
	ksort($RECORDS);
	foreach ($RECORDS as $KEY => $VALUE)
	{
		if ( is_array($RECORDS[$KEY]) )
		{
			recursive_sort_print($RECORDS[$KEY]);
		}else{
			print "{$RECORDS[$KEY]}";
		}
	}
}

recursive_sort_print($RECORDS);	// Sort and print our nested output array!

print "</tbody></table>\n";

arsort($IOS_VERSION);
arsort($MODEL);

print "<br>\n";
print "<table width=800 CELLPADDING=0 CELLSPACING=0 border=0><tr><td valign=top>";

print \metaclassing\HTML::quicktable_report("Device Models", array("Model","Count"), $MODEL) . "\n";
print "</td><td valign=top>";
print \metaclassing\HTML::quicktable_report("IOS Versions", array("IOS","Count"), $IOS_VERSION) . "\n";
print "</td></tr></table>";

new dBug($RECORDS);

?>

