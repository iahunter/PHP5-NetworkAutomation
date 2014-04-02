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

class Provisioning_ServiceInstance_eBGP_Peer	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_eBGP_Peer";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text("peer_ip"		,"Peer IP Address"				);
		$OUTPUT .= $this->html_form_field_text("peer_asn"		,"Peer ASN (1-65535)"			);
		$SELECT = array(
			"RM_PERMIT_ANY"		=> "Permit Any Prefixes",
			"RM_PERMIT_LOCAL"	=> "Permit Locally Originated Prefixes",
			"RM_DENY_ANY"		=> "Deny Any Prefixes"
		);
		$OUTPUT .= $this->html_form_field_select("routemap_in"	,"Inbound Route Map",$SELECT);
		$OUTPUT .= $this->html_form_field_select("routemap_out"	,"Outbound Route Map",$SELECT);
		$SELECT = $this->parent()->get_vrf_array();
		$OUTPUT .= $this->html_form_field_select("vrf"			,"MPLS L3VPN",$SELECT);
		$OUTPUT .= $this->html_form_field_textarea("comments"	,"Comments");
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";
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
		if (isset($this->data['routemap_in']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_in'	]]}\n"; }
		if (isset($this->data['routemap_out']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap_out'	]]}\n"; }

		$OUTPUT .= "router bgp {$ASN}\n";
		if (isset($this->data['vrf']))
		{
			$VPN = $this->parent()->get_vpn_by_vpnid($this->data['vrf']);
			$VRFNAME = "V".$VPN->data['vpnid'].":".$VPN->data['name'];
			$OUTPUT .= "  address-family ipv4 vrf " . $VRFNAME . "\n";
			$OUTPUT .= "    neighbor {$this->data['peer_ip']} remote-as {$this->data['peer_asn']}\n";
			$OUTPUT .= "    neighbor {$this->data['peer_ip']} description {$this->data['name']}\n";
		}else{
			$OUTPUT .= "  neighbor {$this->data['peer_ip']} remote-as {$this->data['peer_asn']}\n";
			$OUTPUT .= "  neighbor {$this->data['peer_ip']} description {$this->data['name']}\n";
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
