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

class Provisioning_ServiceInstance_Switch_Stack	extends Provisioning_ServiceInstance

	public $type = "Provisioning_ServiceInstance_Switch_Stack";

	public function html_form_extended()
	{
		$OUTPUT = "";
/*		$SELECT_MODEL = array(
			"ws-c2960x-24ps-l"		=> "2960X 24 Port",
			"ws-c2960x-48fps-l"		=> "2960X 48 Port",
		); /**/ // Assuming all stacks require 48 port switches
//		$OUTPUT .= $this->html_form_field_select("stack_members"		,"Stack Members",$SELECT);
		$SELECT_SWITCH = array(
			"2"	=> "2",
			"3"	=> "3",
			"4" => "4",
			"5" => "5",
			"6" => "6",
			"7" => "7",
		);
		$OUTPUT .= $this->html_form_field_textarea("comments"	,"Comments");
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";
		foreach($SELECT_SWITCH as $SWITCH)
		{
			// Add 48 downstream gigabit interfaces
			$RANGE = range(1,52);
			foreach($RANGE as $PORT)
			{
				$TYPE		= "Interface";
				$CATEGORY	= $this->data['category'];
				$PARENT		= $this->data['id'];
				$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

				$INTERFACE->data['name']		= "GigabitEthernet{$SWITCH}/0/{$PORT}";
				$INTERFACE->data['description'] = "AVAILABLE";
				$INTERFACE->data['layer']		= "2";
				if ($PORT <= 46) {
					$INTERFACE->data['voicevlan']	= "9";
					$INTERFACE->data['spanningtree']= "host";
				}else{
					$INTERFACE->data['spanningtree']= "network";
				}
				$INTERFACE->data['vlan']		= "all";

				$ID = $INTERFACE->insert();
				$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
				global $DB;
				$DB->log($MESSAGE);
				$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
				$INTERFACE = Information::retrieve($ID);
				$INTERFACE->update();
			}
			return $OUTPUT;
		}
?>
