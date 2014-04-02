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

require_once "information/information.class.php";

class Provisioning_Device	extends Information
{
	public $category = "Provisioning";
	public $type = "Provisioning_Device";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		$CHANGED += $this->customfield("mgmtip4"	,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['mgmtip4'	]);
		if(isset($this->data['newtype']) && $this->data['type'] != $this->data['newtype'])
		{
			print "Device Changed Type To {$this->data['newtype']}<br>
					<b>Please edit this new object and provide additional information!</b><br><br>\n";
			$this->data['type'] = $this->data['newtype'];
			unset($this->data['newtype']);
			$DB->bind("TYPE"            ,$this->data['type'             ]);
		}
		unset($this->data['newtype']); // TODO: remove this line after a while... DB cleanup stuff.
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 250;	// Name
		$this->html_width[$i++] = 220;	// Management IP
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Device Name","Management IP");
		$OUTPUT = $this->html_list_header_template("Provisioning Device List",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$columns = count($this->html_width)-1;	$i = 1;
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['mgmtip4']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		$OUTPUT .= $this->html_detail_buttons();
		$COLUMNS = array("ID","Name","Management IP");
		$COLUMNCOUNT = count($this->html_width)-1;	$i = 0;
		$OUTPUT .= $this->html_list_header_template("Provisioning Device Details",$COLUMNS);
		$OUTPUT .= $this->html_list_row($i++);
		$DUMP = trim(dumper_to_string($this->data));
		$ASN = $this->parent()->get_asn();
		if ($ASN < 1 || $ASN > 65535) { $ASN = "Warning: No Linked ASN Found! $ASN"; }
		$IPV4BLOCK = $this->parent()->get_ipv4block();
		if ($IPV4BLOCK == "") { $IPV4BLOCK = "Warning: No Linked IPv4 Block Found! $IPV4BLOCK"; }
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">Linked ASN: {$ASN}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">Linked IPv4 Block: {$IPV4BLOCK}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$DEV_MGMTIP4		= $this->data["mgmtip4"];
		$DEV_MGMTIP4_ADDR	= Net_IPv4::parseAddress($DEV_MGMTIP4)->ip;
		$DEV_NAME			= $this->data["name"];
		$SEARCH = array(
                    "category"      => "Management",
                    "type"          => "Device_Network_%",
                    "stringfield0"  => "{$DEV_NAME}",
                    "stringfield1"  => "{$DEV_MGMTIP4_ADDR}",
		                );
		$ID_MANAGED = Information::search($SEARCH);
		if ( count($ID_MANAGED) )
		{
			$ID_MANAGED = reset($ID_MANAGED);
			$AUDIT = "Found matching managed device ID {$ID_MANAGED}, <a href=\"/information/information-action.php?id={$this->data['id']}&action=Audit\">configuration audit available</a>!";
		}else{
			$AUDIT = "No matching managed device found, Audit not available.";
		}
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">{$AUDIT}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$CREATED_BY		= $this->created_by();
		$CREATED_WHEN	= $this->created_when();
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">Created by {$CREATED_BY} on {$CREATED_WHEN}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$COLUMNCOUNT}">Device Details:<pre>{$DUMP}</pre></td></tr>
END;
		$OUTPUT .= $this->html_list_footer();

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		if ( preg_match("/^device_ios/i",$this->data['type'],$REG) )	// If we are a device_ios, consider service instances and interfaces our children!
		{
			array_push($CHILDTYPES,"Interface","ServiceInstance");
		}

		foreach ($CHILDTYPES as $CHILDTYPE)
		{
			$OUTPUT .= <<<END

			<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
END;
			$CHILDREN = $this->children($this->id,$CHILDTYPE . "%","Provisioning");

			$i = 1;
			if (!empty($CHILDREN))
			{
				$CHILD = reset($CHILDREN);
				$OUTPUT .= $CHILD->html_list_header();
				foreach ($CHILDREN as $CHILD)
				{
					$OUTPUT .= $CHILD->html_list_row($i++);
				}
				$OUTPUT .= $CHILD->html_list_footer();
			}
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("name"			,"Device Name"											);
		$OUTPUT .= $this->html_form_field_text("mgmtip4"		,"Management IPv4 Address & Prefix Length (A.B.C.D/24)"	);

		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function html_form_extended()
	{
		$OUTPUT = "";
		if ($this->data['type'] == "Device")	// This is important. Only change device type if this is the first base instance!
		{
			$SELECT = array(
				"Device_IOS_RTR_WANRR_2900"		=> "2951 WAN Router",
				"Device_IOS_MLS_DIST_3560X"		=> "Catalyst 3560x Distribution Switch",
				"Device_IOS_SWI_ACC_2960X_24"	=> "Catalyst 2960x 24 Port Access Switch",
				"Device_IOS_SWI_ACC_2960X_48"	=> "Catalyst 2960x 48 Port Access Switch",
				"Device_IOS_MLS_PE_ME3800X"		=> "ME-3800x MPLS PE",
				"Device_IOSXE_RTR_VPNRR_asr1001"=> "ASR1001 MPLS VPNv4 Route Reflector",
				"Device_UPS_APC"				=> "APC UPS",
			);
			$OUTPUT .= $this->html_form_field_select("newtype","Device Model & Role",$SELECT);
		}
		return $OUTPUT;
	}

	public function get_vpn_by_vpnid($VPNID)
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 and stringfield0 = :VPNID limit 1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","VPN");
			$DB->bind("CATEGORY","MPLS");
			$DB->bind("VPNID",$VPNID);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		if(count($RESULTS)>0)
		{
			$VPN = Information::retrieve($RESULTS[0]['id']);
		}else{
			$VPN = "";
		}
		return $VPN;
	}

	public function config_interfaces()
	{
		$OUTPUT = "";
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 and parent = :DEVICEID";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","Interface");
			$DB->bind("CATEGORY","Provisioning");
			$DB->bind("DEVICEID",$this->data['id']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$COUNT = count($RESULTS);
		if($COUNT > 0)
		{
			$OUTPUT .= "\n! Found $COUNT Interfaces in this device.\n";
			foreach($RESULTS as $RESULT)
			{
				$INTERFACE = Information::retrieve($RESULT['id']);
				$OUTPUT .= $this->config_interface($INTERFACE);
			}
		}

		return $OUTPUT;
	}

	public function config_serviceinstances()
	{
		$OUTPUT = "";
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 and parent = :DEVICEID";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","ServiceInstance%");
			$DB->bind("CATEGORY","Provisioning");
			$DB->bind("DEVICEID",$this->data['id']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$COUNT = count($RESULTS);
		if($COUNT > 0)
		{
			$OUTPUT .= "\n! Found $COUNT Service Instances on this device.\n";
			foreach($RESULTS as $RESULT)
			{
				$SERVICEINSTANCE = Information::retrieve($RESULT['id']);
				$OUTPUT .= $SERVICEINSTANCE->config();
			}
		}

		return $OUTPUT;
	}

	public function get_devices_by_asn($ASN)
	{
		$DEVICES = array();
		global $DB; // Our Database Wrapper Object

		// First, look for the ASN and find a list of site codes associated with it!

		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1 and stringfield1 like :ASN";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","asn");
			$DB->bind("CATEGORY","bgp");
			$DB->bind("ASN",$ASN);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$SITES = array();
		if(count($RESULTS)>0)
		{
			$SITES = explode(" ",$RESULTS[0]['stringfield2']);	// Stringfield 2 is the space delimited list of sites for the BGP ASN record
		}else{
			return $DEVICES;
		}

		// Second, take the list of site codes and get their ID numbers

		$SITEQUERY = "";	$i = 0;
		foreach($SITES as $SITE)
		{
			$SITEQUERY .= "stringfield1 = '{$SITE}'";
			if (!(++$i === count($SITES)))
			{
				$SITEQUERY .= " or ";
			}
		}
		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1 and ( {$SITEQUERY} )";

		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","Site");
			$DB->bind("CATEGORY","Provisioning");
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$PARENTS = array();
		foreach ($RESULTS as $SITEDATA)
		{
			array_push($PARENTS,$SITEDATA['id']);
		}
		$PARENTLIST = implode(",",$PARENTS);

		// For every parent site ID, find the children devices that are like device%rtr or device%mls

		$QUERY = "select * from information where ( type like :TYPE1 or type like :TYPE2 ) and category like :CATEGORY and active = 1 and parent IN( {$PARENTLIST} )";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE1","%device%rtr%");
			$DB->bind("TYPE2","%device%mls%");
			$DB->bind("CATEGORY","Provisioning");
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		// Finally, retrieve all the children by ID for every result device

		foreach ($RESULTS as $DEVICEDATA)
		{
			array_push($DEVICES,Information::retrieve($DEVICEDATA['id']));
		}

		return $DEVICES;
	}

