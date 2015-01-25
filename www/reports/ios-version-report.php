<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports/");
$HTML->breadcrumb("IOS Version",$HTML->thispage);
print $HTML->header("IOS Version Report");

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

// If we are NOT passed a list of sites and models to search for, print out a form for the user to fill in!
if (!isset($_GET["sites" ])	&&
	!isset($_GET["models"])	)
{
	// Build our list of sites
	$QUERY = <<<END
	    SELECT DISTINCT SUBSTRING(stringfield0,1,8) AS sitecode FROM information
		WHERE category LIKE 'Management'
		AND type LIKE 'Device_Network_Cisco%'
		AND active = 1
		HAVING sitecode RLIKE '[a-z]{5}[a-z0-9]{3}'
		ORDER BY sitecode ASC
END;
	// LEGACY QUERY: select distinct substring(prompt,1,8) as sitecode from device having sitecode rlike '[a-z]{5}[a-z0-9]{3}' order by sitecode asc
	$DB->query($QUERY);
	try {
		$DB->execute();
		$RESULTS = $DB->results();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE . $HTML->footer());
	}
	$SITES = array(); foreach($RESULTS as $KEY => $RESULT) { array_push($SITES,$RESULT["sitecode"]); }

	// Build our list of device models
	$MODELS = array();
	// LEGACY QUERY: select distinct model from device where model is not null and model != '' order by model asc
	$QUERY = <<<END
	    SELECT DISTINCT stringfield8 FROM information
		WHERE category LIKE 'Management'
		AND type LIKE 'Device_Network_Cisco%'
		AND stringfield8 IS NOT NULL
		AND stringfield8 != ''
		ORDER BY stringfield8 ASC
END;
	$DB->query($QUERY);
	try {
		$DB->execute();
		$RESULTS = $DB->results();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE . $HTML->footer());
	}
	$MODELS = array(); foreach($RESULTS as $KEY => $RESULT) { array_push($MODELS,$RESULT["stringfield8"]); }


	// Generate a nice HTML formatted list of sites and models to print out...
	$SITEOPTIONS  = ""; foreach($SITES as $SITE)	{ $SITEOPTIONS	.= "<option value=\"%{$SITE}%\">{$SITE}</option>";	}
	$MODELOPTIONS = ""; foreach($MODELS as $MODEL)	{ $MODELOPTIONS	.= "<option value=\"%{$MODEL}%\">{$MODEL}</option>";	}

	print <<<END
	<table width="500" border="0" cellspacing="0" cellpadding="1">
		<tr><td>Please select at least one or more models and/or sites to check IOS versioning:</td></tr>
	</table>
	<form name="reportlist" method="get" action="{$_SERVER['PHP_SELF']}">
	<table width="300" border="0" cellspacing="0" cellpadding="1">
		<tr>
			<td>
				Models:
				<br><select name="models[]" size="30" multiple="true">
					{$MODELOPTIONS}
				</select>
			</td>
			<td>
				Sites:<br>
				<select name="sites[]" size="30" multiple="true">
					{$SITEOPTIONS}
				</select>
         </tr>
	</table><br>
	<input type="hidden" name="order"   value="asc">
	<input type="hidden" name="orderby" value="id" >
	<input type="submit" name="Go!" value="Go!"></td>
	</form>
END;
	print $HTML->footer();

}else{

	$MESSAGE = "REPORT IOS ON DEVICES";
	$DB->log($MESSAGE);

	// Get our search input from the browser
	$SEARCH = array(    // Search for all cisco network devices
	                "category"      => "management",
					"type"          => "device_network_cisco%",
					);
	if (count($_GET["sites"]))	{ $SEARCH["stringfield0"]	= $_GET["sites"];	}
	if (count($_GET["models"]))	{ $SEARCH["stringfield8"]	= $_GET["models"];	}

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
		$VERSIONCOLOR	= cisco_check_ios_version($DEVICE->data["model"], $VERSION);
		$PROTOCOLCOLOR	= ($DEVICE->data["protocol"] == "ssh2") ? "green" : "red";
		$IOS_VERSION[$VERSION]++;
		$MODEL[$DEVICE->data["model"]]++;
		$ROWCLASS = "row".(($i++ % 2)+1);

		print <<<END
				<tr class="$ROWCLASS">
					<td class="report">{$DEVICE->data["id"]}</td>
					<td class="report">{$DEVICE->data["name"]}</td>
					<td class="report">{$DEVICE->data["ip"]}</td>
					<td class="report ${PROTOCOLCOLOR}">{$DEVICE->data["protocol"]}</td>
					<td class="report">{$DEVICE->data["model"]}</td>
					<td class="report">{$VERSION}</td>
				</tr>
END;
	}
	print "</tbody></table>\n";

	arsort($IOS_VERSION);
	arsort($MODEL);

	print "<br>\n";
	print "<table width=800 CELLPADDING=0 CELLSPACING=0 border=0><tr><td valign=top>";

	print HTML::quicktable_report("Device Models", array("Model","Count"), $MODEL) . "\n";
	print "</td><td valign=top>";
	print HTML::quicktable_report("IOS Versions", array("IOS","Count"), $IOS_VERSION) . "\n";
	print "</td></tr></table>";

	print $HTML->footer("Back",$HTML->thispage);
}

?>

