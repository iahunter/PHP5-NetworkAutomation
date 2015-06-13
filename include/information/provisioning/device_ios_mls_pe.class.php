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

require_once "information/provisioning/device_ios_mls.class.php";

class Provisioning_Device_IOS_MLS_PE	extends Provisioning_Device_IOS_MLS
{
	public $type = "Provisioning_Device_IOS_MLS_DIST";

	public function config()
	{
		$OUTPUT = "<pre>\n";
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$OUTPUT .= "! Found Device ID ".$this->data['id']." of type ".get_class($this)."\n\n";

		$DEV_NAME       = $this->data['name'];
		$DEV_MGMTIP4    = $this->data['mgmtip4'];
		$DEV_MGMTGW     = $this->data['mgmtgw'];
		$DEV_MGMTINT    = $this->data['mgmtint'];
		$DEV_MGMTVRF    = $this->data['mgmtvrf'];
		$DEV_LOOP4      = $this->data['loopback4'];
		$DEV_RAWCONFIG  = $this->data['rawconfig'];

		$OUTPUT .= "config t\n\n";

		//Pre-interface configuration tasks. Standard chunks.

		$OUTPUT .= "hostname $DEV_NAME\n";

		$OUTPUT .= "ip routing\n";

		$OUTPUT .= "ip cef\n";

		$OUTPUT .= $this->config_management();

		$OUTPUT .= $this->config_loopback();

		$OUTPUT .= $this->config_motd();

		$OUTPUT .= $this->config_dns();

		$OUTPUT .= $this->config_logging();

		$OUTPUT .= $this->config_spanningtree();

		$OUTPUT .= $this->config_aaa();

		$OUTPUT .= $this->config_snmp();

		$OUTPUT .= $this->config_ospf();

		$OUTPUT .= $this->config_mpls();

		$OUTPUT .= $this->config_multicast();

		$OUTPUT .= $this->config_bgp();

		$OUTPUT .= <<<END

mac-address-table aging-time 14400

END;


		$OUTPUT .= $this->config_interfaces();

		$OUTPUT .= $this->config_serviceinstances();

		$OUTPUT .= "end\n";

		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

	public function config_bgp()
	{
		$OUTPUT = "";
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$DEV_BGPASN = $this->parent()->get_asn();
		$DEV_LOOP4	= $this->data['loopback4'];

		$OUTPUT .= "
ip bgp-community new-format
router bgp $DEV_BGPASN
  bgp router-id {$this->data['loopback4']}
  bgp always-compare-med
  no bgp default ipv4-unicast
  bgp log-neighbor-changes
  bgp deterministic-med

  template peer-policy PEER_POLICY_VPNV4_RR
    send-community both
   exit-peer-policy
  template peer-session PEER_SESSION_VPNV4_RR
    remote-as $DEV_BGPASN
    update-source Loopback0
   exit-peer-session

  template peer-policy PEER_POLICY_VPNV6_RR
    send-community both
   exit-peer-policy
  template peer-session PEER_SESSION_VPNV6_RR
    remote-as $DEV_BGPASN
    update-source Loopback0
   exit-peer-session

";
		$ASN_DEVICES = $this->get_devices_by_asn($DEV_BGPASN);
		$RR_COUNT = count($ASN_DEVICES);
		$OUTPUT .= "\n! Found $RR_COUNT layer 3 devices in this ASN.\n";
		// Loop through each VPNv4 route reflector
		foreach ($ASN_DEVICES as $L3DEVICE)
		{
			$REGEX = "/VPNRR_/";
			if (preg_match($REGEX,$L3DEVICE->data['type'],$REG))
			{
				$RR_LOOP4 = $L3DEVICE->data['loopback4'];
				$OUTPUT .= "  neighbor $RR_LOOP4 inherit peer-session PEER_SESSION_VPNV4_RR
  address-family vpnv4
    neighbor $RR_LOOP4 activate
    neighbor $RR_LOOP4 inherit peer-policy PEER_POLICY_VPNV4_RR
   exit
  address-family ipv4 mdt
    neighbor $RR_LOOP4 activate
    neighbor $RR_LOOP4 inherit peer-policy PEER_POLICY_VPNV4_RR
   exit
";
			}
		}
		// Loop through VPNv6 route reflectors
		foreach ($ASN_DEVICES as $L3DEVICE)
		{
			$REGEX = "/VPN6RR_/";
			if (preg_match($REGEX,$L3DEVICE->data['type'],$REG))
			{
				$RR_LOOP4 = $L3DEVICE->data['loopback4'];
				$OUTPUT .= "  neighbor $RR_LOOP4 inherit peer-session PEER_SESSION_VPNV6_RR
  address-family vpnv6
    neighbor $RR_LOOP4 activate
    neighbor $RR_LOOP4 inherit peer-policy PEER_POLICY_VPNV6_RR
   exit
";
			}
		}
		$OUTPUT .= " exit\n";

		return $OUTPUT;
	}

	public function config_interface($INTERFACE)
	{
		$OUTPUT = "";
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
				$OUTPUT .= "  switchport mode trunk\n";
				if ($INT_SPANNINGTREE == "host")
				{
					$OUTPUT .= "  spanning-tree portfast trunk\n";
//					$OUTPUT .= "  spanning-tree bpduguard enable\n";
				}
			}
		}
		if ($INT_LAYER == "3")
		{
			if (!preg_match("/^vlan\s*(\d+)/i",$INT_NAME,$MATCH))
			{
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
	
}

?>
