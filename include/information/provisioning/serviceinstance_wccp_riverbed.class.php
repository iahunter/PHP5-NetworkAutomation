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

class Provisioning_ServiceInstance_WCCP_Riverbed	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_WCCP_Riverbed";

	public function html_form_extended()
	{
		$OUTPUT = "";
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";
		$OUTPUT .= Utility::last_stack_call(new Exception);
		$ASN = $this->parent()->parent()->get_asn();	
		$IPV4BLOCK = $this->parent()->parent()->get_ipv4block();

		$IPV4NETWORK= Net_IPv4::parseAddress($IPV4BLOCK)->ip;
		$IPV4LONG	= ip2long($IPV4NETWORK);
		$IPV4LONG += 4088; // Get us to the last /29 of our last /24 for Riverbed WCCP network space
		$IPV4SUBNET	= long2ip($IPV4LONG);
		$HSRPIP		= long2ip($IPV4LONG + 1);
		if (preg_match("/.*swd0\d*[13579]$/i",$this->parent()->data['name'],$MATCH))
		{
			$IPADDRESS			= long2ip($IPV4LONG + 2);
			$HSRPPRIORITY		= 105;
		}else{
			$IPADDRESS			= long2ip($IPV4LONG + 3);
			$HSRPPRIORITY		= 100;
		}

		$OUTPUT .= <<<END
!!!!!!!!!!!!
!!! STOP !!!
!!!!!!!!!!!!
! DO NOT APPLY THIS CONFIGURATION UNTIL THE RIVERBED HAS BEEN CONFIGURED OR THE WAN0_0 INTERFACE IS SHUTDOWN!
!!!!!!!!!!!!

ip wccp version 2
ip wccp source-interface Vlan17
ip wccp 61

interface GigabitEthernet0/1
  ip wccp 61 redirect in
 exit

interface GigabitEthernet0/2
  ip wccp 61 redirect in
 exit

interface Vlan 1
  ip wccp 61 redirect in
 exit

interface Vlan 5
  ip wccp 61 redirect in
 exit

vlan 17
  name RIVERBED_WCCP_{$IPV4SUBNET}/29
 exit

interface GigabitEthernet0/3
  no switchport trunk encapsulation dot1q
  switchport mode access
  switchport access vlan 17
 exit

interface Vlan 17
  description RIVERBED_{$IPV4SUBNET}/29
  ip address {$IPADDRESS} 255.255.255.248
  no ip redirects
  no ip proxy-arp
  standby version 2
  standby 1 ip {$HSRPIP}
  standby 1 timers 1 4
  standby 1 priority {$HSRPPRIORITY}
  standby 1 preempt delay minimum 30
  hold-queue 4096 in
  hold-queue 4096 out
  no shutdown
 exit

router bgp {$ASN}
  address-family ipv4
    network {$IPV4SUBNET} mask 255.255.255.248
   exit
 exit

END;

		return $OUTPUT;
	}

}