	public function get_vrf_array()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","VPN");
			$DB->bind("CATEGORY","MPLS");
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$OUTPUT = array();
		$OUTPUT[''] = "None";
		if(count($RESULTS)>0)
		{
			foreach($RESULTS as $VPNINFO)
			{
				$OUTPUT[$VPNINFO['stringfield0']] = "V{$VPNINFO['stringfield0']}:{$VPNINFO['stringfield1']}";
			}
		}

		return $OUTPUT;
	}

	public function audit()
	{
		$OUTPUT = "";

		$DEV_MGMTIP4		= $this->data["mgmtip4"];
		$DEV_MGMTIP4_ADDR	= Net_IPv4::parseAddress($DEV_MGMTIP4)->ip;
		$DEV_NAME			= $this->data["name"];

		$ID_PROVISIONED = array($this->data["id"]);

		$SEARCH = array(
							"category"      => "Management",
							"type"          => "Device_Network_%",
							"stringfield0"  => "{$DEV_NAME}",
							"stringfield1"  => "{$DEV_MGMTIP4_ADDR}",
						);
		$ID_MANAGED	= Information::search($SEARCH);

		if ( count($ID_PROVISIONED) && count($ID_MANAGED) )
		{
			$ID_PROVISIONED = reset($ID_PROVISIONED	);
			$ID_MANAGED		= reset($ID_MANAGED		);

			$OUTPUT .= "<a href=\"/information/information-action.php?id={$ID_MANAGED}&action=Scan\">Re-Scan Device</a><br>";

			$CONFIG_PROVISIONED		= $this->config();	// Generate the database clean configuration directives for this device
			$STRUCTURE_PROVISIONED	= $this->parse_nested_list_to_array( $this->filter_config($CONFIG_PROVISIONED) );

			$DEVICE_MANAGED			= Information::retrieve( $ID_MANAGED	 );
			$CONFIG_MANAGED			= $DEVICE_MANAGED->data["run"];		// Get the actual show run contents from the device
			if (strlen($CONFIG_MANAGED) < 900) { $OUTPUT .= "Error: managed device running config too small to audit!"; return $OUTPUT; }

			$STRUCTURE_MANAGED		= $this->parse_nested_list_to_array( $this->filter_config($CONFIG_MANAGED) );

			$MISSING= array_diff_assoc_recursive($STRUCTURE_PROVISIONED	,$STRUCTURE_MANAGED		);
			$EXTRA	= array_diff_assoc_recursive($STRUCTURE_MANAGED		,$STRUCTURE_PROVISIONED	);

			$OUTPUT .= "
				<table>
					<tr>
						<th colspan=2>FOUND MATCHED DEVICES Provisioned ID {$ID_PROVISIONED} Managed ID {$ID_MANAGED}<br><hr size=1>
					</tr>
					<tr>
						<th colspan=2 align=left>Configuration Missing From Provisioned Base</th>
					</tr>
					<tr>
						<td colspan=2>\n";
			$OUTPUT .= dBug_to_string($MISSING);
//					new dBug($MISSING);
			$OUTPUT .= "<br><hr size=1><br>
					</tr>
					<tr>
						<th colspan=2 align=left>Extra Config Not From Provisioning Tool</th>
					</tr>
					<tr>
						<td colspan=2>\n";
			$OUTPUT .= dBug_to_string($EXTRA);
//					new dBug($EXTRA);
			$OUTPUT .= "<br><hr size=1><br>
					</tr>

					<tr>
						<th>Provisioned Config</th>
						<th>Managed Config</th>
					</tr>
					<tr>
						<td valign=top>" . dumper_to_string($STRUCTURE_PROVISIONED) . "</td>
						<td valign=top>" . dumper_to_string($STRUCTURE_MANAGED)     . "</td>
					</tr>
			</table>\n";	/**/

		}else{
			$OUTPUT .= "Unable to find matching provisioning and management devices with name & IP address like {$DEV_NAME} / {$DEV_MGMTIP4_ADDR}\n";
		}

		return $OUTPUT;
	}

}

?>
