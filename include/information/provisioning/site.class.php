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

class Provisioning_Site	extends Information
{
	public $category = "Provisioning";
	public $type = "Provisioning_Site";
	public $customfunction = "DHCP_Scopes";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"		,"stringfield0");
		$CHANGED += $this->customfield("sitecode"	,"stringfield1");
		$CHANGED += $this->customfield("name"		,"stringfield2");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['sitecode'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['name'			]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["sitecode"] == "")
		{
			$this->data['error'] .= "ERROR: site code provided is not valid!\n";
			return 0;
		}

		// If we are EDITING existing data AND changing the site code, REJECT the change!
		if ( isset($this->data["id"]) && $NEWDATA["sitecode"] != $this->data["sitecode"] )
		{
			$this->data['error'] .= "ERROR: site codes may NOT be changed!\n";
			return 0;
		}

		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing sites with the same name!
					"category"		=> "Provisioning",
					"type"			=> "Site",
					"stringfield1"	=> $NEWDATA["sitecode"],
					);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)
			{
				$DUPLICATE = reset($RESULTS);
				$this->data['error'] .= "ERROR: Found existing provisoning site ID {$DUPLICATE} with the same sitecode!\n";
				return 0;
			}
		}

		return 1;
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by stringfield1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type']);
			$DB->bind("CATEGORY",$this->data['category']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		return $RESULTS;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 70;	// Sitecode
		$this->html_width[$i++] = 400;	// Name
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Provisioning Site List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Site Code</th>
					<th class="report" width="{$this->html_width[$i++]}">Site Name</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$columns = count($this->html_width)-1;	$i = 1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['sitecode']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['name']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function get_asn()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1 and stringfield2 like :SITECODE order by stringfield1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","asn");
			$DB->bind("CATEGORY","bgp");
			$DB->bind("SITECODE","%".$this->data['sitecode']."%");
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
			$ASN = $RESULTS[0]['stringfield1'];
		}else{
			$ASN = 0;
		}
		return $ASN;
	}

	public function get_ipv4block()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1 and stringfield4 like :SITECODE order by stringfield1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","block");
			$DB->bind("CATEGORY","ipplan");
			$DB->bind("SITECODE","%".$this->data['sitecode']."%");
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
			$IPV4BLOCK = $RESULTS[0]['stringfield1'] . "/" . $RESULTS[0]['stringfield2'];
		}else{
			$IPV4BLOCK = "";
		}
		return $IPV4BLOCK;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= <<<END
		<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
			<tr>
				<td>
END;
		if ($this->customfunction)
		{
			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$this->data['id']}&action={$this->customfunction}">{$this->customfunction}</a>
						</li>
					</ul>
END;
		}
		$OUTPUT .= <<<END
				</td>
				<td align="right">
					<ul class="object-tools">
						<li>
							<a href="/information/information-edit.php?id={$this->data['id']}" class="viewsitelink">Edit Information</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>
END;

		// Information table itself
		$columns = count($this->html_width)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Provisioning Site Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Site Code</th>
					<th class="report" width="{$this->html_width[$i++]}">Site Name</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);
		$rowclass = "row".(($i % 2)+1); $i++;
        $CREATED_BY     = $this->created_by();
        $CREATED_WHEN   = $this->created_when();
        $OUTPUT .= <<<END
                <tr class="{$rowclass}"><td colspan="{$columns}">Created by {$CREATED_BY} on {$CREATED_WHEN}</td></tr>
END;
        $rowclass = "row".(($i++ % 2)+1);
        $OUTPUT .= <<<END
                <tr class="{$rowclass}"><td colspan="{$columns}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
        $rowclass = "row".(($i++ % 2)+1);
		$ASN = $this->get_asn();
		if ($ASN < 1 || $ASN > 65535)	{ $ASN = "Warning: No Linked ASN Found! $ASN";						$HAS_ASN	= 0; }else{ $HAS_ASN	= 1; }
		$IPV4BLOCK = $this->get_ipv4block();
		if ($IPV4BLOCK == "")			{ $IPV4BLOCK = "Warning: No Linked IPv4 Block Found! $IPV4BLOCK";	$HAS_IPV4	= 0; }else{ $HAS_IPV4	= 1; }
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Linked ASN: {$ASN}</td></tr>
END;
		$rowclass = "row".(($i % 2)+1); $i++;
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Linked IPv4 Block: {$IPV4BLOCK}</td></tr>
END;
		$OUTPUT .= $this->html_list_footer();

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		array_push($CHILDTYPES,"Device");

		$OUTPUT .= <<<END

			<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
