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

class Provisioning_ServiceInstance_WAN_4G_LTE_FlexVPN	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_WAN_4G_LTE_FlexVPN";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text	("circuitid"	,"Internet Circuit ID"								);
		$OUTPUT .= $this->html_form_field_text	("speed"		,"Internet Service Speed (Mbps)"					);
		$SELECT = array(
			"Cellular0/0/0"			=> "Cellular0/0/0",
			"Cellular0/3/0"			=> "Cellular0/3/0",
		);
		$OUTPUT .= $this->html_form_field_select("interface"	,"Internet Interface",$SELECT						);
		//$OUTPUT .= $this->html_form_field_text	("ceipaddress"	,"Internet IP Address/Prefix (1.2.3.6/30 OR dhcp)"	);
		//$OUTPUT .= $this->html_form_field_text	("peipaddress"	,"Next-hop IP Address (1.2.3.5 OR dhcp)"			);
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
		

do cellular 0/0/0 lte technology auto

do cellular 0/0/0 lte profile create 1 10791.mcs none



interface cellular0/0/0
 shut
interface cellular0/0/1
 shut
interface cellular0/0/2
 shut
interface cellular0/0/3
 shut
interface cellular0/0/4
 shut
interface cellular0/0/5
 shut


controller cellular 0/0
  lte sim profile 1 ims 1
  !for 1921, use "lte sim data-profile 1 attach-profile 1"
  lte gps mode standalone
  lte gps nmea
 exit
 
chat-script lte "" "AT!CALL" TIMEOUT 60 "OK" 

dialer watch-list 1 ip 5.6.7.8 0.0.0.0
dialer watch-list 1 delay route-check initial 60
dialer watch-list 1 delay connect 1

vrf definition V999:INTERNET
  address-family ipv4
   exit-address-family
 exit

interface {$this->data['interface']}
  vrf forwarding V999:INTERNET
  ip address negotiated
  ip nat outside
  ip virtual-reassembly in
  encapsulation slip
  dialer in-band
  dialer string lte
  dialer watch-group 1
  async mode interactive
  routing dynamic
  no shutdown
 exit
 
 
ip access-list extended NATALLOWED
 deny   ip any 10.0.0.0 0.255.255.255
 deny   ip any 172.16.0.0 0.15.255.255
 deny   ip any 192.168.0.0 0.0.255.255
 permit ip 10.0.0.0 0.255.255.255 any
 permit ip 172.16.0.0 0.15.255.255 any
 permit ip 192.168.0.0 0.0.255.255 any

ip nat inside source list NATALLOWED interface {$this->data['interface']} overload

ip route vrf V999:INTERNET 0.0.0.0 0.0.0.0 {$this->data['interface']} {$NEXTHOP} name InternetFlexVPNFrontDoor

aaa authorization network AAA_FLEX_AUTH local

crypto ikev2 keyring FLEX_IKEV2_KEYRING
  peer FLEXVPN
    address 0.0.0.0 0.0.0.0
    pre-shared-key awesomesauce123
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
  tunnel source {$this->data['interface']}
  tunnel destination 123.456.78.1
  tunnel vrf V999:INTERNET
  tunnel protection ipsec profile FLEX_IPSEC_PROFILE
 exit

interface Tunnel2
  ip mtu 1400
  ip tcp adjust-mss 1360
  ip address negotiated
  tunnel source {$this->data['interface']}
  tunnel destination 123.456.78.2
  tunnel vrf V999:INTERNET
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
    neighbor 10.251.248.1 remote-as 12345
    neighbor 10.251.248.1 local-as 64998
    neighbor 10.251.248.1 description FLEX_MDC_HUB
    neighbor 10.251.248.1 send-community both
    neighbor 10.251.248.1 soft-reconfiguration inbound
    neighbor 10.251.248.1 route-map {$this->data['routemap_in' ]} in
    neighbor 10.251.248.1 route-map {$this->data['routemap_out']} out

    neighbor 10.252.248.1 remote-as 12345
    neighbor 10.252.248.1 local-as 64999
    neighbor 10.252.248.1 description FLEX_SDC_HUB
    neighbor 10.252.248.1 send-community both
    neighbor 10.252.248.1 soft-reconfiguration inbound
    neighbor 10.252.248.1 route-map {$this->data['routemap_in' ]} in
    neighbor 10.252.248.1 route-map {$this->data['routemap_out']} out
   exit-address-family
 exit

END;
		return $OUTPUT;
	}

}
