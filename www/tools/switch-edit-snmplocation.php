<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

print $HTML->header("Access Switch SNMP Location Configuration Tool");

PERMISSION_REQUIRE("tool.switch.edit");

//
// STEP 1 - VIEW current snmp location configuration. Prompt for changes.
//
	$DEVICEID		= $_GET['device'];
	$DEBUG			= $_SESSION["DEBUG"];	if ($DEBUG>0) { print "<h2>TOOL RUNNING IN DEBUG MODE, NO CONFIGURATION CHANGES WILL BE MADE!</h2>\n"; }

	$SNMPLOCATION	= \metaclassing\Utility::strip($_GET['snmplocation']);
	$STEP			= \metaclassing\Utility::strip($_GET['step']);

	if ($DEVICEID == "")
	{
		print "The switch snmp location configuration tool was not correctly passed a switch to configure.<br>\n";
		print "Please go <a href=/tools/switch-viewer.php>back</a> and try again.\n";
		\metaclassing\Utility::dumper($_GET);
		$MESSAGE = "ERROR: Failed to pass variables DEVICEID: $DEVICEID";
		$DB->log($MESSAGE);
		exit;
	}

	$js = new \metaclassing\JS;

	print "<div id='dialog' style='display: none;'>\n";
	print "<div id='message' style='font-size: 14px;'></div><br>\n";
	print $js->progressbar('progressbar');
	print "<div id='confirm' style='display: none' align=right>";
	print "<input type=button id=confirm value='Ok' ".
        	"onClick='javascript:$(&quot;#dialog&quot;).dialog(&quot;close&quot;);'></div>\n";
	print "</div>\n";
	print $js->dialog('dialog','init',array('title' => 'Loading...', 'no.close' => 1, 'height' => 240,'width' => 650));
	print $js->progressbar('progressbar','init');

	$progress = 0;

	print $js->html('message','Creating Device Object');    $progress = 5;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    \metaclassing\Utility::flush();

	$command = new command($DEVICEID);

	print $js->html('message',"Connecting to device " . $HTML->timer_diff());               $progress = 10;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 3000)); \metaclassing\Utility::flush();

	$cli = $command->getcli();

	if (!$cli->connected)
	{
		print $js->dialog('dialog','overlay',array('overlay.color' => '990000'));
		print $js->dialog('dialog','title',array('title' => 'Failure', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));
		print $js->progressbar('progressbar','hide');
		print $js->show('confirm'); \metaclassing\Utility::flush();
		print "The switch snmp location configuration tool could not connect to this device.<br>\n";
		$MESSAGE = "ERROR: Could not connect to DEVICEID: $DEVICEID";
		$DB->log($MESSAGE);
		exit;
	}

	$PROMPT = $cli->prompt;

	print $js->html('message',"Connected to $PROMPT " . $HTML->timer_diff());               $progress = 30;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    \metaclassing\Utility::flush();

	// Begin running commands on each device
	//======================================

	$SWITCHINFO[$PROMPT]['prompt'] = $PROMPT;
	$cli->exec("terminal length 0");

	//DANGER ZONE, making actual configuration changes to device.
	if($STEP == 2)
	{
		print $js->html('message',"Entering Configuration Mode! " . $HTML->timer_diff());$progress = 35;
		print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 3000)); \metaclassing\Utility::flush();

		$COMMAND = "configure terminal";				($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "snmp-server location $SNMPLOCATION";($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "end";								($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);

		print $js->html('message',"Done Making Changes. Saving Config! " . $HTML->timer_diff());$progress = 70;
		print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 10000)); \metaclassing\Utility::flush();
		$cli->settimeout(30);
		$COMMAND = "copy run start\n";					($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$cli->settimeout(15);

		$MESSAGE = "CONFIGURE {$PROMPT} SNMP LOCATION {$SNMPLOCATION}";
		$DB->log($MESSAGE);
	}
	//END of danger.

	print $js->html('message',"Gathering $prompt configuration " . $HTML->timer_diff());$progress = 80;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 4000)); \metaclassing\Utility::flush();
	$COMMAND = "show running";
	$SWITCHINFO[$PROMPT]['showrun']         = $cli->exec($COMMAND);

	$cli->disconnect();

	print $js->html('message',"Work Complete! " . $HTML->timer_diff());     $progress = 100;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    \metaclassing\Utility::flush();

	print $js->dialog('dialog','overlay',array('overlay.color' => '009900'));
	print $js->dialog('dialog','title',array('title' => 'Success', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));

	print $js->progressbar('progressbar','hide');
	print $js->show('confirm'); \metaclassing\Utility::flush();

	// Parse and generate output
	//============================================

        foreach ($SWITCHINFO as $SWITCH)
        {
			$CISCO = new \metaclassing\Cisco();

			$CISCO->parse_config(explode("\n",$SWITCH['showrun']));
			$LOCATION = "";
			foreach (explode("\n",$SWITCH['showrun']) as $LINE)
			{
				if (preg_match('/snmp-server location (.+)/',$LINE,$MATCH))
				{
					$LOCATION = $MATCH[1];
				}
			}

		print $SWITCH['prompt']."\n";

		print <<<END
			<form name="switchedit" method="get" action="{$_SERVER['PHP_SELF']}">
				<input type="hidden" name="device" value="{$DEVICEID}">
				<input type="hidden" name="step" value="2">
				<input type="hidden" name="debug" value="{$DEBUG}">
				SNMP Location: <input type="text" name="snmplocation" size="100" value="{$LOCATION}"><br>
				<input type="submit" name="go" value="Configure SNMP Location">
			</form>
END;

	} #END OF FOREACH for all switches

// DONE.

$LINK = "/tools/switch-viewer.php?switch=".$SWITCH_ID;
print $HTML->footer("Back",$LINK);

?>
