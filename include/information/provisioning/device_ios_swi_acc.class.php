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

require_once "information/provisioning/device_ios_swi.class.php";

class Provisioning_Device_IOS_SWI_ACC	extends Provisioning_Device_IOS_SWI
{
	public $type = "Provisioning_Device_IOS_SWI_ACC";

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

		$OUTPUT .= "lldp run\n";

		$OUTPUT .= $this->config_management();

		$OUTPUT .= $this->config_motd();

		$OUTPUT .= $this->config_dns();

		$OUTPUT .= $this->config_logging();

		$OUTPUT .= $this->config_spanningtree();

		$OUTPUT .= $this->config_aaa();

		$OUTPUT .= $this->config_snmp();

		$IPV4BLOCK	= $this->parent()->get_ipv4block();
		$IPV4NETWORK= Net_IPv4::parseAddress($IPV4BLOCK)->ip;
		$IPV4LONG	= ip2long($IPV4NETWORK);

		$OUTPUT .= $this->config_vlan(1	, long2ip($IPV4LONG +    0) . "/22_Wired"			);
		$OUTPUT .= $this->config_vlan(5	, long2ip($IPV4LONG + 1024) . "/22_Wireless"		);
		$OUTPUT .= $this->config_vlan(9	, long2ip($IPV4LONG + 2048) . "/22_Voice"			);
		$OUTPUT .= $this->config_vlan(13, long2ip($IPV4LONG + 3072) . "/23_Guest_Partner_JV");

		$OUTPUT .= $this->config_interfaces();

		$OUTPUT .= $this->config_serviceinstances();

		$OUTPUT .= "end\n";

		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

	public function config_management()
	{
		$OUTPUT = "";
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$DEV_MGMTIP4    = $this->data['mgmtip4'];
		$DEV_MGMTGW     = $this->data['mgmtgw'];
		$DEV_MGMTINT    = $this->data['mgmtint'];
		$DEV_MGMTVRF    = $this->data['mgmtvrf'];

		$DEV_MGMTIP4_ADDR = Net_IPv4::parseAddress($DEV_MGMTIP4)->ip;
		$DEV_MGMTIP4_MASK = Net_IPv4::parseAddress($DEV_MGMTIP4)->netmask;

		if ($DEV_MGMTVRF != "")
		{
			$OUTPUT .= "
vrf definition $DEV_MGMTVRF
  address-family ipv4
 exit

interface $DEV_MGMTINT
  vrf forwarding $DEV_MGMTVRF
  ip address $DEV_MGMTIP4_ADDR $DEV_MGMTIP4_MASK
  no shut
 exit

ip route vrf $DEV_MGMTVRF 0.0.0.0 0.0.0.0 $DEV_MGMTGW
";
		}else{
			$OUTPUT .= "
interface $DEV_MGMTINT
  ip address $DEV_MGMTIP4_ADDR $DEV_MGMTIP4_MASK
  no shut
 exit

ip default-gateway $DEV_MGMTGW
";
		}
		return $OUTPUT;
	}

}

?>
