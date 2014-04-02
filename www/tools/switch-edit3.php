<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

print $HTML->header("Access Switch Port Configuration Tool");

PERMISSION_REQUIRE("tool.switch.edit");

//
// STEP 1 - VIEW current port configuration. Prompt for changes.
//
	$DEVICEID	= $_GET['device'];
	$INTERFACE	= strip($_GET['interface']);
	$DEBUG		= $_SESSION["DEBUG"];	if ($DEBUG>0) { print "<h2>TOOL RUNNING IN DEBUG MODE, NO CONFIGURATION CHANGES WILL BE MADE!</h2>\n"; }

	$VLAN		= strip($_GET['vlan']);
	$DESCRIPTION= strip($_GET['description']);
	$STEP		= strip($_GET['step']);

	$USERNAME	= $_SESSION["AAA"]["username"];

	if ($DEVICEID == "" | $INTERFACE == "")
	{
		print "The switch port configuration tool was not correctly passed a switch and port to configure.<br>\n";
		print "Please go <a href=/tools/switch-viewer.php>back</a> and try again.\n";
		dumper($_GET);
		$MESSAGE = "ERROR: Failed to pass variables DEVICEID: $DEVICEID INTERFACE: $INTERFACE";
		$DB->log($MESSAGE);
		exit;
	}

	$js = new JS;

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
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    john_flush();

	$command = new command($DEVICEID);

	print $js->html('message',"Connecting to device " . $HTML->timer_diff());               $progress = 10;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 3000)); john_flush();

	$cli = $command->getcli();

	if (!$cli->connected)
	{
		print $js->dialog('dialog','overlay',array('overlay.color' => '990000'));
		print $js->dialog('dialog','title',array('title' => 'Failure', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));
		print $js->progressbar('progressbar','hide');
		print $js->show('confirm'); john_flush();
		print "The switch port configuration tool could not connect to this device.<br>\n";
		$MESSAGE = "ERROR: Could not connect to DEVICEID: $DEVICEID INTERFACE: $INTERFACE";
		$DB->log($MESSAGE);
		exit;
	}

	$PROMPT = $cli->prompt;

	print $js->html('message',"Connected to $PROMPT " . $HTML->timer_diff());               $progress = 30;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    john_flush();

        // Begin running commands on each device
        //======================================

	$SWITCHINFO[$PROMPT]['prompt'] = $PROMPT;
	$cli->exec("terminal length 0");

	//DANGER ZONE, making actual configuration changes to device.
	if($STEP == 2)
	{
		print $js->html('message',"Entering Configuration Mode! " . $HTML->timer_diff());$progress = 35;
		print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 3000)); john_flush();

		$COMMAND = "configure terminal";			($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "interface {$INTERFACE}";		($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);

		$COMMAND = "description TOOL({$USERNAME}) {$DESCRIPTION}";	($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "switchport";					($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "spanning-tree bpduguard enable";($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);

		if ($VLAN > 0 && $VLAN < 4094) {
		$COMMAND = "switchport mode access";		($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "no switchport trunk encap";		($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "switchport access vlan {$VLAN}";($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "spanning-tree portfast";		($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		}elseif ($VLAN = "trunk") {
		$COMMAND = "switchport trunk encap dot1q";	($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "switchport mode trunk";			($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "no switchport access vlan";		($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "spanning-tree portfast trunk";	($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		}else{
			print "Error: Unknown VLAN {$VLAN}!\n";
		}

		$COMMAND = "auto qos trust dscp";			($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "no shut";						($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$COMMAND = "end";							($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);

		print $js->html('message',"Done Making Changes. Saving Config! " . $HTML->timer_diff());$progress = 70;
		print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 10000)); john_flush();
		$cli->settimeout(30);
		$COMMAND = "copy run start\n";					($DEBUG > 0) ? print "$COMMAND<br>\n" : $OUTPUT = $cli->exec($COMMAND);
		$cli->settimeout(15);

		$MESSAGE = "CONFIGURE ".$PROMPT." ".$INTERFACE." VLAN ".$VLAN;
		$DB->log($MESSAGE);
	}
	//END of danger.

	print $js->html('message',"Gathering $prompt configuration " . $HTML->timer_diff());$progress = 80;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 4000)); john_flush();
	$COMMAND = "show running";
	$SWITCHINFO[$PROMPT]['showrun']         = $cli->exec($COMMAND);

	print $js->html('message',"Collecting $prompt interfaces " . $HTML->timer_diff());      $progress = 90;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 1000)); john_flush();
	$COMMAND = "show int status";
	$SWITCHINFO[$PROMPT]['showintstatus']   = $cli->exec($COMMAND);

	$COMMAND = "sh mac address-table dynamic";
	$SWITCHINFO[$PROMPT]['showmac']         = $cli->exec($COMMAND);

	$COMMAND = "show vlan brief | I active|Name";
	$SWITCHINFO[$PROMPT]['showvlan']        = $cli->exec($COMMAND);

	$cli->disconnect();

	print $js->html('message',"Work Complete! " . $HTML->timer_diff());     $progress = 100;
	print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    john_flush();

	print $js->dialog('dialog','overlay',array('overlay.color' => '009900'));
	print $js->dialog('dialog','title',array('title' => 'Success', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));

	print $js->progressbar('progressbar','hide');
	print $js->show('confirm'); john_flush();

	// Parse and generate output
	//============================================

        foreach ($SWITCHINFO as $SWITCH)
        {
                $CISCO = new Cisco();

                $CISCO->parse_config(explode("\n",$SWITCH['showrun']));

		$CISCO_INTCONFIG = $CISCO->config("interface");
		$CISCO_CONFIG = "interface ".$INTERFACE."\n";
		foreach($CISCO_INTCONFIG[$INTERFACE] as $line)
			{ $CISCO_CONFIG .= "  $line\n"; }

                $SWITCH['vlans'] = array();
                $SWITCH_IP = $SWITCH['ip'];
                $SWITCH_ID = $SWITCH['id'];
                $CISCO->parse_interface_status($SWITCH['showintstatus']);
                $SWITCH['vlans'] = $CISCO->parse_space_delimited_command($SWITCH['showvlan']);
                $SWITCH['mactable'] = $CISCO->parse_mac_address_table(explode("\n",$SWITCH['showmac']));
                $SWITCH['interfaces'] = $CISCO->config('interface');

                if (!$SWITCH['interfaces'])
                {
                        print "<p>Cannot get interfaces from ".$SWITCH['prompt']."!<br>\n";
                        next;
                }

		print $SWITCH['prompt']."\n";

		$idata = $SWITCH['interfaces'][$INTERFACE];
		$INT_NAME = $INTERFACE;
		$INTERFACE = $CISCO->iface($INT_NAME);

		$INT_DESC = $INTERFACE['desc'];
		$INT_STAT = $INTERFACE['status'];
		$INT_MODE = $INTERFACE['mode'];
		$INT_VLAN_ACC = (isset($INTERFACE['vlan.access'])) ? $INTERFACE['vlan.access'] : "none";
		$INT_VLAN_TRK = (isset($INTERFACE['vlan.trunk']))  ? $INTERFACE['vlan.trunk']  : "all";
		if($INT_MODE == "access") { $INT_VLAN = ($INT_VLAN_ACC != "") ? $INT_VLAN_ACC : "none"; }
		if($INT_MODE == "trunk" ) { $INT_VLAN = ($INT_VLAN_TRK != "") ? $INT_VLAN_TRK : "all";  }
		if($INT_MODE == ""      ) { $INT_MODE = "routed"; $INT_VLAN = "routed";                 }
		$INT_SPEE = $INTERFACE['speed'];
		$INT_DUPL = $INTERFACE['duplex'];

		//Strip off the "TOOL($USERNAME) " from our description if we configured the port.
		if (preg_match('/^TOOL\(\S+\) (.*)$/',$INT_DESC,$result)) { $INT_DESC = $result[1]; }

//dumper($INT_VLAN);
?>
<form name="switchedit" method="get" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="device" value="<?=$DEVICEID?>">
<input type="hidden" name="interface" value="<?=$INT_NAME?>">
<input type="hidden" name="step" value="2">
<input type="hidden" name="debug" value="<?=$DEBUG?>">
Current Configuration Of <?=$INT_NAME?><pre><?=$CISCO_CONFIG?></pre>
      <table width="800" border="0" cellspacing="0" cellpadding="1">
         <tr><td>Please select a VLAN to place the port in, or configure it as a 802.1q tagged TRUNK.</td></tr>
         <tr><td><select name="vlan">
<?php
		foreach ($SWITCH['vlans'] as $VLAN)
		{
		        $VLAN_ID = $VLAN['vlan'];
		        $VLAN_NAME = $VLAN['name'];
		        $VLAN_STATUS = $VLAN['status'];
			$VLAN_SELECTED = ($INT_VLAN == $VLAN_ID) ? " selected=\"yes\"" : "";
		        if ($VLAN_STATUS == "active") { print "<option value=\"$VLAN_ID\"$VLAN_SELECTED>$VLAN_ID - $VLAN_NAME</option>\n"; }
		} #END of foreach for vlans
		$VLAN_SELECTED = ($INT_MODE == "trunk") ? " selected=\"yes\"" : "";
		print "<option value=\"trunk\"$VLAN_SELECTED>Trunk - All VLANs Tagged</option>\n";
?>
	 </select></td></tr>
	 <tr><td>Please enter a description for the device connected to this interface:</td></tr>
	 <tr><td><input type="text" name="description" size="50" value="<?=$INT_DESC?>"></td></tr>
	 <tr><td><input type="submit" name="go" value="Configure Port"></td></tr>
      </table>
</form>

<?php

	} #END OF FOREACH for all switches

// DONE.

$LINK = "/tools/switch-viewer.php?switch=".$SWITCH_ID;
print $HTML->footer("Back",$LINK);

?>

