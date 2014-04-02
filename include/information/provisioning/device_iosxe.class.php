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

require_once "information/provisioning/device_ios.class.php";

class Provisioning_Device_IOSXE	extends Provisioning_Device_IOS
{
	public $type = "Provisioning_Device_IOSXE";

	public function config_multicast()
	{
		$OUTPUT = "";
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$OUTPUT .= "
ip multicast-routing distributed
ip multicast multipath
ip pim ssm default

int loopback0
  ip pim sparse-mode
 exit

";
		return $OUTPUT;
	}

}

?>
