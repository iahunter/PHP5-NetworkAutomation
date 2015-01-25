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

require_once "information/provisioning/serviceinstance.class.php";

class Provisioning_ServiceInstance_WAN_FlexVPN	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_WAN_FlexVPN";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text	("circuitid"	,"Internet Circuit ID"								);
		$OUTPUT .= $this->html_form_field_text	("speed"		,"Internet Service Speed (Mbps)"					);
		$SELECT = array(
			"GigabitEthernet0/2"		=> "GigabitEthernet0/2",
			"GigabitEthernet0/1"		=> "GigabitEthernet0/1",
			"GigabitEthernet0/1/0"		=> "GigabitEthernet0/1/0",
			"GigabitEthernet0/3/0"		=> "GigabitEthernet0/3/0",
		);
		$OUTPUT .= $this->html_form_field_select("interface"	,"Internet Interface",$SELECT						);
		$OUTPUT .= $this->html_form_field_text	("ceipaddress"	,"Internet IP Address/Prefix (1.2.3.6/30 OR dhcp)"	);
		$OUTPUT .= $this->html_form_field_text	("peipaddress"	,"Next-hop IP Address (1.2.3.5 OR dhcp)"			);
		$OUTPUT .= $this->html_form_field_textarea("comments"	,"Comments"											);
		$OUTPUT .= $this->html_form_field_hidden("routemap_in"	,"RM_PERMIT_ANY"									);
		$OUTPUT .= $this->html_form_field_hidden("routemap_out"	,"RM_PERMIT_LOCAL"									);
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";

		// Base FlexVPN Interface and Crypto Configuration
		if ($this->data['ceipaddress'] == "dhcp")
		{
			$IP = "dhcp";
			$NETMASK = "";
			$NEXTHOP = "dhcp";
		}else{
			$NET = Net_IPv4::parseAddress($this->data['ceipaddress']);
			$IP = $NET->ip;
			$NETMASK = $NET->netmask;
			$NEXTHOP = $this->data['peipaddress'];
		}
		$OUTPUT .= <<<END
vrf definition V999:INTERNET
  address-family ipv4
   exit-address-family
 exit

interface {$this->data['interface']}
  vrf forwarding V999:INTERNET
  ip address {$IP} {$NETMASK}
  duplex auto
  speed auto
  no ip redirects
  no ip proxy-arp
  no ip directed-broadcast
  hold-queue 4096 in
  hold-queue 4096 out
 exit

ip route vrf V999:INTERNET 0.0.0.0 0.0.0.0 {$this->data['interface']} {$NEXTHOP} name InternetFlexVPNFrontDoor

aaa authorization network AAA_FLEX_AUTH local

crypto ikev2 keyring FLEX_IKEV2_KEYRING
  peer FLEXVPN
    address 0.0.0.0 0.0.0.0
    pre-shared-key T0pS3cretKeyzomglulz
   exit
 exit

crypto ikev2 profile FLEX_IKE2_PROFILE
  match fvrf V999:INTERNET
  match identity remote address 0.0.0.0
  authentication remote pre-share
  authentication local pre-share
  keyring local FLEX_IKEV2_KEYRING
  dpd 10 3 on-demand
  aaa authorization group psk list AAA_FLEX_AUTH default
  virtual-template 1
 exit

crypto ipsec transform-set FLEX_IPSEC_TRANSFORM esp-aes esp-sha-hmac
  mode transport
 exit

crypto ipsec profile FLEX_IPSEC_PROFILE
  set transform-set FLEX_IPSEC_TRANSFORM
  set ikev2-profile FLEX_IKE2_PROFILE
 exit

interface Tunnel1
  ip mtu 1400
  ip tcp adjust-mss 1360
  ip address negotiated
  ip nhrp network-id 1
  ip nhrp holdtime 300
  ip nhrp shortcut virtual-template 1
! ip nhrp redirect
  tunnel source {$this->data['interface']}
  tunnel destination 123.456.78.1
  tunnel vrf V999:INTERNET
! tunnel path-mtu-discovery
  tunnel protection ipsec profile FLEX_IPSEC_PROFILE
 exit

interface Tunnel2
  ip mtu 1400
  ip tcp adjust-mss 1360
  ip address negotiated
  ip nhrp network-id 1
  ip nhrp holdtime 300
  ip nhrp shortcut virtual-template 1
! ip nhrp redirect
  tunnel source {$this->data['interface']}
  tunnel destination 123.456.78.2
  tunnel vrf V999:INTERNET
! tunnel path-mtu-discovery
  tunnel protection ipsec profile FLEX_IPSEC_PROFILE
 exit

interface Virtual-Template1 type tunnel
  ip mtu 1300
  ip tcp adjust-mss 1260
  ip unnumbered {$this->data['interface']}
  ip nhrp network-id 1
  ip nhrp holdtime 300
  ip nhrp shortcut virtual-template 1
! ip nhrp redirect
  tunnel vrf V999:INTERNET
! tunnel path-mtu-discovery
  tunnel protection ipsec profile FLEX_IPSEC_PROFILE
 exit


END;
		// Flex BGP Configuration
		$ASN = $this->parent()->parent()->get_asn();

		$ROUTEMAPS = array();
		$ROUTEMAPS["RM_PERMIT_ANY"] = <<<END
route-map RM_PERMIT_ANY permit 10
 exit

END;
		$ROUTEMAPS["RM_PERMIT_LOCAL"] = <<<END
ip as-path access-list 1 permit ^$
route-map RM_PERMIT_LOCAL permit 10
  match as-path 1
 exit
route-map RM_PERMIT_LOCAL deny 20
 exit

END;
		$ROUTEMAPS["RM_DENY_ANY"] = <<<END
route-map RM_DENY_ANY deny 10
 exit

END;
		if (isset($this->data['routemap_in']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_in'	]]}\n";		}
		if (isset($this->data['routemap_out']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_out'	]]}\n";	}

		$OUTPUT .= <<<END
router bgp {$ASN}
  address-family ipv4
    neighbor 10.251.248.1 remote-as 55028
    neighbor 10.251.248.1 local-as 64998
    neighbor 10.251.248.1 description FLEX_MDC_HUB
    neighbor 10.251.248.1 send-community both
	neighbor 10.251.248.1 ebgp-multihop 2
    neighbor 10.251.248.1 soft-reconfiguration inbound
    neighbor 10.251.248.1 route-map {$this->data['routemap_in' ]} in
    neighbor 10.251.248.1 route-map {$this->data['routemap_out']} out

    neighbor 10.252.248.1 remote-as 55028
    neighbor 10.252.248.1 local-as 64999
    neighbor 10.252.248.1 description FLEX_SDC_HUB
    neighbor 10.252.248.1 send-community both
	neighbor 10.252.248.1 ebgp-multihop 2
    neighbor 10.252.248.1 soft-reconfiguration inbound
    neighbor 10.252.248.1 route-map {$this->data['routemap_in' ]} in
    neighbor 10.252.248.1 route-map {$this->data['routemap_out']} out
   exit-address-family
 exit

END;
		return $OUTPUT;
	}

}
