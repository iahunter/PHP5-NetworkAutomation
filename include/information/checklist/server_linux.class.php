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

require_once "information/checklist/server.class.php";

class Checklist_Server_Linux	extends Checklist_Server
{
	public $type = "Checklist_Server_Linux";

	public function html_form_extended()
	{
		$OUTPUT = "";

		$SELECT = array(
			"Production" => "Production",
			"QA" => "QA",
			"Dev" => "Dev",
			"Test" => "Test",
			"UAT" => "UAT",
			"Sandbox" => "Sandbox",
			"Training" => "Training",
		);
		$OUTPUT .= $this->html_form_field_select("environment","Environment",$SELECT);
		$SELECT = array(
			"Web" => "Web",
			"App" => "App",
			"Oracle" => "Oracle",
			"SQL" => "SQL",
			"Infrastructure" => "Infrastructure",
			"Reporting" => "Reporting",
			"File Server" => "File Server",
			"Terminal Server" => "Terminal Server",
		);
		$OUTPUT .= $this->html_form_field_select("systemtype","System Type"	,$SELECT);
		$SELECT = array(
			"Windows 2008 R2"	=> "Windows 2008 R2",
			"Windows 2012"		=> "Windows 2012",
			"Windows 2012 R2"	=> "Windows 2012 R2",
			"Windows 2003"		=> "Windows 2003",
		);
		$OUTPUT .= $this->html_form_field_select("os","Operating System"	,$SELECT);
		$OUTPUT .= $this->html_form_field_text("application","Application"				);

		$OUTPUT .= $this->html_form_field_text("disk1"		,"Disk 1 C: (Default 60GB)"	);
		$OUTPUT .= $this->html_form_field_text("disk2"		,"Disk 2 F: Data"			);
		$OUTPUT .= $this->html_form_field_text("disk3"		,"Disk 3 L: Logs"			);
		$OUTPUT .= $this->html_form_field_text("disk4"		,"Disk 4 Q: Quorum"			);
		$OUTPUT .= $this->html_form_field_text("disk5"		,"Disk 5 U: Backups"		);

		$SELECT = array(
			"No"	=> "No",
			"Yes"	=> "Yes",
		);
		$OUTPUT .= $this->html_form_field_select("internetfacing","Internet Facing",$SELECT);
		$OUTPUT .= $this->html_form_field_select("loadbalanced","Load Balanced",	$SELECT);
		$SELECT = array(
			"Test" => "Test",
			"Non-Prod" => "Non-Prod",
			"Prod" => "Prod",
		);
		$OUTPUT .= $this->html_form_field_select("updategroup","Update Group",  $SELECT);
		$SELECT = array(
			"Grid 1" => "Grid 1",
			"Grid 2" => "Grid 2",
			"Grid 3" => "Grid 3",
			"Grid 4" => "Grid 4",
			"DataDomain Midlands" => "DataDomain Midlands",
			"DataDomain Scott Tech" => "DataDomain Scott Tech",
		);
		$OUTPUT .= $this->html_form_field_select("backuplocation","Backup Location",  $SELECT);
		$SELECT = array(
			"EST"	=> "Eastern",
			"CST"	=> "Central",
			"MST"	=> "Mountain",
			"PST"	=> "Pacific",
			"AKST"	=> "Alaska",
			"HST"	=> "Hawaii",
		);
		$OUTPUT .= $this->html_form_field_select("timezone","Time Zone",  $SELECT);

		$OUTPUT .= $this->html_form_field_textarea("avexclusion","AV Exclusions (ex. C:\Program Files\Exchange"	);
		$OUTPUT .= $this->html_form_field_textarea("admingroups","Admin Groups"									);

		$OUTPUT .= $this->html_form_field_text("ip4"		,"IPv4 Address (10.2.14.77/24)"	);
		$OUTPUT .= $this->html_form_field_text("natip4"		,"Internet NAT IPv4 Address"	);
		$OUTPUT .= $this->html_form_field_text("dhcpserver"	,"DHCP Server IP"				);
		$OUTPUT .= $this->html_form_field_text("domain"		,"AD Domain"					);

		$OUTPUT .= $this->html_form_field_textarea("notes","Notes");

		return $OUTPUT;
	}

