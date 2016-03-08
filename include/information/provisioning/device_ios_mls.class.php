<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once "information/provisioning/device_ios.class.php";

class Provisioning_Device_IOS_MLS	extends Provisioning_Device_IOS
{
	public $type = "Provisioning_Device_IOS_MLS";

	public function config_interface($INTERFACE)
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEVICEID		= $this->data['id'];
		$DEV_NAME		= $this->data['name'];
		$DEV_MGMTIP4	= $this->data['mgmtip4'];
		$DEV_MGMTGW		= $this->data['mgmtgw'];
		$DEV_MGMTINT	= $this->data['mgmtint'];
		$DEV_MGMTVRF	= $this->data['mgmtvrf'];
		$DEV_LOOP4		= $this->data['loopback4'];
		$DEV_BGPASN		= $this->parent()->get_asn();

		$INT_ID			= $INTERFACE->data['id'];
		$INT_NAME		= $INTERFACE->data['name'];
		$INT_DESCRIPTION= $INTERFACE->data['description'];
		$INT_IP4		= $INTERFACE->data['ip4'];
		$INT_IP6		= $INTERFACE->data['ip6'];
		$INT_MTU		= $INTERFACE->data['mtu'];
		$INT_QOSBANDWIDTH = $INTERFACE->data['qosbandwidth'];
		$INT_QOSPOLICY	= $INTERFACE->data['qospolicy'];
		$INT_QOSREALTIME = $INTERFACE->data['qosrealtime'];
		$INT_OSPF		= $INTERFACE->data['ospf'];
		$INT_OSPFCOST	= $INTERFACE->data['ospfcost'];
		$INT_OSPFBFD	= $INTERFACE->data['ospfbfd'];
		$INT_RSVP		= $INTERFACE->data['rsvp'];
		$INT_SRLG		= $INTERFACE->data['srlg'];
		$INT_PIM		= $INTERFACE->data['pim'];
		$INT_LDP		= $INTERFACE->data['ldp'];
		$INT_LAG		= $INTERFACE->data['lag'];
		$INT_VPNID		= $INTERFACE->data['vpnid'];
		$INT_LAYER		= $INTERFACE->data['layer'];
		$INT_VLAN		= $INTERFACE->data['vlan'];
		$INT_VOICE_VLAN	= $INTERFACE->data['voicevlan'];
		$INT_SPANNINGTREE = $INTERFACE->data['spanningtree'];
		$INT_HSRPIP		= $INTERFACE->data['hsrpip'];
		$INT_HSRPPRIORITY = $INTERFACE->data['hsrppriority'];
		$INT_RAWCONFIG	= $INTERFACE->data['rawconfig'];

		if ($INT_IP4) { $INT_IP4_ADDR = Net_IPv4::parseAddress($INT_IP4)->ip;		}
		if ($INT_IP4) { $INT_IP4_MASK = Net_IPv4::parseAddress($INT_IP4)->netmask;	}

		$OUTPUT .= "! INTERFACE $INT_ID NAME $INT_NAME configuration\n";

		// pre-interface config for VLANs and VRFs etc.
		if ($INT_LAYER == "2")
		{
			if (preg_match("/^(\d+)$/",$INT_VLAN,$MATCH))
			{
				$OUTPUT .= $this->config_vlan($MATCH[1]);
			}
		}
		if ($INT_LAYER == "3")
		{
			if ($INT_VPNID)
			{
				$OUTPUT .= $this->config_vrf($INT_VPNID);
				$VPN = $this->get_vpn_by_vpnid($INT_VPNID);
				$INT_VRFNAME = "V".$VPN->data['vpnid'].":".$VPN->data['name'];

			}
			if (preg_match("/^vlan\s*(\d+)/i",$INT_NAME,$MATCH))
			{
				$OUTPUT .= $this->config_vlan($MATCH[1],$INT_DESCRIPTION);
			}
		}

