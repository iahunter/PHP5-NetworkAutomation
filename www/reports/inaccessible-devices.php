<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports");
$HTML->breadcrumb("Inaccessible Devices",$HTML->thispage);

$HEAD_EXTRA = <<<EOT
<script type="text/javascript" language="javascript">
jQuery.extend({
    confirm: function(message, title, okAction) {
        jQuery("<div></div>").dialog({
            // Remove the closing 'X' from the dialog
            open: function(event, ui) { jQuery(".ui-dialog-titlebar-close").hide(); }, 
            buttons: {
                "Ok": function() {
                    jQuery(this).dialog("close");
                    return true;
                },
                "Cancel": function() {
                    jQuery(this).dialog("close");
                    return false;
                }
            },
            close: function(event, ui) { jQuery(this).remove(); },
            resizable: false,
            title: title,
            modal: true
        }).text(message);
    }
});
</script>
EOT;

$HTML->set("HEAD_EXTRA",$HEAD_EXTRA);

print $HTML->header("Inaccessible Devices Report");

$SEARCH = array(	// Search for all network devices
				"category"      => "management",
				"type"          => "device_network_%",
				);
$RESULTS = Information::search($SEARCH,"stringfield4");	// Search with order!
$RECORDCOUNT = count($RESULTS);

$i=0;
print <<<END
<table class="report">
	<caption class="report">Inaccessible Devices (Scanned All {$RECORDCOUNT} Devices)</caption>
	<thead>
		<tr>
			<th class="report">ID</th>
			<th class="report">Device IP</th>
			<th class="report">Protocol</th>
			<th class="report">Prompt</th>
			<th class="report">Model</th>
			<th class="report">ShowRun</th>
			<th class="report">ShowVer</th>
			<th class="report">ShowInv</th>
			<th class="report">PING</th>
			<th class="report">SSH</th>
			<th class="report">Telnet</th>
			<th class="report">Last Scanned</th>
			<th class="report">Scan</th>
			<th class="report">Edit</th>
			<th class="report">Deactivate</th>
		</tr>
	</thead>
	<tbody class=\"report\">
END;

// Keep track of the statistics for all device management stats
$COUNT = array();
$COUNT["run"		] = array();
$COUNT["ver"		] = array();
$COUNT["inv"		] = array();
$COUNT["protocol"	] = array();

$i=1;
foreach ($RESULTS as $DEVICEID)
{
	$DEVICE = Information::retrieve($DEVICEID);

	$RUNLEN	= strlen($DEVICE->data["run"]);
	$VERLEN	= strlen($DEVICE->data["version"]);
	$INVLEN	= strlen($DEVICE->data["inventory"]);

	$RUNCOLOR		= ($RUNLEN < 900)						? "red" : "green";
	$VERCOLOR		= ($VERLEN < 200)						? "red" : "green";
	$INVCOLOR		= ($INVLEN < 100)						? "red" : "green";
	switch ($DEVICE->data["protocol"])
	{
		case "ssh2":
			$PROTOCOLCOLOR = "green";
			break;
		case "ssh1":
			$PROTOCOLCOLOR = "yellow";
			break;
		case "telnet":
			$PROTOCOLCOLOR = "yellow";
			break;
		default:
			$PROTOCOLCOLOR = "red";
			break;
	}

	$COUNT['run'][$RUNCOLOR]++;
	$COUNT['ver'][$VERCOLOR]++;
	$COUNT['inv'][$INVCOLOR]++;
	$COUNT['protocol'][$DEVICE->data["protocol"]]++;

	// If the device is unhappy with its management, print a row
	if (	($RUNCOLOR		== "red")	||
			($VERCOLOR		== "red")	||
			($INVCOLOR		== "red")	||
			($PROTOCOLCOLOR	== "red")	)
	{
		$i++; $ROWCLASS = "row".(($i % 2)+1);
		print <<<END
		<tr class="{$ROWCLASS}">
			<td class="report"><a href="/information/information-view.php?id={$DEVICE->data["id"]}">{$DEVICE->data["id"]}</td>
			<td class="report">{$DEVICE->data["ip"]}</td>
			<td class="report {$PROTOCOLCOLOR}">{$DEVICE->data["protocol"]}</td>
			<td class="report">{$DEVICE->data["name"]}</td>
			<td class="report">{$DEVICE->data["model"]}</td>
			<td class="report $RUNCOLOR">{$RUNLEN}</td>
			<td class="report $VERCOLOR">{$VERLEN}</td>
			<td class="report $INVCOLOR">{$INVLEN}</td>
			<td class="report" width="76" background="/ajax/probe.png.php?host={$DEVICE->data["ip"]}&detail=yes"         style="background-repeat: no-repeat; background-position: left;"></td>
			<td class="report" width="49" background="/ajax/probe.png.php?host={$DEVICE->data["ip"]}&port=22&detail=yes" style="background-repeat: no-repeat; background-position: left;"></td>
			<td class="report" width="49" background="/ajax/probe.png.php?host={$DEVICE->data["ip"]}&port=23&detail=yes" style="background-repeat: no-repeat; background-position: left;"></td>
			<td class="report">{$DEVICE->data["lastscan"]}</td>
END;
		if(PERMISSION_CHECK("information.management.device.*.action.*"))
		{
			print <<<END
			<td class="report" width="49" align="center"><a href="/information/information-action.php?id={$DEVICE->data["id"]}&action=Scan" target="_blank"><img src="/images/managed.png"></a></td>
END;
		}else { print "<td></td>"; }
		if(PERMISSION_CHECK("information.management.device.*.edit"))
		{
			print <<<END
			<td class="report" width="49" align="center"><a href="/information/information-edit.php?id={$DEVICE->data["id"]}" target="_blank"><img src="/images/icon_changelink.gif"></a></td>
END;
		}else { print "<td></td>"; }
		if(PERMISSION_CHECK("information.management.device.*.edit"))
		{
			print <<<END
			<td class="report" width="49" align="center"><a href="/information/information-toggleactive.php?id={$DEVICE->data["id"]}" target="_blank"><img src="/images/icon_deletelink.gif"></a></td>
END;
		}else { print "<td></td>"; }
		print "</tr>\n";
	}
	unset($DEVICE);	// Save memory by clearing information objects from the set after we are done!
}
print "</tbody></table>\n";

print "<br>\n";
print "<table width=600><tr><td valign=top>";
print HTML::quicktable_report("Protocol"	,array("Protocol","Count")	,$COUNT['protocol']	) . "<br>\n";
print "</td><td valign=top>";
print HTML::quicktable_report("Run"			,array("Output","Count")	,$COUNT['run']		) . "<br>\n";
print "</td><td valign=top>";
print HTML::quicktable_report("Version"		,array("Output","Count")	,$COUNT['ver']		) . "<br>\n";
print "</td><td valign=top>";
print HTML::quicktable_report("Inventory"	,array("Output","Count")	,$COUNT['inv']		) . "<br>\n";
print "</td></tr></table>";

print $HTML->footer();
?>


