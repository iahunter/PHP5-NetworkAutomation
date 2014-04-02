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

require_once "information/provisioning/device_ios_mls_pe.class.php";

class Provisioning_Device_IOS_MLS_PE_ME3800X	extends Provisioning_Device_IOS_MLS_PE
{
	public $type = "Provisioning_Device_IOS_MLS_PE_ME3800X";
	public $customfunction = "Config";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text("mgmtgw"		,"Management Network Gateway Address (A.B.C.D)" );
		$SELECT = array(
			"GigabitEthernet0"	=> "GigabitEthernet0",
		);
		$OUTPUT .= $this->html_form_field_select("mgmtint"	,"Management Interface",$SELECT);
		$SELECT = array(
			"management"		=> "management",
		);
		$OUTPUT .= $this->html_form_field_select("mgmtvrf"	,"Management VRF Name",$SELECT);
		$OUTPUT .= $this->html_form_field_text("loopback4"	,"Loopback IPv4 Address (A.B.C.D)" );
		return $OUTPUT;
	}

	public function initialize()
	{
		$OUTPUT = "";
		global $DB;

		$TYPE		= "Interface";
		$CATEGORY	= $this->data['category'];
		$PARENT		= $this->data['id'];

		///////////////////////////////
		// Add 24 gigabit interfaces //
		///////////////////////////////

		$RANGE = range(1,24);
		foreach($RANGE as $PORT)
		{
			$INTERFACE  = Information::create($TYPE,$CATEGORY,$PARENT);

			$INTERFACE->data['name']        = "GigabitEthernet0/{$PORT}";
			$INTERFACE->data['description'] = "AVAILABLE";
			$INTERFACE->data['layer']       = "3";

			$ID = $INTERFACE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$INTERFACE = Information::retrieve($ID);
			$INTERFACE->update();
		}

		/////////////////////////////
		// Add 2 tengig interfaces //
		/////////////////////////////

		$RANGE = range(1,2);
		foreach($RANGE as $PORT)
		{
			$INTERFACE  = Information::create($TYPE,$CATEGORY,$PARENT);

			$INTERFACE->data['name']        = "TenGigabitEthernet0/{$PORT}";
			$INTERFACE->data['description'] = "AVAILABLE";
			$INTERFACE->data['layer']       = "3";

			$ID = $INTERFACE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$INTERFACE = Information::retrieve($ID);
			$INTERFACE->update();
		}

		return $OUTPUT;
	}

}

?>