		// interface config that goes under the interface config structure!
		$OUTPUT .= "interface $INT_NAME\n";
		$OUTPUT .= "  no shutdown\n";
		$OUTPUT .= "  description INT-ID $INT_ID $INT_DESCRIPTION\n";
		if ($INT_LAG)		{ $OUTPUT .= "  channel-group $INT_LAG mode active\n";					}

		if ($INT_LAYER == "2")
		{
			$OUTPUT .= "  switchport\n";
			$OUTPUT .= "  auto qos trust dscp\n";
			$OUTPUT .= "  storm-control broadcast level 0.4\n";
			$OUTPUT .= "  storm-control multicast level 0.4\n";
			if (preg_match("/^(\d+)$/",$INT_VLAN,$MATCH))
			{
				$VLAN = $MATCH[1];
				$OUTPUT .= "  switchport access vlan $VLAN\n";
				$OUTPUT .= "  switchport mode access\n";
				if ($INT_SPANNINGTREE == "host")
				{
					$OUTPUT .= "  spanning-tree portfast\n";
					$OUTPUT .= "  spanning-tree bpduguard enable\n";
				}
			}
			if ($INT_VLAN == "all")
			{
				$OUTPUT .= "  switchport\n";
				$OUTPUT .= "  switchport trunk encapsulation dot1q\n";
				$OUTPUT .= "  switchport mode trunk\n";
				if ($INT_SPANNINGTREE == "host")
				{
					$OUTPUT .= "  spanning-tree portfast trunk\n";
//					$OUTPUT .= "  spanning-tree bpduguard enable\n";
				}
			}
			if ($INT_VOICE_VLAN)
			{
				$OUTPUT .= "  switchport voice vlan $INT_VOICE_VLAN\n";
			}
		}
		if ($INT_LAYER == "3")
		{
			if (preg_match("/^vlan\s*(\d+)/i",$INT_NAME,$MATCH))
			{
				$OUTPUT .= "  ip helper-address 10.252.12.143\n";
				$OUTPUT .= "  ip helper-address 10.252.12.144\n";
			}else{
				$OUTPUT .= "  no switchport\n";
			}
			$OUTPUT .= "  no ip redirects\n";
			$OUTPUT .= "  no ip directed-broadcast\n";
			$OUTPUT .= "  no ip proxy-arp\n";
			$OUTPUT .= "  hold-queue 4096 in\n";
			$OUTPUT .= "  hold-queue 4096 out\n";
			if ($INT_VPNID)		{ $OUTPUT .= "  vrf forwarding $INT_VRFNAME\n";					}
			if ($INT_IP4)		{ $OUTPUT .= "  ip address $INT_IP4_ADDR $INT_IP4_MASK\n";			}
			if ($INT_MTU)		{ $OUTPUT .= "  mtu $INT_MTU\n";						}
			if ($INT_QOSBANDWIDTH)	{ $OUTPUT .= "  bandwidth $INT_QOSBANDWIDTH"."000\n";				}
			if ($INT_QOSPOLICY)	{ $OUTPUT .= "!QOS Template not complete for this device!\n";			}
			if ($INT_RSVP)		{ $OUTPUT .= "  ip rsvp bandwidth\n";						}
			if ($INT_SRLG)		{										}
			if ($INT_HSRPIP)
			{
				$OUTPUT .= "  standby 1 ip $INT_HSRPIP\n";
				$OUTPUT .= "  standby version 2\n";
				$OUTPUT .= "  standby 1 timers 1 4\n";
				if ($INT_HSRPPRIORITY) { $OUTPUT .= "  standby 1 priority $INT_HSRPPRIORITY\n";			}
				$OUTPUT .= "  standby 1 preempt delay minimum 30\n";
//				$OUTPUT .= "  standby 1 track 1 decrement 10\n";
//				$OUTPUT .= "  standby 1 track 2 decrement 10\n";
			}
			if ($INT_LDP)
			{
				$OUTPUT .= "  mpls ip\n";
				$OUTPUT .= "  mpls label protocol ldp\n";
				$OUTPUT .= "  mpls traffic-eng tunnels\n";
			}
			if ($INT_PIM)		{ $OUTPUT .= "  ip pim sparse-mode\n";						}
			if ($INT_OSPF)
			{
				$OUTPUT .= "  ip ospf 1 area 0\n";
				$OUTPUT .= "  ip ospf network point-to-point\n";
				$OUTPUT .= "  ip ospf hello-interval 1\n";
				if ($INT_OSPFCOST) { $OUTPUT .= "  ip ospf cost $INT_OSPFCOST\n";				}
				if ($INT_OSPFBFD)
				{
					$OUTPUT .= "  bfd interval 400 min_rx 400 multiplier 3\n";
					$OUTPUT .= "  ip ospf bfd\n";
				}
			}
		}

