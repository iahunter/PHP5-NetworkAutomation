<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once "/etc/networkautomation/networkautomation.inc.php";

	$DEVICEID = $_GET['device'];
	$DEBUG = $_GET['debug'];	if ($DEBUG>0) { print "<h2>TOOL RUNNING IN DEBUG MODE, NO CONFIGURATION CHANGES WILL BE MADE!</h2>\n"; }
	$SWITCHINFO = array();

	$command = new command($DEVICEID);

	$cli = $command->getcli();

	if (!$cli->connected)
	{
		print "Error, could not connect to switch id $DEVICEID!\n";
		$DB->log("Could not connect to switch {$DEVICEID}",1);
		exit;
	}

	$PROMPT = $cli->prompt;

	$MESSAGE = "Viewed Switch $PROMPT ID $DEVICEID";
	$DB->log($MESSAGE);

	$SWITCHINFO[$PROMPT]['prompt'] = $PROMPT;
	$cli->exec("terminal length 0");

	$COMMAND = "show running";
	$SWITCHINFO[$PROMPT]['showrun']		= $cli->exec($COMMAND);

	$COMMAND = "show int status";
	$SWITCHINFO[$PROMPT]['showintstatus']	= $cli->exec($COMMAND);

	$COMMAND = "sh mac address-table dynamic";
	$SWITCHINFO[$PROMPT]['showmac'] 	= $cli->exec($COMMAND);

	$COMMAND = "show vlan brief | I active|Name";
	$SWITCHINFO[$PROMPT]['showvlan']	= $cli->exec($COMMAND);

	$cli->disconnect();

	### Parse and generate output
	###============================================

	$PORTS_UP    = array();
	$PORTS_DOWN  = array();

	foreach ($SWITCHINFO as $SWITCH)
	{
		$CISCO = new \metaclassing\Cisco();

		$CISCO->parse_config(explode("\n",$SWITCH['showrun']));

		$SNMPLOCATION = "";
		foreach (explode("\n",$SWITCH['showrun']) as $LINE)
		{
			if (preg_match('/snmp-server location (.+)/',$LINE,$MATCH))
			{
				$SNMPLOCATION = $MATCH[1];
			}
		}

		$SWITCH['vlans'] = array();

		$CISCO->parse_interface_status($SWITCH['showintstatus']);

		$SWITCH['vlans'] = $CISCO->parse_space_delimited_command($SWITCH['showvlan']);

		$SWITCH['mactable'] = $CISCO->parse_mac_address_table(explode("\n",$SWITCH['showmac']));

		$SWITCH['interfaces'] = $CISCO->config('interface');

		if (!$SWITCH['interfaces'])
		{
			print "<p>Could not get interfaces from ".$SWITCH['prompt']."!<br>\n";
			$DB->log("Could not get interfaces from {$DEVICEID} {$SWITCH["prompt"]}",1);
//			exit;
		}

		$WIDTH = array();
		$WIDTH[1] = 130; // Port / Interface Name
		$WIDTH[2] = 70;  // Status
		$WIDTH[3] = 50;  // VLAN
		$WIDTH[4] = 60;  // Speed
		$WIDTH[5] = 60;  // Duplex
		$WIDTH[6] = 500; // Description
		$WIDTH[7] = 170; // Mac Addresses
		$WIDTH[8] = 20;  // Edit Link
		$WIDTH[0] = array_sum($WIDTH);

print <<<END
	<table class="report" width="{$WIDTH[0]}">
	<caption class="report">{$SWITCH['prompt']}
		<font style="font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-variant: normal; font-weight: normal;">- Location: {$SNMPLOCATION}</font>
					<a href="/tools/switch-edit-snmplocation.php?device=$DEVICEID" target="_blank">
						<img src="/images/pencil.png" height="12" width="12" style="margin-top: 4px;" alt="Edit SNMP Location">
					</a>
	</caption>
	<thead>
		<tr>
			<th class="report" width="{$WIDTH[1]}">Port</th>
			<th class="report" width="{$WIDTH[2]}">Status</th>
			<th class="report" width="{$WIDTH[3]}">VLAN</th>
			<th class="report" width="{$WIDTH[4]}">Speed</th>
			<th class="report" width="{$WIDTH[5]}">Duplex</th>
			<th class="report" width="{$WIDTH[6]}">Description</th>
			<th class="report" width="{$WIDTH[7]}">MAC Addresses {VLAN}</th>
			<th class="report" width="{$WIDTH[8]}"></th>
		</tr>
	</thead>
	<tbody class="report">

END;
		$i=1;
		foreach ($SWITCH['interfaces'] as $INT_NAME => $idata)
		{
			if (!preg_match('/^(Fa|Gi|Ten|Eth)/',$INT_NAME,$match)) { continue; }
			$INTERFACE = $CISCO->iface($INT_NAME);

			$INT_DESC = $INTERFACE['desc'];
			$INT_STAT = $INTERFACE['status'];

			if ($INT_STAT == "connected") { $PORTS_UP[$SWITCH['prompt']]++; }else{ $PORTS_DOWN[$SWITCH['prompt']]++; }

			$INT_MODE = $INTERFACE['mode'];
			$INT_VLAN_ACC = ($INTERFACE['vlan.access']) ? $INTERFACE['vlan.access'] : "";
			$INT_VLAN_TRK = ($INTERFACE['vlan.trunk'])  ? $INTERFACE['vlan.trunk']  : "";
			if($INT_MODE == ""      ) { $INT_MODE = "access";							}
			if($INT_MODE == "access") { $INT_VLAN = ($INT_VLAN_ACC != "") ? $INT_VLAN_ACC : "none";			}
			if($INT_MODE == "trunk" ) { $INT_VLAN = ($INT_VLAN_TRK != "") ? implode(', ',$INT_VLAN_TRK) : "all";	}
			if($INTERFACE['status'] == "monitoring" ) { $INT_VLAN = "span";						}
			$INT_SPEE = $INTERFACE['speed'];
			$INT_DUPL = $INTERFACE['duplex'];
			$INT_MACS = "none";
			if (isset($SWITCH['mactable'][$INT_NAME]))
			{
				$INT_MACS = "";
				foreach ($SWITCH['mactable'][$INT_NAME] as $MAC => $VLAN)
				{
					$INT_MACS .= $MAC . " {" . $VLAN . "} ";
				}
			}

			$INT_ROLE = (   $INT_NAME == "GigabitEthernet0/49" ||
					$INT_NAME == "GigabitEthernet0/50" ||
					$INT_NAME == "GigabitEthernet0/51" ||
					$INT_NAME == "GigabitEthernet0/52" ||
					$INT_NAME == "TenGigabitEthernet0/49" ||
					$INT_NAME == "TenGigabitEthernet0/50" ||
					$INT_NAME == "TenGigabitEthernet0/51" ||
					$INT_NAME == "TenGigabitEthernet0/52" ||
					$INT_NAME == "TenGigabitEthernet1/49" ||
					$INT_NAME == "TenGigabitEthernet1/50" ||
					$INT_NAME == "TenGigabitEthernet1/51" ||
					$INT_NAME == "TenGigabitEthernet1/52" ) ? "upstream" : "downstream";

			$INT_ROLE = (	$INT_NAME == "FastEthernet0" )	? "management" : $INT_ROLE;
			$INT_ROLE = (	$INT_NAME == "FastEthernet1" )	? "management" : $INT_ROLE;
//			$INT_ROLE = (	$INT_MODE == "routed" )		? "management" : $INT_ROLE;
			$INT_ROLE = (	$INT_MODE == "fex-fabric" )	? "fex-fabric" : $INT_ROLE;

			$COLOR = array();
			$COLOR[1] = "";
			$COLOR[2] = (($INTERFACE['status'] == 'connected')  ? 'green' :
		                 (($INTERFACE['status'] == 'disabled')   ? 'orange' :
		                 (($INTERFACE['status'] == 'errdisable') ? 'red' : 'silver')));
			$COLOR[3] = "";
			$COLOR[4] = (($INT_SPEE[0] != "a")&&($INT_SPEE[0] != "A") && !preg_match('/^(Ten)/',$INT_NAME,$match)) ? "red" : "";
			$COLOR[5] = (($INT_DUPL[0] != "a")&&($INT_DUPL[0] != "A") && !preg_match('/^(Ten)/',$INT_NAME,$match)) ? "red" : "";
			$COLOR[6] = ($INT_DESC    == ""     && $INT_STAT == "connected")  ? "red" : "";
			$COLOR[7] = ($INT_MACS    == "none" && $INT_STAT == "connected")  ? "red" : "";
			$COLOR[8] = "";

			if ($INT_ROLE == "downstream")
			{
				$i++; $rowclass = "row".(($i % 2)+1);
				print "<tr class='".$rowclass."'>
					<td width=\"".$WIDTH[1]."\" class=\"report ".$COLOR[1]."\">$INT_NAME&nbsp;</td>
					<td width=\"".$WIDTH[2]."\" class=\"report ".$COLOR[2]."\">$INT_STAT&nbsp;</td>
					<td width=\"".$WIDTH[3]."\" class=\"report ".$COLOR[3]."\">$INT_VLAN&nbsp;</td>
					<td width=\"".$WIDTH[4]."\" class=\"report ".$COLOR[4]."\">$INT_SPEE&nbsp;</td>
					<td width=\"".$WIDTH[5]."\" class=\"report ".$COLOR[5]."\">$INT_DUPL&nbsp;</td>
					<td width=\"".$WIDTH[6]."\" class=\"report ".$COLOR[6]."\">$INT_DESC&nbsp;</td>
					<td width=\"".$WIDTH[7]."\" class=\"report ".$COLOR[7]."\">$INT_MACS&nbsp;</td>
					<td width=\"".$WIDTH[8]."\" class=\"report ".$COLOR[8]."\">
					\n";
				if ( PERMISSION_CHECK("tool.switch.edit") )
				{
					print <<<END
					<a href="/tools/switch-edit3.php?device=$DEVICEID&interface=$INT_NAME&" target="_blank">
						<img src="/images/pencil.png" height="12" width="12">
					</a>
END;
				}else{
					print "Denied";
				}
				print "</td></tr>\n";
			}
		} #END of foreach for interfaces
		print "</tbody></table>\n";
		print "<br>\n";

		$WIDTH = array();
		$WIDTH[1] = 100; // VLAN ID
		$WIDTH[2] = 400; // VLAN Name
		$WIDTH[0] = array_sum($WIDTH);

print "<table class=\"report\" width=".$WIDTH[0].">
	<caption class=\"report\">VLANs On ".$SWITCH['prompt']."</caption>
        <thead>
                <tr>
                        <th class=\"report\" width=".$WIDTH[1].">VLAN ID</th>
                        <th class=\"report\" width=".$WIDTH[2].">VLAN Name</th>
                </tr>
        </thead>
        <tbody class=\"report\">\n";
		$i=1;
		foreach ($SWITCH['vlans'] as $VLAN)
		{
			$VLAN_ID = $VLAN['vlan'];
			$VLAN_NAME = $VLAN['name'];
			$VLAN_STATUS = $VLAN['status'];

			if ($VLAN_STATUS == "active")
			{
				$i++; $rowclass = "row".(($i % 2)+1);
				print "<tr class='".$rowclass."'>
					<td class=\"report\">$VLAN_ID</td>
					<td class=\"report\">$VLAN_NAME</td>
					</tr>\n";
			}

		} #END of foreach for vlans
		print "</tbody></table><br><hr style=\"border: 0; color: #ccc; background-color: #aaa; height: 1px;\"><br>\n";

	} #END OF FOREACH for all switches

	$WIDTH = array();
	$WIDTH[1] = 200; // Switch
	$WIDTH[2] = 50;  // Ports Up
	$WIDTH[3] = 50;  // Ports Down
	$WIDTH[4] = 50;  // Ports Total
	$WIDTH[0] = array_sum($WIDTH);

