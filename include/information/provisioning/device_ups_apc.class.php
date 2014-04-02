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

class Provisioning_Device_UPS_APC	extends Provisioning_Device
{
	public $type = "Provisioning_Device_UPS_APC";
	public $customfunction = "Config";

	public function html_form_extended()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_field_text("gateway","Default Gateway (1.2.3.4)");
		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "<pre>\n";
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$OUTPUT .= "! Found Device ID ".$this->data['id']." of type ".get_class($this)."\n\n";

		$IP = Net_IPv4::parseAddress($this->data['mgmtip4'])->ip;
		$NETMASK = Net_IPv4::parseAddress($this->data['mgmtip4'])->netmask;

		$OUTPUT .= <<<END
!****************************************************************************
!APC AP9631 Management Card Config Template - ALL SIZES
!Modified : 2/25/2014
!****************************************************************************

Hostname:       {$this->data["name"]}
IP Address:     {$this->data["mgmtip4"]}
Gateway:        {$this->data["gateway"]}

!****************************************************************************
!GLOBAL SETTINGS - These settings should be applied to ALL APC UPSes.
!After performing all Replacements above, these settings should not need to be modified.
!
!After applying these settings and rebooting the management card, complete the configuration
!via the WEB interface using HTTPS.
!****************************************************************************

tcpip -S enable
tcpip -i {$IP}
tcpip -s {$NETMASK}
tcpip -g {$this->data["gateway"]}
tcpip -h {$this->data["name"]}
tcpip -d net.company.com

portspeed -s auto

system -n {$this->data["name"]}
system -c IM TELECOM
system -l {$this->parent()->data['name']}

radius -a local

user -an telecom
user -ap changeme


console -S ssh
console -pt 23
console -ps 22

web -S https
web -ph 80
web -ps 443

ntp -p 10.123.1.123
ntp -p 10.123.2.123
ntp -p 10.123.3.123
ntp -OM enable

dns -p 172.30.0.225
dns -s 10.216.1.103
dns -d company.COM
dns -n company.COM
dns -h {$this->data["name"]}

snmp -S enable

ftp -S disable

reboot
YES

END;
		$OUTPUT .= "</pre>";
		return $OUTPUT;
	}

}

?>
