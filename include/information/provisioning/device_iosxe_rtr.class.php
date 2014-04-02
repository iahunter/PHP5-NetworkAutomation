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

require_once "information/provisioning/device_iosxe.class.php";

class Provisioning_Device_IOSXE_RTR	extends Provisioning_Device_IOSXE
{
	public $type = "Provisioning_Device_IOSXE_RTR";

	public function config_interface($INTERFACE)
	{
		$OUTPUT = "";

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



		$INT_RAWCONFIG	= $INTERFACE->data['rawconfig'];

		if ($INT_IP4) { $INT_IP4_ADDR = Net_IPv4::parseAddress($INT_IP4)->ip;		}
		if ($INT_IP4) { $INT_IP4_MASK = Net_IPv4::parseAddress($INT_IP4)->netmask;	}

		$OUTPUT .= "! INTERFACE $INT_ID NAME $INT_NAME configuration\n";

		// pre-interface config for VLANs and VRFs etc.
		if ($INT_LAYER == "2")
		{
			$OUTPUT .= "!Layer 2 config missing from this template!\n";






		}
		if ($INT_LAYER == "3")
		{
			if ($INT_VPNID)
			{
				$OUTPUT .= $this->config_vrf($INT_VPNID);
				$VPN = $this->get_vpn_by_vpnid($INT_VPNID);
				$INT_VRFNAME = "V".$VPN->data['vpnid'].":".$VPN->data['name'];

			}







		}

		// interface config that goes under the interface config structure!
		$OUTPUT .= "interface $INT_NAME\n";
		$OUTPUT .= "  no shutdown\n";
		$OUTPUT .= "  cdp enable\n";
		$OUTPUT .= "  description INT-ID $INT_ID $INT_DESCRIPTION\n";
		if ($INT_LAG)		{ $OUTPUT .= "  channel-group $INT_LAG mode active\n";					}

		if ($INT_LAYER == "2")
		{
			$OUTPUT .= "!Layer 2 config missing from this template!\n";














		}
		if ($INT_LAYER == "3")
		{

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

	public function config_dns()
	{
        $OUTPUT = "";
        $OUTPUT .= Utility::last_stack_call(new Exception);
        $OUTPUT .= "
ip domain-lookup
ip domain retry 0
ip domain timeout 1
ip name-server 10.252.26.4
ip name-server 10.252.26.5

";
		return $OUTPUT;
	}

}

?>