END;
		if($HAS_ASN && $HAS_IPV4)
		{
			foreach ($CHILDTYPES as $CHILDTYPE)
			{
				$OUTPUT .= <<<END

						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
END;
			}
		}else{
			$OUTPUT .= "Site must be linked to BGP ASN and IPv4 block before devices may be added!";
		}
		$OUTPUT .= <<<END
					</td>
				</tr>
			</table>
END;

		$CHILDREN = $this->children($this->id,"","Provisioning");
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


		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();  // Permit the user to deactivate any devices and children

		if ( !isset($this->data["id"]) )    // If this is a NEW record being added, NOT an edit
		{
			$WSDL = "https://portal.company.com/sites/imclientengag/logistics/_vti_bin/Lists.asmx?WSDL"; // Site Code Register list
			require_once "PHP-SharePoint-Lists-API/SharePointAPI.php";
			$SP = new SharePointAPI(LDAP_USER, LDAP_PASS, $WSDL, TRUE);
			$SITEREGLIST = $SP->read("{6EECFAF1-2D97-4DC9-A34B-AD9A29498E29}", NULL, NULL, "{850F94A7-849B-44EE-89AE-731AFBBF1D81}");   // Site Code Register list, view is for ALL items (default is not!)
			$SELECT = array();
			// Enforce only 8 characters per site name
			foreach($SITEREGLIST as $SITEREGITEM) { $SELECT[substr($SITEREGITEM["title"],0,8)] = substr($SITEREGITEM["title"],0,8); }
			//foreach($SITEREGLIST as $SITEREGITEM) { $SELECT[$SITEREGITEM["title"]] = $SITEREGITEM["title"]; }
			ksort($SELECT);
			$OUTPUT .= $this->html_form_field_select("sitecode" ,"Site Code (From Sharepoint)",$SELECT  );
		}else{
			$OUTPUT .= $this->html_form_field_comment("sitecode"    ,"Site Code (Pulled from sharepoint)");
			$OUTPUT .= $this->html_form_field_hidden("sitecode"		,$this->data["sitecode"]);
		}
		$OUTPUT .= $this->html_form_field_text("name"       ,"Site Name / Description"                  );
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

        return $OUTPUT;
    }

	public function dhcp_scopes()
	{
		$OUTPUT = "";

		$IPV4BLOCK = $this->get_ipv4block();
		$IPV4NETWORK= Net_IPv4::parseAddress($IPV4BLOCK)->ip;
		$IPV4LONG   = ip2long($IPV4NETWORK);

		$SCOPES = array();

		$SCOPES["VLAN 1 - WIRED"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/22")->netmask,
										"firstip" => long2ip($IPV4LONG +   50),
										"lastip"  => long2ip($IPV4LONG + 1010),
										"extra"   => "-enableOption241",
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 5 - WIRELESS"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/22")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG + 1010),
										"extra"   => "-wirelessLease",
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 9 - VOICE"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/22")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG + 1010),
										"extra"   => "-enableTFTPServer",
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 13 - GUEST_PARTNER_JV"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/23")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG +  500),
										"extra"   => "-publicDNS",
									); //$IPV4LONG += 512; // Dont need this because no more subnets added