print "<table class=\"report\" width=".$WIDTH[0].">
	<caption class=\"report\">Port Utilization Statistics</caption>
        <thead>
                <tr>
                        <th class=\"report\" width=".$WIDTH[1].">Switch</th>
                        <th class=\"report\" width=".$WIDTH[2].">Up</th>
                        <th class=\"report\" width=".$WIDTH[3].">Down</th>
                        <th class=\"report\" width=".$WIDTH[4].">Total</th>
                </tr>
        </thead>
	<tbody class=\"report\">\n";

	$i=1;
	foreach ($PORTS_UP as $SWITCH => $PORTS)
	{
		$UP	= $PORTS_UP[$SWITCH];
		$DOWN	= $PORTS_DOWN[$SWITCH];
		$TOTAL	= $UP + $DOWN;
		$i++; $rowclass = "row".(($i % 2)+1);
		print "<tr class='".$rowclass."'>
			<td class=\"report\">$SWITCH</td>
			<td class=\"report\">$UP</td>
			<td class=\"report\">$DOWN</td>
			<td class=\"report\">$TOTAL</td>
		</tr>\n";
	} #END of forewach for switch port statistics.
	print "</tbody><tfoot>\n";
	$UP	= array_sum($PORTS_UP);
	$DOWN	= array_sum($PORTS_DOWN);
	$TOTAL	= $UP + $DOWN;
	print "<tr>
		<td class=\"report\">Total For All Switches</td>
		<td class=\"report\">$UP</td>
		<td class=\"report\">$DOWN</td>
		<td class=\"report\">$TOTAL</td>
	</tr>\n";
	print "</tfoot></table>\n";
?>
<font color="777777" face="arial" style="font-size:6pt;">Loaded in <?=$HTML->timer_diff()?> seconds.</font>
