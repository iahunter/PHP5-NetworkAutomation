<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
require_once "diff.class.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","");
$HTML->breadcrumb("Config Diff",$HTML->thispage);
print $HTML->header("Config Diff");

// If we are NOT passed a list of sites and models to search for, print out a form for the user to fill in!
if (!isset($_GET["devices" ])	||
	count($_GET["devices"]) < 2	)
{
	// Build our list of devices
	$QUERY = <<<END
	    SELECT id,stringfield0 FROM information
		WHERE category LIKE 'Management'
		AND type LIKE 'Device_Network_Cisco%'
		AND active = 1
		AND stringfield0 != ""
		ORDER BY stringfield0 ASC
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
//\metaclassing\Utility::dumper($RESULTS);
	$DEVICEOPTIONS = "";
	foreach($RESULTS as $RESULT)
	{
		$DEVICEOPTIONS .= "<option value=\"{$RESULT["id"]}\">{$RESULT["stringfield0"]}</option>";
	}
	print <<<END
	<table width="500" border="0" cellspacing="0" cellpadding="1">
		<tr><td>Please select at least TWO devices for differential comparison:</td></tr>
	</table>
	<form name="reportlist" method="get" action="{$_SERVER['PHP_SELF']}">
	<table width="300" border="0" cellspacing="0" cellpadding="1">
		<tr>
			<td>
				Devices:
				<br><select name="devices[]" size="40" multiple="true">
					{$DEVICEOPTIONS}
				</select>
			</td>
         </tr>
	</table><br>
	<input type="hidden" name="order"   value="asc">
	<input type="hidden" name="orderby" value="id" >
	<input type="submit" name="Go!" value="Go!"></td>
	</form>
END;
	print $HTML->footer();

}else{

	$MESSAGE = "CONFIG DIFF";
//	$DB->log($MESSAGE);

	$DEVICES = $_GET["devices"];

	$DIFFS = array();	$LASTDEVICE = array();
	foreach ($DEVICES as $DEVICEID)
	{
		$DEVICE = Information::retrieve($DEVICEID);
		if ( isset($LASTDEVICE["name"]) )
		{
			// Compare the configs of last device
			$DIFFS [ "{$LASTDEVICE["name"]} / {$DEVICE->data["name"]}" ] = \metaclassing\Diff::compare($LASTDEVICE["run"],$DEVICE->data["run"]);
		}
		$LASTDEVICE["name"] = $DEVICE->data["name"];// Save the current device as the last device...
		$LASTDEVICE["run"]	= $DEVICE->data["run"];	// Save the current device as the last device...
	}

	// foreach diff, compare 1+2, 2+3, 3+4, ... in a big long lame table.
	$RECORDCOUNT = count($DEVICES);
	print <<<END
		<table class="report">
			<caption class="report">Config Differences (Comparing {$RECORDCOUNT} Devices)</caption>
			<thead>
				<tr>

END;
	foreach($DIFFS as $DIFFNAME => $DIFF)
	{
		print "					<th class=\"report\">{$DIFFNAME}</th>\n";
	}
	print <<<END
				</tr>
			</thead>
			<tbody>
				<tr>

END;
	foreach($DIFFS as $DIFFNAME => $DIFF)
	{
		$DIFFHTML = \metaclassing\Diff::toTable($DIFF);
		print "					<td>{$DIFFHTML}</td>\n";
	}
	print <<<END
				</tr>
			</tbody>
		</table>

END;

	print $HTML->footer("Back",$HTML->thispage);
}

?>

