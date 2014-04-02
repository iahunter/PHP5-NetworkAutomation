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

require_once "information/provisioning/device_ios_mls_dist.class.php";

class Provisioning_Device_IOS_MLS_DIST_3560X	extends Provisioning_Device_IOS_MLS_DIST
{
	public $type = "Provisioning_Device_IOS_MLS_DIST_3560X";
	public $customfunction = "Config";

	public function update_override()
	{
		$this->data['loopback4']= Net_IPv4::parseAddress($this->data['mgmtip4'])->ip;
		$this->data['mgmtint']	= "Loopback0";
	}

	public function html_form_extended()
	{
		$OUTPUT = "";
		return $OUTPUT;
	}

	public function initialize()
	{
		$OUTPUT = "";
		global $DB;

		if (!preg_match("/.*swd01$/i",$this->data['name'],$MATCH)	&&
			!preg_match("/.*swd02$/i",$this->data['name'],$MATCH)	)
		{
			$MESSAGE = "Initialize did not match standard device name, got {$this->data['name']}, initialize aborted!";
			$DB->log($MESSAGE);
			return $MESSAGE;
		}

		$TYPE		= "Interface";
		$CATEGORY	= $this->data['category'];
		$PARENT		= $this->data['id'];

		$IPV4BLOCK  = $this->parent()->get_ipv4block();
		$IPV4NETWORK= Net_IPv4::parseAddress($IPV4BLOCK)->ip;
		$IPV4LONG   = ip2long($IPV4NETWORK);

		$ADVERTISE_PREFIXES = "";

		//////////////////////////
		// Add 4 standard SVI's //
		//////////////////////////

		// VLAN 1 - WIRED
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "Vlan1";
		$INTERFACE->data['description']	= long2ip($IPV4LONG +    0) . "/22_WIRED";
		$ADVERTISE_PREFIXES .= long2ip($IPV4LONG +    0) . "/22\n";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['hsrpip']		= long2ip($IPV4LONG +    1);
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    2) . "/22";
			$INTERFACE->data['hsrppriority']= "105";
		}else{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    3) . "/22";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();

		$IPV4LONG += 1024;

		// VLAN 5 - WIRELESS
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "Vlan5";
		$INTERFACE->data['description']	= long2ip($IPV4LONG +    0) . "/22_WIRELESS";
		$ADVERTISE_PREFIXES .= long2ip($IPV4LONG +    0) . "/22\n";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['hsrpip']		= long2ip($IPV4LONG +    1);
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    2) . "/22";
			$INTERFACE->data['hsrppriority']= "105";
		}else{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    3) . "/22";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();

		$IPV4LONG += 1024;

		// VLAN 9 - VOICE
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "Vlan9";
		$INTERFACE->data['description']	= long2ip($IPV4LONG +    0) . "/22_VOICE";
		$ADVERTISE_PREFIXES .= long2ip($IPV4LONG +    0) . "/22\n";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['hsrpip']		= long2ip($IPV4LONG +    1);
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    2) . "/22";
			$INTERFACE->data['hsrppriority']= "105";
		}else{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    3) . "/22";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();

		$IPV4LONG += 1024;

		// VLAN 13 - GUEST_PARTNER_JV
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "Vlan13";
		$INTERFACE->data['description']	= long2ip($IPV4LONG +    0) . "/23_GUEST_PARTNER_JV";
		$ADVERTISE_PREFIXES .= long2ip($IPV4LONG +    0) . "/23\n";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['hsrpip']		= long2ip($IPV4LONG +    1);
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    2) . "/23";
			$INTERFACE->data['hsrppriority']= "105";
		}else{
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG +    3) . "/23";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();

		///////////////////////////////
		// Add 2 upstream interfaces //
		///////////////////////////////

		$IPV4LONG = ip2long($IPV4NETWORK);	// Reset IPv4 Long before moving onto the next interface batch!
		$IPV4LONG += 3840; // Get us to the beginning of our transit network space

		// Gig0/1 - WAN RTR 1 Gi0/1
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "GigabitEthernet0/1";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['ospf']		= "1";
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['description']	= "To WAN RTR 1 Gi0/0";
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG + 22) . "/30";
		}else{
			$INTERFACE->data['description']	= "To WAN RTR 1 Gi0/1";
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG + 26) . "/30";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();

		// Gig0/2 - WAN RTR 2 Gi0/1
		$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

		$INTERFACE->data['name']		= "GigabitEthernet0/2";
		$INTERFACE->data['layer']		= "3";
		$INTERFACE->data['ospf']		= "1";
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$INTERFACE->data['description']	= "To WAN RTR 2 Gi0/0";
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG + 30) . "/30";
		}else{
			$INTERFACE->data['description']	= "To WAN RTR 2 Gi0/1";
			$INTERFACE->data['ip4']			= long2ip($IPV4LONG + 34) . "/30";
		}

		$ID = $INTERFACE->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$INTERFACE = Information::retrieve($ID);
		$INTERFACE->update();


		//////////////////////////////////
		// Add 20 downstream interfaces //
		//////////////////////////////////

		$RANGE = range(3,24);
		foreach($RANGE as $PORT)
		{
			$INTERFACE  = Information::create($TYPE,$CATEGORY,$PARENT);

			$INTERFACE->data['name']        = "GigabitEthernet0/{$PORT}";
			$INTERFACE->data['description'] = "AVAILABLE";
			$INTERFACE->data['layer']       = "2";
			if ($PORT <= 24) {
				$INTERFACE->data['voicevlan']   = "9";
				$INTERFACE->data['spanningtree']= "host";
			}else{
				$INTERFACE->data['spanningtree']= "network";
			}
			$INTERFACE->data['vlan']        = "all";

			$ID = $INTERFACE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$INTERFACE = Information::retrieve($ID);
			$INTERFACE->update();
		}

		///////////////////////////////////
		// Add 4 downstream fiber module //
		///////////////////////////////////

		$RANGE = range(1,4);
		foreach($RANGE as $PORT)
		{
			$INTERFACE  = Information::create($TYPE,$CATEGORY,$PARENT);

			$INTERFACE->data['name']        = "GigabitEthernet1/{$PORT}";
			$INTERFACE->data['description'] = "AVAILABLE";
			$INTERFACE->data['layer']       = "2";
			$INTERFACE->data['spanningtree']= "network";
			$INTERFACE->data['vlan']        = "all";

			$ID = $INTERFACE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$INTERFACE = Information::retrieve($ID);
			$INTERFACE->update();
		}

		/////////////////////////////////////////////////////
		// Add bgp advertisment serviceinstance for VLANs! //
		/////////////////////////////////////////////////////

		$TYPE = "ServiceInstance_BGP_Advertisment";
		$SI  = Information::create($TYPE,$CATEGORY,$PARENT);

		$SI->data['name']        = "Standard VLANs";
		$SI->data['description'] = "Standard VLANs";
		$SI->data['prefixes']	= $ADVERTISE_PREFIXES;

		$ID = $SI->insert();
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
		$SI = Information::retrieve($ID);
		$SI->update();

		return $OUTPUT;
	}

	public function config_sdm()
	{
		$OUTPUT = "";

		$OUTPUT = <<<END
config t
  sdm prefer routing
! This requires a reload, setting the SDM preference is required for later configuration to work.
 end
wr
reload

! After the switch reboots, continue with the configuration.


END;

		return $OUTPUT;
	}

}

?>
