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

class Provisioning_ServiceInstance_BGP_Advertisment	extends Provisioning_ServiceInstance
{
	public $type = "Provisioning_ServiceInstance_BGP_Advertisment";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_textarea("prefixes"	,"IPv4 Prefixes, new-line delimited (A.B.C.D/length)"	);
		$SELECT = array(
			""					=> "None",
			"RM_SET_NOEXPORT"	=> "Set No-Export Community",
		);
		$OUTPUT .= $this->html_form_field_select("routemap"		,"Route Map",$SELECT);
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
		$ROUTEMAPS[""] = "";	// Do I need this?
		$ROUTEMAPS["RM_SET_NOEXPORT"] = <<<END
route-map RM_PERMIT_ANY permit 10
 exit

END;
		if (isset($this->data['routemap']))	{ $OUTPUT .= "{$ROUTEMAPS[$this->data['routemap'	]]}\n"; }

		$OUTPUT .= "router bgp {$ASN}\n";
		if (isset($this->data['vrf']))
		{
			$VPN = $this->parent()->get_vpn_by_vpnid($this->data['vrf']);
			$VRFNAME = "V".$VPN->data['vpnid'].":".$VPN->data['name'];		
			$OUTPUT .= "  address-family ipv4 vrf " . $VRFNAME . "\n";
		}else{
			$OUTPUT .= "  address-family ipv4\n";
		}
		$PREFIXES = preg_split('/\r\n|\r|\n/',$this->data['prefixes']);
		foreach ($PREFIXES as $PREFIX)
		{
			$NET = Net_IPv4::parseAddress($PREFIX);
			if ($NET->network && $NET->netmask)
			{
				$OUTPUT .= "    network {$NET->network} mask {$NET->netmask}";
				if (isset($this->data['routemap']))	{ $OUTPUT .= " route-map {$this->data['routemap']}"; }
				$OUTPUT .= "\n";
			}
		}
		if (isset($this->data['vrf'])) { $OUTPUT .= "   exit\n"; }
		$OUTPUT .= " exit\n";

		return $OUTPUT;
	}

}
