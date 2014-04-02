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

class Provisioning_ServiceInstance_WAN_T1	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_WAN_T1";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$SELECT = array(
			"Verizon"		=> "Verizon",
			"CenturyLink"	=> "CenturyLink",
			"Telus"			=> "Telus",
			"Telus65001"	=> "Telus65001",
		);
		$OUTPUT .= $this->html_form_field_select("provider"		,"Service Provider",$SELECT);
		$OUTPUT .= $this->html_form_field_text	("circuitid"	,"Carrier Circuit ID"		);
		$OUTPUT .= $this->html_form_field_text	("ceipaddress"	,"CE IP Address (1.2.3.4)");
		$SELECT = array(
			"1"	=> "1",
			"2"	=> "2",
			"3"	=> "3",
			"4" => "4",
			"5" => "5",
			"6" => "6",
			"7" => "7",
			"8" => "8",
		);
		$OUTPUT .= $this->html_form_field_select("t1count"		,"T1 Count",$SELECT);
		$OUTPUT .= $this->html_form_field_textarea("comments"	,"Comments");
		$OUTPUT .= $this->html_form_field_hidden("routemap_in"	,"RM_PERMIT_ANY");
		$OUTPUT .= $this->html_form_field_hidden("routemap_out"	,"RM_PERMIT_LOCAL");
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";
		$ASN = $this->parent()->parent()->get_asn();
		$MBPS = 1.5  * $this->data['t1count'];
		$KBPS = 1536 * $this->data['t1count'];

		// Configure T1 cards, controllers, and channel groups
		$T1RANGE = range(0,$this->data['t1count'] - 1);
		foreach($T1RANGE as $T1)
		{
			$CARD = floor($T1 / 2);
			$PORT = floor($T1 % 2);
			if(!$PORT)	// if this is the first port on a new card, print out the card type
			{
				$OUTPUT .= "card type t1 0 {$CARD}\n";
			}
			$OUTPUT .= "controller T1 0/{$CARD}/{$PORT}\n";
			$OUTPUT .= "  channel-group 0 timeslots 1-24\n";
		}

		// Configure T1 interfaces
		$OUTPUT .= "\n";
		if ($this->data['t1count'] == 1)	// For exactly ONE T1, we just configure a serial interface and put our IP there
		{
			$this->data["interface"] = "Serial0/0/0:0";
			if ($this->data['provider'] == "Verizon")		// For verizon, we always run frame-relay
			{
				$OUTPUT .= <<<END
interface Serial0/0/0:0
  no ip address
  encapsulation frame-relay IETF
  no shutdown
 exit

interface Serial0/0/0:0.100 point-to-point
  description WAN_MPLS_$MPLSPROVIDER_{$MBPS}Mbps_$CIRCUITID
  ip address {$this->data['ceipaddress']} 255.255.255.252
  bandwidth {$KBPS}
  no ip redirects
  snmp trap link-status
  frame-relay interface-dlci 100 IETF
  no shutdown
 exit

END;
			}
			if ($this->data['provider'] == "CenturyLink")	// For CenturyLink, we always run HDLC
			{
				$OUTPUT .= <<<END
interface Serial0/0/0:0
  description WAN_MPLS_{$this->data['provider']}_{$MBPS}Mbps_{$this->data['circuitid']}
  bandwidth {$KBPS}
  ip address {$this->data['ceipaddress']} 255.255.255.252
  bandwidth {$KBPS}
  no ip redirects
  no shutdown
 exit

END;
			}
		}else{	// For MULTIPLE T1 interfaces, we need to create a multilink bundle and put our IP there
			$this->data["interface"] = "Multilink1";
			$OUTPUT .= <<<END
interface Multilink1
  description WAN_MPLS_{$this->data['provider']}_{$MBPS}Mbps_{$this->data['circuitid']}
  ip address {$this->data['ceipaddress']} 255.255.255.252
  bandwidth {$KBPS}
  no ip redirects
  ppp multilink
  ppp multilink group 1
  ppp multilink fragment disable
  no shutdown
  ppp chap hostname {$this->data['circuitid']}
 exit

END;
			// Configure member serial interfaces
			$T1RANGE = range(0,$this->data['t1count'] - 1);
			foreach($T1RANGE as $T1)
			{
				$CARD = floor($T1 / 2);
				$PORT = floor($T1 % 2);
				$OUTPUT .= <<<END
interface Serial0/{$CARD}/{$PORT}:0
  no ip address
  encapsulation ppp
  ppp multilink
  ppp multilink group 1
  no shutdown
  ppp chap hostname {$this->data['circuitid']}
 exit

END;
			}
		}

		// Configure BGP
		$OUTPUT .= "\n";
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
		if (isset($this->data['routemap_in']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_in'	]]}\n"; }
		if (isset($this->data['routemap_out']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_out'	]]}\n"; }

		// Set carrier peer IP and ASN
		$this->data['peer_ip']	= long2ip(ip2long($this->data['ceipaddress']) - 1);	// PE is always 1 below the CE IP
		$PROVIDERASN = array();
		$PROVIDERASN["Verizon"]		= "65000";
		$PROVIDERASN["CenturyLink"]	= "209";
		$PROVIDERASN["Telus"]		= "852";
		$PROVIDERASN["Telus65001"]	= "65001";
		$this->data['peer_asn'] = $PROVIDERASN[$this->data['provider']];

		$OUTPUT .= "router bgp {$ASN}\n";
		$OUTPUT .= "  neighbor {$this->data['peer_ip']} remote-as {$this->data['peer_asn']}\n";
		$OUTPUT .= "  neighbor {$this->data['peer_ip']} description {$this->data['name']} {$this->data['provider']} {$this->data['circuitid']} {$this->data['interface']}\n";
		if (isset($this->data['vrf']))
		{
			$OUTPUT .= "  address-family ipv4 vrf " . $VRFS[$this->data['vrf']] . "\n";
		}else{
			$OUTPUT .= "  address-family ipv4\n";
		}
		$OUTPUT .= "    neighbor {$this->data['peer_ip']} activate\n";
		$OUTPUT .= "    neighbor {$this->data['peer_ip']} send-community both\n";
		$OUTPUT .= "    neighbor {$this->data['peer_ip']} soft-reconfiguration inbound\n";
		if (isset($this->data['routemap_in']))	{ $OUTPUT .= "    neighbor {$this->data['peer_ip']} route-map {$this->data['routemap_in' ]} in\n"; }
		if (isset($this->data['routemap_out']))	{ $OUTPUT .= "    neighbor {$this->data['peer_ip']} route-map {$this->data['routemap_out']} out\n"; }
		if (isset($this->data['vrf'])) { $OUTPUT .= "   exit\n"; }
		$OUTPUT .= " exit\n";

		return $OUTPUT;
	}

}
