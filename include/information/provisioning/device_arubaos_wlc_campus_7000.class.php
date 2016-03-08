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

require_once "information/provisioning/device_arubaos_wlc_campus.class.php";

class Provisioning_Device_ArubaOS_WLC_Campus_7000	extends Provisioning_Device_ArubaOS_WLC_Campus
{
	public $type = "Provisioning_Device_ArubaOS_WLC_Campus_7000";
	public $customfunction = "Config";

	// Nothing hardware specific to do here yet,
	// Maybe add initialize function later for custom child object creation? interfaces?
	// Could make a call to activate.arubanetworks.com and populate WLCs from a PO?
}