		if ($INT_RAWCONFIG)	{ $OUTPUT .= "$INT_RAWCONFIG\n";							}
		$OUTPUT .= " exit\n";

		// interface config that goes under OTHER config structures!
/*		if ($INT_LAYER == "3")
	        {
			if ($INT_OSPF)
			{
				$OUTPUT .= "router ospf $INT_OSPF\n";
				$OUTPUT .= "  network $INT_IP4_ADDR 0.0.0.0 area 0\n";
				$OUTPUT .= " exit\n";
			}
		}
/**/
		$OUTPUT .= "!End of interface $INT_ID name $INT_NAME config\n\n";

		return $OUTPUT;
	}

	function filter_config($CONFIG)
	{
		$LINES_IN = preg_split( '/\r\n|\r|\n/', $CONFIG );
		$LINES_OUT = array();
		$SKIP = "";
		$HOSTNAME = "";
		foreach($LINES_IN as $LINE)
		{
			// Filter out the BANNER MOTD lines
			if (preg_match("/banner \S+ (\S+)/",$LINE,$REG))   // If we encounter a banner motd or banner motd line
			{
				$SKIP = $REG[1];                  continue;     // Skip until we see this character
			}
			if ($SKIP != "" && trim($LINE) == $SKIP)            // If $SKIP is set AND we detect the end of our skip character
			{
				$SKIP = "";                       continue;     // Stop skipping and unset the character
			}
			if ($SKIP != "")                    { continue; }   // Skip until we stop skipping

			// Find the hostname to identify our prompt
			if (preg_match("/^hostname (\S+)/",$LINE,$REG)) { $HOSTNAME = $REG[1]; }
			// Filter out the prompt at the end if it exists
			if ($HOSTNAME != "" && preg_match("/^{$HOSTNAME}.+/",$LINE,$REG)) { continue; }

			// Ignore a bunch of unimportant often-changing lines that clutter up the config repository
			if (
				( trim($LINE) == ""										)	||	//	Ignore blank and whitespace-only lines
				( trim($LINE) == "exit"									)	||	//	Ignore exit lines (mostly provisioning lines)
				( preg_match('/.*no shut.*/'				,$LINE,$REG))	||	//	no shut/no shutdown lines from provisioning tool
				( preg_match('/.*no enable.*/'				,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*spanning-tree vlan 1-4094.*/',$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*enable secret.*/'			,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*ip domain.lookup.*/'		,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*ip domain.name.*/'			,$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*crypto key generate rsa.*/',$LINE,$REG))	||	//	from provisioning tool
				( preg_match('/.*log-adjacency-changes.*/'	,$LINE,$REG))	||	//	from provisioning tool
				( trim($LINE) == "end"									)	||	//	from provisioning tool
				( trim($LINE) == "wr"									)	||	//	from provisioning tool
				( trim($LINE) == "reload"								)	||	//	from provisioning tool
				( trim($LINE) == "switchport"							)	||	//	from provisioning tool
				( trim($LINE) == "snmp-server ifindex persist"			)	||	//	from provisioning tool
				( trim($LINE) == "aaa session-id common"				)	||	//	from provisioning tool
				( trim($LINE) == "ip routing"							)	||	//	from provisioning tool
				( trim($LINE) == "cdp enable"							)	||	//	from provisioning tool
				( trim($LINE) == "no ip directed-broadcast"				)	||	//	from provisioning tool
				( trim($LINE) == "no service finger"					)	||	//	from provisioning tool
				( trim($LINE) == "no service udp-small-servers"			)	||	//	from provisioning tool
				( trim($LINE) == "no service tcp-small-servers"			)	||	//	from provisioning tool
				( trim($LINE) == "no service config"					)	||	//	from provisioning tool
				( trim($LINE) == "no clock timezone"					)	||	//	from provisionnig tool
	//			( trim($LINE) == "end"									)	||	//	skip end, we dont need this yet
				( trim($LINE) == "<pre>" || trim($LINE) == "</pre>"		)	||	//	skip <PRE> and </PRE> output from html scrapes
				( substr(trim($LINE),0,1) == "!"						)	||	//	skip conf t lines
				( substr(trim($LINE),0,4) == "exit"						)	||	//	skip conf lines beginning with the word exit
				( preg_match('/.*config t.*/'				,$LINE,$REG))	||	//	skip show run
				( preg_match('/.*show run.*/'				,$LINE,$REG))	||	//	and show start
				( preg_match('/.*show startup.*/'			,$LINE,$REG))	||	//	show run config topper
				( preg_match('/^version .*/'				,$LINE,$REG))	||	//	version 12.4 configuration format
				( preg_match('/^boot-\S+-marker.*/'			,$LINE,$REG))	||	//	boot start and end markers
				( preg_match('/^Building configur.*/'		,$LINE,$REG))	||	//	ntp clock period in seconds is constantly changing
				( preg_match('/^ntp clock-period.*/'		,$LINE,$REG))	||	//	nvram config last messed up
				( preg_match('/^Current configuration.*/'	,$LINE,$REG))	||	//	current config size
				( preg_match('/.*NVRAM config last up.*/'	,$LINE,$REG))	||	//	nvram config last saved
				( preg_match('/.*uncompressed size*/'		,$LINE,$REG))	||	//	uncompressed config size
				( preg_match('/^!Time.*/'					,$LINE,$REG))		//	time comments
			   )
			{ continue; }

			// If we have UTC and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* UTC$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we have CST and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/.* CST$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we have CDT and its NOT the configuration last changed line, ignore it.
			if (
				(preg_match('/. *CDT$/'			,$LINE,$REG)) &&
				!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
			   )
			{ continue; }

			// If we find a control code like ^C replace it with ascii ^C
			$LINE = str_replace(chr(3),"^C",$LINE);

			// If we find the prompt, break out of this function, end of command output detected
			if (isset($DELIMITER) && preg_match($DELIMITER,$LINE,$REG))
			{
				break;
			}

			// If we find a line with a tacacs key in it, HIDE THE KEY!
			if ( preg_match('/(\s*server-private 10.252.12.10. timeout . key) .*/',$LINE,$REG) )
			{
				$LINE = $REG[1];	// Strip out the KEYS from a server-private line!
			}


			array_push($LINES_OUT, $LINE);
		}

		// REMOVE blank lines from the leading part of the array and REINDEX the array
		while ($LINES_OUT[0] == ""	&& count($LINES_OUT) > 2 ) { array_shift	($LINES_OUT); }

		// REMOVE blank lines from the end of the array and REINDEX the array
		while (end($LINES_OUT) == ""	&& count($LINES_OUT) > 2 ) { array_pop	($LINES_OUT); }

		// Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
		array_push($LINES_OUT, "");

		$CONFIG = implode("\n",$LINES_OUT);

		return $CONFIG;
	}

}
