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

require_once "information/provisioning/device.class.php";

class Provisioning_Device_ArubaOS	extends Provisioning_Device
{
	public $type = "Provisioning_Device_ArubaOS";

	public function config_logging()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
logging 10.0.192.130 severity informational
";
		return $OUTPUT;
	}

	public function config_aaa()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= "
enable bypass
";
		return $OUTPUT;
	}

	public function config_management()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_MGMTIP4    = $this->data['mgmtip4'];
		$DEV_MGMTGW     = $this->data['mgmtgw'];
		$DEV_MGMTINT    = $this->data['mgmtint'];

		$DEV_MGMTIP4_ADDR = Net_IPv4::parseAddress($DEV_MGMTIP4)->ip;
		$DEV_MGMTIP4_MASK = Net_IPv4::parseAddress($DEV_MGMTIP4)->netmask;

		$OUTPUT .= <<<END
mgmt-server profile "AMP"
mgmt-server type amp primary-server 10.252.12.126 profile AMP

END;
		return $OUTPUT;
	}

	public function config_snmp()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$SITENAME = $this->parent()->data['name'];
		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= <<<END
! We need an ACL on here the same as everything else
snmp-server community "NetworkRO"
snmp-server host 10.252.12.126 version 2c NetworkRO udp-port 162
! SNMP Trap reciever netman via lancope flow replicator
snmp-server host 10.0.192.130 version 2c public udp-port 162

END;
		return $OUTPUT;
	}

	public function config_tunnel()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= <<<END
! Override this function in the child objects to build appropriate tunnels for this controller!

END;
		return $OUTPUT;
	}

	public function config_ospf()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Aruba OS does not support OSPF
";
		return $OUTPUT;
	}

	public function config_mpls()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Aruba OS does not support MPLS
";
		return $OUTPUT;
	}

	public function config_multicast()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Aruba OS does not require configuration for multicast
";
		return $OUTPUT;
	}

	public function config_bgp()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_BGPASN = $this->parent()->get_asn();

		$OUTPUT .= "
! Aruba OS does not run BGP
";
		return $OUTPUT;
	}

	public function config_vrf($VPNID)
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Aruba OS does not leverage VRFs, we might need to figure out what contextualized routing is called though?
";
		return $OUTPUT;
	}

	public function config_vlan($VLANID, $VLANNAME = "")
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Standard site WLC VLAN definitions for all controllers... may need to override in children
vlan 5 Users-WLAN
vlan 9 VoIP
vlan 902 Guest
";
		return $OUTPUT;
	}

	public function config_spanningtree()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= <<<END
! STP disabled globally but also under interface configuration
no spanning-tree
! Hard set for now, will investigate adding child interface objects later.
interface gigabitethernet 0/0/0
        description "uplink to SWITCH ID"
        trusted
        trusted vlan 1,5,9
        switchport mode trunk
        switchport trunk allowed vlan 1,5,9
        no spanning-tree
        lldp transmit
        lldp receive

END;
		return $OUTPUT;
	}

	public function config_motd()
	{
		$OUTPUT = "
! TBD, does this stuff support banner MOTD's?
";
		return $OUTPUT;
	}

	public function config_loopback()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! Aruba WLC's do not contain loopback devices
";
		return $OUTPUT;
	}

	public function config_dns()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= "
! How do we configure DNS resolution on these things?
";
		return $OUTPUT;
	}

	public function config_qos($SPEED,$INTERFACE)
	{
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
! No platform QOS is configured on ArubaOS currently
";
		return $OUTPUT;
	}

}
