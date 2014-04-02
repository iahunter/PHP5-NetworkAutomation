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

require_once "information/provisioning/device_ios_swi_acc_2960x.class.php";

class Provisioning_Device_IOS_SWI_ACC_2960X_48	extends Provisioning_Device_IOS_SWI_ACC_2960X
{
	public $type = "Provisioning_Device_IOS_SWI_ACC_2960X_48";
	public $customfunction = "Config";

	public function initialize()
	{
		$OUTPUT = "";
		// Add 48 downstream gigabit interfaces
		$RANGE = range(1,52);
		foreach($RANGE as $PORT)
		{
			$TYPE		= "Interface";
			$CATEGORY	= $this->data['category'];
			$PARENT		= $this->data['id'];
			$INTERFACE	= Information::create($TYPE,$CATEGORY,$PARENT);

			$INTERFACE->data['name']		= "GigabitEthernet1/0/{$PORT}";
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

}

?>
