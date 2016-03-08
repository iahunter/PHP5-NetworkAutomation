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

require_once "information/provisioning/device_ios_rtr.class.php";

class Provisioning_Device_IOS_RTR_WANSS	extends Provisioning_Device_IOS_RTR
{
	public $type = "Provisioning_Device_IOS_RTR_WANSS";

	public function config()
	{
		$OUTPUT = "<pre>\n";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

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

		$OUTPUT .= $this->config_loopback();

//		$OUTPUT .= $this->config_management();	// On wanrr devices we dont use dedicated mgmt interfaces, mgmt networks, mgmt vrfs, etc.

		$OUTPUT .= $this->config_motd();

		$OUTPUT .= $this->config_logging();

		$OUTPUT .= $this->config_dns();

		$OUTPUT .= $this->config_aaa();

		$OUTPUT .= $this->config_snmp();

		$OUTPUT .= $this->config_netflow();

		$OUTPUT .= $this->config_ospf();

		$OUTPUT .= $this->config_bgp();

		$OUTPUT .= $this->config_interfaces();

		$OUTPUT .= $this->config_serviceinstances();

		$OUTPUT .= "end\n";

		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

	public function config_bgp()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_BGPASN = $this->parent()->get_asn();
		$DEV_LOOP4	= $this->data['loopback4'];
		$SITE_IP4BLOCK	= $this->parent()->get_ipv4block();

		$OUTPUT .= "
ip bgp-community new-format
router bgp $DEV_BGPASN
  bgp router-id {$this->data['loopback4']}
  bgp always-compare-med
  no bgp default ipv4-unicast
  bgp log-neighbor-changes
  bgp deterministic-med

  template peer-policy PEER_POLICY_IPV4_WANRR_CLIENT
    route-reflector-client
    next-hop-self
    send-community both
   exit-peer-policy
  template peer-session PEER_SESSION_IPV4_WANRR_CLIENT
    remote-as $DEV_BGPASN
    update-source Loopback0
    fall-over
   exit-peer-session
";
/*		$ASN_DEVICES = $this->get_devices_by_asn($DEV_BGPASN);
		$RR_COUNT = count($ASN_DEVICES) - 1; // Subtract myself
		$OUTPUT .= "\n! Found $RR_COUNT other BGP devices in this ASN.\n";
		foreach ($ASN_DEVICES as $L3DEVICE)
		{
			if ($L3DEVICE->data['id'] != $this->data['id'])
			{
				$RR_LOOP4 = $L3DEVICE->data['loopback4'];
				$OUTPUT .= "  neighbor $RR_LOOP4 inherit peer-session PEER_SESSION_IPV4_WANRR_CLIENT
  address-family ipv4
    neighbor $RR_LOOP4 activate
    neighbor $RR_LOOP4 inherit peer-policy PEER_POLICY_IPV4_WANRR_CLIENT
   exit
";
			}
		}
/**/
		$SITE_IP4BLOCK_ADDR = Net_IPv4::parseAddress($SITE_IP4BLOCK)->ip;
		$SITE_IP4BLOCK_MASK = Net_IPv4::parseAddress($SITE_IP4BLOCK)->netmask;
		$OUTPUT .= "  address-family ipv4
!   network $DEV_LOOP4 mask 255.255.255.255
    aggregate-address $SITE_IP4BLOCK_ADDR $SITE_IP4BLOCK_MASK summary-only
";
		$OUTPUT .= "   exit\n";
		$OUTPUT .= " exit\n";

		return $OUTPUT;
	}

}
