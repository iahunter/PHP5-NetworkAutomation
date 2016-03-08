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

require_once "information/provisioning/device_arubaos_wlc.class.php";

class Provisioning_Device_ArubaOS_WLC_Campus	extends Provisioning_Device_ArubaOS_WLC
{
	public $type = "Provisioning_Device_ArubaOS_WLC_Campus";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text("mgmtgw"		,"Management Network Gateway Address (A.B.C.D)" );
		$SELECT = array(
			"gigabitethernet 0/0/0"	=> "gigabitethernet 0/0/0",
		);
		$OUTPUT .= $this->html_form_field_select("mgmtint"	,"Management Interface",$SELECT);
		$SELECT = array(
			"United States"	=> "USA",
			"Rest of World"	=> "ROW",
		);
        $OUTPUT .= $this->html_form_field_select("region"  ,"Wireless Spectrum Region",$SELECT);
		return $OUTPUT;
	}

	// Find all the WLC's in our current region
	public function get_wlcs()
	{
		$SEARCH = array(
						"category"		=> "provisioning",
						"type"			=> "device_arubaos_wlc_master_%",
						"stringfield2"	=> "{$this->data["region"]}",
						);
		// Get all the MASTER WLC device ID's in OUR REGION ONLY for building tunnels to
		$DEVICES = Information::search($SEARCH);
		return $DEVICES;
	}

	public function config_tunnels()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEVICES = $this->get_wlcs();
		$DEVICECOUNT = count($DEVICES);
		$OUTPUT .= "! Found {$DEVICECOUNT} WLCs in my region to tunnel to";
		foreach ($DEVICES as $DEVICEID)
		{
			$DEVICE = Information::retrieve($DEVICEID);
			$OUTPUT .= <<<END


! Building tunnel to {$DEVICE->data["type"]} {$DEVICE->data["name"]} ID {$DEVICE->data["id"]} Region {$DEVICE->data["region"]}
interface tunnel {$DEVICE->data["id"]}
        description "Tunnel Interface to {$DEVICE->data["type"]} {$DEVICE->data["name"]} {$DEVICE->data["id"]}"
        tunnel mode gre 1
        tunnel source {$this->data["mgmtip4"]}
        tunnel destination {$DEVICE->data["mgmtip4"]}
        trusted
        tunnel vlan 902
END;
		}
		return $OUTPUT;
	}

}