	public function html_detail_rows($i = 0)
	{
		$OUTPUT = "";

		$this->html_width();
		$CREATOR = $this->created_by();
		$ROWCLASS = "row".(($i++ % 2)+1);
		$j = 1;
		$OUTPUT .= <<<END

			</tbody>
		</table><br>
		<table class="report" width="785">
			<caption class="report">Server Information</caption>
			<thead>
				<tr>
					<th class="report">Hostname</td>
					<th class="report">Environment</td>
					<th class="report">System Type</td>
					<th class="report">Operating System</td>
					<th class="report">Application</td>
				</tr>
			</thead>
			</tbody>
				<tr class="{$ROWCLASS}">
					<td class="report">{$this->data['name']}</td>
					<td class="report">{$this->data['environment']}</td>
					<td class="report">{$this->data['systemtype']}</td>
					<td class="report">{$this->data['os']}</td>
					<td class="report">{$this->data['application']}</td>
				</tr>
END;
		$ROWCLASS = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END

			</tbody>
			<thead>
				<tr>
					<th class="report">Disk 1 C:</td>
					<th class="report">Disk 2 F:</td>
					<th class="report">Disk 3 L:</td>
					<th class="report">Disk 4 Q:</td>
					<th class="report">Disk 5 U:</td>
				</tr>
			</thead>
			</tbody>
				<tr class="{$ROWCLASS}">
					<td class="report">{$this->data['disk1']}</td>
					<td class="report">{$this->data['disk2']}</td>
					<td class="report">{$this->data['disk3']}</td>
					<td class="report">{$this->data['disk4']}</td>
					<td class="report">{$this->data['disk5']}</td>
				</tr>
END;

		$ROWCLASS = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END

			</tbody>
			<thead>
				<tr>
					<th class="report">Internet Facing</td>
					<th class="report">Load Balanced</td>
					<th class="report">Update Group</td>
					<th class="report">Backup Location</td>
					<th class="report">Time Zone</td>
				</tr>
			</thead>
			</tbody>
				<tr class="{$ROWCLASS}">
					<td class="report">{$this->data['internetfacing']}</td>
					<td class="report">{$this->data['loadbalanced']}</td>
					<td class="report">{$this->data['updategroup']}</td>
					<td class="report">{$this->data['backuplocation']}</td>
					<td class="report">{$this->data['timezone']}</td>
				</tr>
END;
		$ROWCLASS = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END

			</tbody>
			<thead>
				<tr>
					<th class="report" colspan="3">AV Exclusions</td>
					<th class="report" colspan="2">Admin Groups</td>
				</tr>
			</thead>
			</tbody>
				<tr class="{$ROWCLASS}">
					<td class="report" colspan="3">{$this->data['avexclusion']}</td>
					<td class="report" colspan="2">{$this->data['admingroups']}</td>
				</tr>
END;
		$ROWCLASS = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END

			</tbody>
			<thead>
				<tr>
					<th class="report">IPv4 Address</td>
					<th class="report">IPv4 NAT</td>
					<th class="report">DHCP Server</td>
					<th class="report">Domain</td>
					<th class="report">...</td>
				</tr>
			</thead>
			</tbody>
				<tr class="{$ROWCLASS}">
					<td class="report" width="{$this->html_width[$j++]}">{$this->data['ip4']}</td>
					<td class="report" width="{$this->html_width[$j++]}">{$this->data['natip4']}</td>
					<td class="report" width="{$this->html_width[$j++]}">{$this->data['dhcpserver']}</td>
					<td class="report" width="{$this->html_width[$j++]}">{$this->data['domain']}</td>
					<td class="report" width="{$this->html_width[$j++]}">...</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function initialize()
	{
		$TASKS = array(
			"Create CMDB Entry",
			"Assign IP Address",
			"Create Computer Object in AD",
			"Deploy Server (Template or Full Build)",
			"VMware - Enable HOTPlug Mem/CPU",
			"VMWare - Enable Upgrade/Install VMTools on reboot",
			"Create DHCP reservation",
			"Join Domain",
			"Apply KMS Key",
			"Install SCCM",
			"Install SCOM",
			"Install Endpoint Protection",
			"Avamar - Install Agent",
			"Avamar - Activate Agent",
			"Avamar - Ensure server is in the right Avamar Domain",
			"Avamar - Make sure the Client has been added to policy/group",
		);
		$this->addtasks($TASKS);
	}

}

?>