//		$OUTPUT .= "! Please open a service desk ticket with IM DCO - Server Administration to have SITE:{$this->data["sitecode"]} BLOCK:{$IPV4BLOCK} added in Active Directory Sites & Services!\n<pre>\n";
		$OUTPUT .= "! Please update IM DCO - Server Administration from Project Task to have SITE:{$this->data["sitecode"]} BLOCK:{$IPV4BLOCK} added in Active Directory Sites & Services!\n<pre>\n";
		foreach($SCOPES as $SCOPENAME => $SCOPE)
		{
			$OUTPUT .= <<<END
./DHCPScopeAdd.ps1 -scopeID {$SCOPE["network"]} -range {$SCOPE["firstip"]},{$SCOPE["lastip"]} -subnetMask {$SCOPE["netmask"]} -gatewayAddress {$SCOPE["gateway"]} -scopeName "{$this->data["sitecode"]} {$SCOPENAME}" -scopeDescription "{$this->data["sitecode"]} {$SCOPENAME}" {$SCOPE["extra"]}

END;
		}
		$OUTPUT .= "</pre>";

		return $OUTPUT;
	}

	public function initialize()
	{
		$OUTPUT = "";
		global $DB;

		$ASN = $this->get_asn();
		if ($ASN < 1 || $ASN > 65535)	// Could not find a pre-assigned BGP ASN in the database for this site
		{
			$OUTPUT .= "Warning: No Linked ASN Found! $ASN Attempting to autoprovision... ";
			$SEARCH = array(			// Search the pile of information for a valid ASN thats named AVAILABLE
				"category"		=> "BGP",
				"type"			=> "ASN",
				"stringfield2"	=> "AVAILABLE",
				);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)					// IF we found an available ASN, assign it!
			{
				$INFOBJECT = Information::retrieve(reset($RESULTS));	// Grab the first available ASN in the results
				$INFOBJECT->data["name"] = $this->data["sitecode"];		// Set the available ASN name = our site code
				$INFOBJECT->update();									// Save our changes to the information object
				$OUTPUT .= "Successfully assigned ASN {$INFOBJECT->data["asn"]} ID {$INFOBJECT->data["id"]} to site!";
			}else{
				$OUTPUT .= "Unable to assign ASN automatically, please assign one manually!";
			}
		}else{
			$OUTPUT .= "Successfully identified {$ASN} as assigned BGP ASN to this site!";
		}
		$OUTPUT .= "<br>\n";

		$IPV4BLOCK = $this->get_ipv4block();
		if ($IPV4BLOCK == "")			// Same as before, if we dont have a /20 assigned, go find one...
		{
			$OUTPUT .= "Warning: No Linked IPv4 Block Found! $IPV4BLOCK Attempting to autoprovision... ";
			$SEARCH = array(
				"category"		=> "ipplan",
				"type"			=> "block",
				"stringfield2"	=> "20",
				"stringfield4"	=> "AVAILABLE",
				);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)                 // IF we found an available /20 block, assign it!
			{
				$INFOBJECT = Information::retrieve(reset($RESULTS));    // Grab the first available /20 block in the results
				$INFOBJECT->data["name"] = $this->data["sitecode"];     // Set the available /20 name = our site code
				$INFOBJECT->update();                                   // Save our changes to the information object
				$OUTPUT .= "Successfully assigned IPv4 block {$INFOBJECT->data["prefix"]}/{$INFOBJECT->data["length"]} ID {$INFOBJECT->data["id"]} to site!";
			}else{
				$OUTPUT .= "Unable to assign IPv4 block automatically, please assign one manually!";
			}
		}else{
			$OUTPUT .= "Successfully identified IPv4 block {$IPV4BLOCK} assigned to this site!";
		}
		$OUTPUT .= "<br>\n";
		$DB->log($OUTPUT,1);	// End our debugging output with our asn/block info, the device & interface initialization log themselves!

		$IPV4BLOCK = $this->get_ipv4block();
		if ($IPV4BLOCK != "")			// Because we successfully grabbed an IP block, we can pre-provision devices!
		{
			$IPV4NETWORK= Net_IPv4::parseAddress($IPV4BLOCK)->ip;
			$IPV4LONG	= ip2long($IPV4NETWORK);
			$IPV4LONG += 3840; // Get us to the beginning of our last /24 for loopbacks and transit network space

			// WAN Routers
			$TYPE		= "Device_IOS_RTR_WANRR_2900";
			$CATEGORY	= $this->data['category'];
			$PARENT		= $this->data['id'];

			// WAN Router 01
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}RWA01";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 3) . "/32";;
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();

			// WAN Router 02
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}RWA02";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 4) . "/32";;
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();

			// Distribution MLS
			$TYPE		= "Device_IOS_MLS_DIST_3560X";

			// Distribution mls 01
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}SWD01";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 1) . "/32";;
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();

			// Distribution mls 02
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}SWD02";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 2) . "/32";;
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();
		}
		return $OUTPUT;
	}

}

?>
