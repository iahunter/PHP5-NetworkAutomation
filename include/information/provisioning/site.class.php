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
	public $customfunction = "DHCP_add";

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
		$datadump = \metaclassing\Utility::dumperToString($this->data);
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
	// Function to display the HTML for the page. 
	// Called by /opt/networkautomation/www/information/information-view.php
	{
		$OUTPUT = "";

		$this->html_width();

		// print out our information magic buttons
		$OUTPUT .= $this->html_detail_buttons();
		
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
		// Call CUCM Provisioning if Debug level is above 0
		if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 0 ) {
			$OUTPUT .= $this->html_callmanager_details();
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();  // Permit the user to deactivate any devices and children

		if ( !isset($this->data["id"]) )    // If this is a NEW record being added, NOT an edit
		{
			$SELECT = $this->get_site_codes();	// Get our site codes from an external source, sharepoint or service-now via API calls.
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

	public function get_site_codes()
	{
		// Instance (company = prod, companydev = dev, might be a test one as well?)
		$INSTANCE = "company";

		// Table to query, translation map in SNSoapClient class for some
		$TABLE = "location";

		// Include our library objects
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/class.Record.php");
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/Class.SoapClient.php");
		require_once(BASEDIR . "/vendor/metaclassing/snsoapclient/phpsoapclient/Class.DefaultValues.php");

		$OPTIONS = [
					"login" => LDAP_USER,
					"password" => LDAP_PASS,
					"instance" => $INSTANCE,
					"tableName" => $TABLE,
					"debug" => FALSE,
                    ];
		$SERVICENOW = new SNSoapClient($OPTIONS);

		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		// by default, this returns a maximum of 250 items...
		$RECORDS = array();     // Our final results array built by the chunk foreach
		$KEYNAME = "sys_id";    // sys_id is the current key value returned
		$CHUNKSIZE = 250;       // 250 records at a time (max supported by ServiceNow API)
		// So we are going to need the complete keys list...
		$KEYS = $SERVICENOW->getKeys();
		// and array_chunk them into separate requests
		foreach ( array_chunk($KEYS,250) as $RECORDKEYS ) {
			$SEARCH = "sys_id=" . implode( "^ORsys_id=" , $RECORDKEYS );// Form a crafted search for our key chunk
			$RECORDSRESPONSE = $SERVICENOW->getRecords($SEARCH);        // Run our search
			$RECORDS = array_merge($RECORDS , $RECORDSRESPONSE );       // Add response to our records array
		}
		// Convert the soap reply objects into an assoc array (recursively)
		$RECORDS = \metaclassing\Utility::objectToArray($RECORDS);

		$SITES = [];
		foreach($RECORDS as $RECORD) {
			if(isset($RECORD["soapRecord"]["full_name"])) {
				$SITE = substr($RECORD["soapRecord"]["full_name"],0,8);
				$SITES[$SITE] = $SITE;
			}
		}
		ksort($SITES);

		return $SITES;
	}
/**/
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

	public function dhcp_get_scopes()
	{
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
										"options" => array(	// 003 is the default gateway
															"003"	=>	long2ip($IPV4LONG +    1),
													),
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 5 - WIRELESS"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/22")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG + 1010),
										"options"   =>	array( // 051 is lease time
															"003" =>	long2ip($IPV4LONG +    1),
															"051"	=>	"36000",
														)
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 9 - VOICE"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/22")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG + 1010),
										"options"   =>	array( // 150 is TFTP server list, comma separated
															"003"	=>	long2ip($IPV4LONG +    1),
															"150"	=>	["10.252.11.14","10.252.22.14"],
														)
									); $IPV4LONG += 1024;

		$SCOPES["VLAN 13 - GUEST_PARTNER_JV"] = array(
										"network" => long2ip($IPV4LONG +    0),
										"gateway" => long2ip($IPV4LONG +    1),
										"netmask" => Net_IPv4::parseAddress(long2ip($IPV4LONG) . "/23")->netmask,
										"firstip" => long2ip($IPV4LONG +   10),
										"lastip"  => long2ip($IPV4LONG +  500),
										"options"   =>	array( // 006 is dns servers
															"003"	=>	long2ip($IPV4LONG +    1),
															"006"	=>	["8.8.8.8","8.8.4.4"],
														)
									); //$IPV4LONG += 512; // Dont need this because no more subnets added
		return $SCOPES;
	}

    public function curl_ps1_api( $ACTION, $POST = array() )
    {
		// passed this separately for no particular reason
		$POST['action'] = $ACTION;
		// create a curl handle for our URL
        $URL = "https://knewaniwp001.company.com/ps1api/v3/";
        $CURL = curl_init($URL);

		//url-ify the data for the POST
		$PARRAY = array();
		$NOQUOTES = ['action'];
		foreach($POST as $KEY => $VALUE ) {
			// handle basic arrays
			if(is_array($VALUE)) {
				foreach($VALUE as $INDEX => $ELEMENT) {
					$FARRAY[] = "{$KEY}[{$INDEX}]" . '="\"' . $ELEMENT . '"\"';
				}
			// Handle simple values as strings
			}else{
				if(!in_array($KEY,$NOQUOTES)) {
					$VALUE = '"\"' . $VALUE . '"\"';
				}
				$FARRAY[] = $KEY . '=' . $VALUE;
			}
		}
		$PSTRING = implode('&',$FARRAY);

		// setup curl options
        $OPTS = array(
								// send basic authentication as the tools service account in AD
								CURLOPT_USERPWD			=> LDAP_USER . ":" . LDAP_PASS,
                                // We will be sending POST requests
                                CURLOPT_POST            => true,
								CURLOPT_POSTFIELDS		=> $PSTRING,
								CURLOPT_HEADER			=> false,
                                // Generic client stuff
                                CURLOPT_RETURNTRANSFER  => true,
                                CURLOPT_FOLLOWLOCATION  => true,
                                CURLOPT_USERAGENT       => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
								CURLOPT_TIMEOUT			=> 30,
                                // Debugging
                                //CURLOPT_CERTINFO      => true,
                                //CURLOPT_VERBOSE       => true,
                                );
        curl_setopt_array($CURL,$OPTS);

		// execute the curl request and get our response
        $RESPONSE = curl_exec($CURL);
		// close the curl handle
		curl_close($CURL);
		// return the complete response
        return $RESPONSE;
    }

	public function dhcp_status()
	{
		$RESPONSE = $this->curl_ps1_api( 'DhcpScopeStatus', array('DhcpServer' => 'knedcpiwp001.company.com', 'ScopeID' => "10.133.32.0") );
		return \metaclassing\Utility::dumperToString(json_decode($RESPONSE,true));
	}

	public function ps1_scope_add($SERVER, $NAME, $SCOPE)
	{
		return $this->curl_ps1_api('DhcpScopeAdd', [
													"dhcpserver"		=> $SERVER,
													"startrange"		=> $SCOPE["firstip"],
													"endrange"			=> $SCOPE["lastip"],
													"subnetmask"		=> $SCOPE["netmask"],
													"scopename"			=> $this->data["sitecode"] . " " . $NAME,
													"scopedescription"	=> $this->data["sitecode"] . " " . $NAME,
												]);
	}

	public function ps1_option_add($SERVER, $SCOPE, $OPTION, $VALUE)
	{
		return $this->curl_ps1_api('DhcpOptionAdd', [
													"dhcpserver"	=> $SERVER,
													"scopeid"		=> $SCOPE["network"],
													"optionid"		=> $OPTION,
													"optionvalue"	=> $VALUE,
													]);
	}

	public function ps1_failover_add($SERVER, $SCOPE, $RELATIONSHIP)
	{
		return $this->curl_ps1_api('DhcpFailoverAdd', [
													"dhcpserver"	=> $SERVER,
													"scopeid"		=> $SCOPE["network"],
													"failovername"	=> $RELATIONSHIP,
													]);
	}

	public function dhcp_add()
	{
		$OUTPUT = "";
		$SCOPES = $this->dhcp_get_scopes();
		$dhcpserver = "knedcpiwp001.company.com";
		global $HTML;
		$js = new \metaclassing\JS;
		print "<div id='dialog' style='display: none;'>\n";
		print "<div id='message' style='font-size: 14px;'></div><br>\n";
		print $js->progressbar('progressbar');
		print "<div id='confirm' style='display: none' align=right>";
		print "<input type=button id=confirm value='Ok' ".
		        "onClick='javascript:$(&quot;#dialog&quot;).dialog(&quot;close&quot;);'></div>\n";
		print "</div>\n";
		print $js->dialog('dialog','init',array('title' => 'Loading...', 'no.close' => 1, 'height' => 240,'width' => 650));
		print $js->progressbar('progressbar','init');
		$progress = 0;
		foreach($SCOPES as $SCOPENAME => $SCOPE)
		{
			print $js->html('message', "Adding DHCP Scope {$SCOPENAME} " . $HTML->timer_diff());    $progress += 10;
			print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 7000));    \metaclassing\Utility::flush();
			$RESULT = json_decode($this->ps1_scope_add($dhcpserver, $SCOPENAME, $SCOPE), true);
			$OUTPUT .= "Scope {$SCOPENAME} " . \metaclassing\Utility::dumperToString($RESULT);
			if($RESULT["success"] == 0) { return $OUTPUT . $this->dhcp_error($js, $RESULT); }
			foreach($SCOPE["options"] as $OPTION => $VALUE)
			{
				print $js->html('message',"Adding DHCP Options for scope {$SCOPENAME} " . $HTML->timer_diff());               $progress += 7;
				print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 4000)); \metaclassing\Utility::flush();
				$RESULT = json_decode($this->ps1_option_add($dhcpserver, $SCOPE, $OPTION, $VALUE), true);
				$OUTPUT .= "Option {$OPTION} " . \metaclassing\Utility::dumperToString($RESULT);
				if($RESULT["success"] == 0) { return $OUTPUT . $this->dhcp_error($js, $RESULT); }
			}
			print $js->html('message',"Adding DHCP failover for scope {$SCOPENAME} " . $HTML->timer_diff());               $progress += 10;
			print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 5000)); \metaclassing\Utility::flush();
			$RESULT = json_decode($this->ps1_failover_add($dhcpserver, $SCOPE, "knedcpiwp001.company.com-knedcpiwp002.company.com"), true);
			$OUTPUT .= "Failover {$SCOPENAME} " . \metaclassing\Utility::dumperToString($RESULT);
			if($RESULT["success"] == 0) { return $OUTPUT . $this->dhcp_error($js, $RESULT); }
		}
		print $js->html('message',"DONE DOING THE NEEDFUL! " . $HTML->timer_diff());     $progress = 100;
		print $js->progressbar('progressbar','animateprogress',array('value' => $progress,'duration' => 0));    \metaclassing\Utility::flush();
		print $js->dialog('dialog','overlay',array('overlay.color' => '009900'));
		print $js->dialog('dialog','title',array('title' => 'Success', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));
		print $js->progressbar('progressbar','hide');
		print $js->show('confirm'); \metaclassing\Utility::flush();
		return $OUTPUT;
	}

	public function dhcp_error($js, $RESULT)
	{
		print $js->dialog('dialog','overlay',array('overlay.color' => '990000'));
		print $js->dialog('dialog','title',array('title' => 'Failure', 'title.color' => 'ffffff', 'title.bg.color' => '009900'));
		print $js->progressbar('progressbar','hide');
		print $js->show('confirm'); \metaclassing\Utility::flush();
		print "{$RESULT["error"]}<br>\n";
		$MESSAGE = "ERROR: " . $RESULT["error"];
		//$DB->log($MESSAGE);/**/
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
			$DEVICE->data["name"]	= "{$this->data["sitecode"]}RWA01";
			$DEVICE->data["name"] = "{$this->data["sitecode"]}RWA01";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 3) . "/32";
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
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 4) . "/32";
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();

			/*
			$TYPE		= "Device_IOS_RTR_WANSS_1900";
			
			// WAN Router 03
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}RWA03";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 0) . "/32";	// LOL ping .0 and it works because reasons
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();
			*/
			
			// Distribution MLS
			$TYPE		= "Device_IOS_MLS_DIST_3560X";

			// Distribution mls 01
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}SWD01";
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 1) . "/32";
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
			$DEVICE->data["mgmtip4"] = long2ip($IPV4LONG + 2) . "/32";
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();

			// Access Switches
			$TYPE		= "Device_IOS_SWI_ACC_2960X_48";
			/*
			// Access Switch 01
			$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
			$DEVICE->data["name"] = "{$this->data["sitecode"]}SWA01";
			$IPV4LONG	= ip2long($IPV4NETWORK);
			$DEVICE->data["mgmtip4"]= long2ip($IPV4LONG + 11) . "/22";
			$DEVICE->data["mgmtgw"]	= long2ip($IPV4LONG + 1);
			$DEVICE->data["mgmtint"]= "Vlan1";
			$ID = $DEVICE->insert();
			$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
			$DB->log($MESSAGE);
			$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
			$DEVICE = Information::retrieve($ID);
			$OUTPUT .= $DEVICE->initialize();
			$DEVICE->update();	/**/

			//Create Access Switches
			foreach( range(1,4) as $switchno )
			{
				$DEVICE		= Information::create($TYPE,$CATEGORY,$PARENT);
				$DEVICE->data["name"] = "{$this->data["sitecode"]}SWA0{$switchno}";
				$IPV4LONG	= ip2long($IPV4NETWORK);
				$DEVICE->data["mgmtip4"]= long2ip($IPV4LONG + 10 + $switchno) . "/22";
				$DEVICE->data["mgmtgw"]	= long2ip($IPV4LONG + 1);
				$DEVICE->data["mgmtint"]= "Vlan1";
				$ID = $DEVICE->insert();
				$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
				$DB->log($MESSAGE);
				$OUTPUT .= "Auto Initialized: {$MESSAGE}<br>\n";
				$DEVICE = Information::retrieve($ID);
				$OUTPUT .= $DEVICE->initialize();
				$DEVICE->update();
			}
		}
		return $OUTPUT;
	}

	public function html_callmanager_details()
	{
		$OUTPUT = "";
		require_once "/opt/networkautomation/vendor/autoload.php";
		// Connect to THIS callmanager and use this schema version
		$URL    = "https://10.252.22.11:8443/axl"; // Prod CUCM
		//$URL    = "https://192.168.249.11:8443/axl"; // Lab CUCM
		$SCHEMA = BASEDIR . "/axl/schema/10.5/AXLAPI.wsdl";

		try {
			$CUCM = new \CallmanagerAXL\Callmanager($URL,$SCHEMA);
			$CMSITES = $CUCM->get_site_names();
			//$OUTPUT .= \metaclassing\Utility::dumperToString($CMSITES);

			if ( in_array($this->data["sitecode"],$CMSITES) ) {
				$OUTPUT .= "I found this site in callmanager!\n";
				if(!isset($this->data["cucmprovisioned"])) {
					print "CUCM provisioning state tracking not previously defined for this site, setting now\n";
					$this->data["cucmprovisioned"] = 1;
					$this->update();
				}
				$STUFF = $CUCM->get_all_object_types_by_site($this->data["sitecode"]);
//				$STUFF = $CUCM->get_all_object_type_details_by_site($this->data["sitecode"]);
//				$OUTPUT .= \metaclassing\Utility::dumperToString($STUFF);
				$OUTPUT .= $this->html_callmanager_object_type_accordion();
			}else{
				//$OUTPUT .= "This site does NOT appear to exist in callmanager, so ill print the add-new-cm-site button!\n";
				if(!isset($this->data["cucmprovisioned"])) {
					print "CUCM provisioning state tracking not previously defined for this site, setting now\n";
					$this->data["cucmprovisioned"] = 0;
					$this->update();
				}
				$OUTPUT .= <<<END
				<table border="0" cellspacing="0" cellpadding="1">
					<tr>
						<td align="right">
							<ul class="object-tools">
								<li>
									<a href="/information/information-action.php?id={$this->data['id']}&action=provision_callmanager" class="addlink">Provision Callmanager</a>
								</li>
							</ul>
						</td>
					</tr>
				</table>
END;
			}
/*			if($_SESSION["DEBUG"] <= 1) {
				$TOTALTIME = array_sum( $CUCM->assoc_key_values_to_array( $CUCM->SOAPCALLS , "time" ) );
				global $HTML;
				print $HTML->quicktable_assoc_report("SOAP Calls<br>{$TOTALTIME} sec",["call" , "time"],$CUCM->SOAPCALLS);
				\metaclassing\Utility::dumper($CUCM->SOAPCALLS);
			}/**/
		} catch (\Exception $E) {
			$OUTPUT .= "Error communicating with callmanager: {$E->getMessage()}\n";
		}

		return $OUTPUT;
	}

	public function html_form_provision_callmanager()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();

		// SRST Router IP address
		$OUTPUT .= $this->html_form_field_text("srstip"		,"SRST router IP address");
		// H323 Gateway IP address
		$OUTPUT .= $this->html_form_field_textarea("h323ip"	,"H323 gateway IP addresses, one per line");
		// CUCM Timezone
		require_once "/opt/networkautomation/vendor/autoload.php";
		// Connect to THIS callmanager and use this schema version
		$URL    = "https://10.252.22.11:8443/axl"; // Prod CUCM
		//$URL    = "https://192.168.249.11:8443/axl"; // Lab CUCM
		$SCHEMA = BASEDIR . "/axl/schema/10.5/AXLAPI.wsdl";
		try {
			$CUCM = new \CallmanagerAXL\Callmanager($URL,$SCHEMA);
			$TIMEZONE = $this->array_to_assoc( $CUCM->get_object_type_by_site("","DateTimeGroup") );
			if(isset($TIMEZONE["CMLocal"])) { unset($TIMEZONE["CMLocal"]); }
			asort($TIMEZONE);
			//\metaclassing\Utility::dumper($TIMEZONE);
		} catch(\Exception $E) {
			$OUTPUT .= "Error communicating with callmanager: {$E->getMessage()}\n";
			return $OUTPUT;
		}
		$OUTPUT .= $this->html_form_field_select("timezone" ,"Timezone & Format",$TIMEZONE	);
		// NPA and NXX 3 digit codes, 200-999 valid
		$NPANXX = $this->array_to_assoc( range(200,999) );
		$OUTPUT .= $this->html_form_field_select("npa"		,"NPA (###)"		,$NPANXX	);
		$OUTPUT .= $this->html_form_field_select("nxx"		,"NXX (###)"		,$NPANXX	);
		// DID Ranges - multiple @ one per line, regular expression formats
		$this->data["didrangeexamples"] = <<<END
<table>
	<tr>
		<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>4020 - 4099 = <strong>40[2-9]X</strong></td>
	</tr>
	<tr>
		<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
		<td>4300 - 4499 = <strong>4[3-4]XX</strong></td>
	</tr>
</table>
END;
		$OUTPUT .= $this->html_form_field_comment("didrangeexamples","DID Range Examples");
		$OUTPUT .= $this->html_form_field_textarea("didranges"		,"DID ranges, one per line"	);
		$OUTPUT .= $this->html_form_field_text("operator"			,"4 digit operator extension");

		$OUTPUT .= <<<END
					<tr><td>
						<input type="hidden" name="id"      value="{$this->data["id"]}">
						<input type="hidden" name="action"  value="provision_callmanager">
						<input type="submit"                value="Provision Callmanager Site">
					</td></tr>
				</table>
			</form>
		</div>
END;
		return $OUTPUT;
	}

	public function provision_callmanager($POST)
	{
		// If the provisioning state is UNKNOWN dont let them continue
		if(!isset($this->data["cucmprovisioned"])) {
			$OUTPUT = "This sites CUCM provisioning state is unknown, please view the site to set this first\n";
			return $OUTPUT;
		}
		// If the site is already provisioned, dont let them continue
		if($this->data["cucmprovisioned"]) {
			$OUTPUT = "This site is already showing as provisioned in callmanager\n";
			return $OUTPUT;
		}
		// Handle GET method from user, print out the form to submit
		if($_SERVER['REQUEST_METHOD'] == "GET") {
			$OUTPUT = $this->html_form_provision_callmanager();
			return $OUTPUT;
		}
		// Input validation checking for form submitted

		// If the SRST IP is set, has contents, and validates as an IP address
		if(!isset($POST["srstip"]) || !$POST["srstip"] || !filter_var($POST["srstip"],FILTER_VALIDATE_IP)) {
			return "Error: SRST IP missing or invalid";
		}
		$SRSTIP = $POST["srstip"];

		// Turn the users text into an array of IP addresses
		$H323TEXT = "";
		$H323LIST = [];
		if(isset($POST["h323ip"]) && $POST["h323ip"]) {
			$H323TEXT = $POST["h323ip"];
		}
		$H323LIST = preg_split( '/\r\n|\r|\n/', $H323TEXT );

		// Loop through H323 IP addresses in an array and validate them as IPs
		foreach($H323LIST as $KEY => $H323IP) {
			// If the line is blank rip it out of the list
			if(trim($H323IP) == "") {
				unset($H323LIST[$KEY]);
				continue;
			}
			// If the line has content but is NOT an ip address, abort
			if(!filter_var($H323IP,FILTER_VALIDATE_IP) ) {
				return "Error, one of the H323 IPs provided is not valid: {$H323IP}";
			}
		}
		$H323LIST = array_values($H323LIST);

		// Check their timezone
		if(!isset($POST["timezone"]) || !$POST["timezone"]) {
			return "Error, no timezone selected";
		}
		$TIMEZONE = $POST["timezone"];

		// Check their NPA
		if(!isset($POST["npa"]) || !$POST["npa"]) {
			return "Error, no npa selected";
		}
		$NPA = $POST["npa"];

		// Check their NXX
		if(!isset($POST["nxx"]) || !$POST["nxx"]) {
			return "Error, no nxx selected";
		}
		$NXX = $POST["nxx"];

		// Turn the users text into an array of translation patterns
		$DIDTEXT = "";
		if(isset($POST["didranges"]) && $POST["didranges"]) {
			$DIDTEXT = $POST["didranges"];
		}else{
			return "No DID ranges provided";
		}
		$DIDLIST = preg_split( '/\r\n|\r|\n/', $DIDTEXT );
		if(!count($DIDLIST)) {
			return "No DID ranges found";
		}
		// Loop through translation pattern DID sections and validate them against callmanagers data dictionary
		foreach($DIDLIST as $KEY => $DID) {
			// Trim off any whitespace around the DID range
			$DID = trim($DID);
			$DIDLIST[$KEY] = $DID;
			// If the line is blank rip it out of the list
			if(!$DID) {
				unset($DIDLIST[$KEY]);
				continue;
			}
			// If the line has content but is NOT a valid DID thing, then abort
			$REGEX = "/^[]0-9X[-]{4,14}$/"; // This is from CUCM 10.5's data dictionary... and modified
			if(!preg_match($REGEX,$DID) ) {
				return "Error, one of the DID ranges provided is not valid: {$DID}";
			}
		}
		$DIDLIST = array_values($DIDLIST);

		// Check for an optional operator extension
		$OPERATOR = "";
		if(isset($POST["operator"]) && $POST["operator"]) {
			$OPERATOR = $POST["operator"];
		}

		// Use user input to decide what CUCM subscribers to home this new site to

		// If the users site code is KHO, dump them on our subscribers
		$SITECODE = strtoupper($this->data["sitecode"]);
		if(substr($SITECODE,0,2) == "KHO") {
			$CUCM1 = "KHONEMDCVCS02";
			$CUCM2 = "KHONESDCVCS06";
		// Otherwise if they are KOS, dump them there
		}elseif( substr($SITECODE,0,2) == "KOS" ){
			$CUCM1 = "KHONESDCVCS04";
			$CUCM2 = "KHONEMDCVCS05";
		// Otherwise if they are EAST or CENTRAL time
		}elseif(preg_match("/(eastern|central)+/i",$TIMEZONE)){
			$CUCM1 = "KHONESDCVCS04";
			$CUCM2 = "KHONESDCVCS06";
		}else{
			$CUCM1 = "KHONESDCVCS03";
			$CUCM2 = "KHONEMDCVCS05";
		}

		print "Launching AXL CUCM provisioning... Please wait...<br>"; \metaclassing\Utility::flush();

		// Final user information required to provision a CUCM SITE:
		return $this->provision_callmanager_axl(
												$SITECODE,
												$SRSTIP,
												$H323LIST,
												$TIMEZONE,
												$NPA,
												$NXX,
												$DIDLIST,
												$CUCM1,
												$CUCM2,
												$OPERATOR
												);
	}

	// This is a wrapper function used for all the add operations to simplify error handling and output for CUCM provisioning
	function wrap_add_object($DATA,$TYPE,$SITE,$CUCM) {
		try{
			print "Attempting to add a {$TYPE} for {$SITE}:\n";
			$REPLY = $CUCM->add_object_type_by_assoc($DATA,$TYPE);
			print "{$TYPE} CREATED: {$REPLY}\n\n";
		}catch (\Exception $E) {
			print "Exception adding object type {$TYPE} for site {$SITE}:\n" .
				  "{$E->getMessage()}\n" .
				  "Stack trace:\n" .
				  "{$E->getTraceAsString()}\n" .
				  "Data sent:\n";
				  print_r($DATA);
		}
		\metaclassing\Utility::flush();
	}

	private function provision_callmanager_axl(
												$SITE,
												$SRSTIP,
												$H323LIST,
												$TIMEZONE,
												$NPA,
												$NXX,
												$DIDLIST,
												$CUCM1,
												$CUCM2,
												$OPERATOR
											)
	{
		print "<pre>"; \metaclassing\Utility::flush();

		// Load Travis's CUCM AXL wrapper library
		require_once "/opt/networkautomation/vendor/autoload.php";
		// Connect to THIS callmanager and use this schema version
		$URL    = "https://10.252.22.11:8443/axl"; // Prod CUCM
		//$URL    = "https://192.168.249.11:8443/axl"; // Lab CUCM
		$SCHEMA = BASEDIR . "/axl/schema/10.5/AXLAPI.wsdl";

		// Our CUCM connection
		$CUCM = new \CallmanagerAXL\Callmanager($URL,$SCHEMA);


		// 1 - Add a SRST router

		// Calculated data structure
		$TYPE = "Srst";
		$DATA = [
				"name"		=> "SRST_{$SITE}",
				"ipAddress"	=> $SRSTIP,
				"port"		=> 2000,
				"SipPort"	=> 5060,
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 2 - Add a route partition

		// Calculated variables
		$TYPE = "RoutePartition";
		// Prepared datastructure
		$DATA = [
				"name"							=> $SITE,
				"description"					=> $SITE,
				"useOriginatingDeviceTimeZone"	=> "true",
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 3 - Add a CSS

		// Calculated variables
		$TYPE = "Css";
		// Prepared datastructure
		$DATA = [
				"name"			=> "CSS_{$SITE}",
				"description"	=> "CSS for {$SITE}",
				"members"		=> [
									"member" => [
													[
													"routePartitionName"=> $SITE,
													"index"				=> 1,
													],
													[
													"routePartitionName"=> "Global-All-Lines",
													"index"				=> 2,
													],
												],
									],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 4 - Add a location

		// Calculated variables
		$TYPE = "Location";
		// Prepared datastructure
		$DATA = [
				"name"					=> "LOC_{$SITE}",
				"withinAudioBandwidth"	=> "0",
				"withinVideoBandwidth"	=> "0",
				"withinImmersiveKbits"	=> "0",
				"betweenLocations"		=> [],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 5 - Add a region

		// Calculated variables
		$TYPE = "Region";
		// Prepared datastructure
		$DATA = [
				"name"				=> "R_{$SITE}",
				"relatedRegions"	=> [
										"relatedRegion" => [
																[
																"regionName"				=> "Default",
																"bandwidth"					=> "G.729",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_711",
																"bandwidth"					=> "G.711",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_729",
																"bandwidth"					=> "G.729",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_{$SITE}",
																"bandwidth"					=> "G.711",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_FAX",
																"bandwidth"					=> "G.711",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_GW",
																"bandwidth"					=> "G.711",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
																[
																"regionName"				=> "R_Voicemail",
																"bandwidth"					=> "G.729",
																"videoBandwidth"			=> "384",
																"lossyNetwork"				=> "",
																"codecPreference"			=> "",
																"immersiveVideoBandwidth"	=> "",
																],
															],
										],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 6 - Add a call mangler group

		// Calculated variables
		$TYPE = "CallManagerGroup";
		// Prepared datastructure
		$DATA = [
				"name"		=> "CMG-{$SITE}",
				"members"	=> [
								"member" => [
												[
												"callManagerName"	=> $CUCM1,
												"priority"			=> "1",
												],
												[
												"callManagerName"	=> $CUCM2,
												"priority"			=> "2",
												],
											],
								],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 7 - Add a device pool

		// Calculated variables
		$TYPE = "DevicePool";
		// Prepared datastructure
		$DATA = [
				"name"					=> "DP_{$SITE}",
				"dateTimeSettingName"	=> $TIMEZONE,
				"callManagerGroupName"	=> "CMG-{$SITE}",
				"regionName"			=> "R_{$SITE}",
				"srstName"				=> "SRST_{$SITE}",
				"locationName"			=> "LOC_{$SITE}",
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 8 - Add a conference bridge

		// Calculated variables
		$TYPE = "ConferenceBridge";
		// Prepared datastructure
		$DATA = [
				"name"			=> "{$SITE}_CFB",
				"description"	=> "Conference bridge for {$SITE}",
				"product"		=> "Cisco IOS Enhanced Conference Bridge",
				"devicePoolName"=> "DP_{$SITE}",
				"locationName"	=> "LOC_{$SITE}",
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 9 - Add media termination point 1

		// Calculated variables
		$TYPE = "Mtp";
		// Prepared datastructure
		$DATA = [
				"name"				=> "{$SITE}_729",
				"description"		=> "G729 MTP for {$SITE}",
				"mtpType"			=> "Cisco IOS Enhanced Software Media Termination Point",
				"devicePoolName"	=> "DP_{$SITE}",
				"trustedRelayPoint"	=> "false",
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 10 - Add media termination point 2

		// Calculated variables
		$TYPE = "Mtp";
		// Prepared datastructure
		$DATA = [
				"name"				=> "{$SITE}_711",
				"description"		=> "G711 MTP for {$SITE}",
				"mtpType"			=> "Cisco IOS Enhanced Software Media Termination Point",
				"devicePoolName"	=> "DP_{$SITE}",
				"trustedRelayPoint"	=> "false",
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 11 - Add a media resource group

		// Calculated variables
		$TYPE = "MediaResourceGroup";
		// Prepared datastructure
		$DATA = [
				"name"			=> "MRG_{$SITE}",
				"description"	=> "{$SITE} Media Resources",
				"multicast"		=> "false",
				"members"		=> [
									"member" => [
													[
													"deviceName"	=> "{$SITE}_711",
													],
													[
													"deviceName"	=> "{$SITE}_729",
													],
													[
													"deviceName"	=> "{$SITE}_CFB",
													],
												],
									],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 12 - Add a media resource list

		// Calculated variables
		$TYPE = "MediaResourceList";
		// Prepared datastructure
		$DATA = [
				"name"			=> "MRGL_{$SITE}",
				"members"		=> [
									"member"	=> [
														[
														"mediaResourceGroupName"	=> "MRG_{$SITE}",
														"order"						=> "0",
														],
														[
														"mediaResourceGroupName"	=> "MRG_Sub1_Resources",
														"order"						=> "1",
														],
														[
														"mediaResourceGroupName"	=> "MRG_Pub_Resources",
														"order"						=> "2",
														],
													],
									],
				];
		// Run the operation
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 13 - Add H323 Gateways

		$ROUTERMODEL = "Cisco 2951";
		// Calculated variables
		$TYPE = "H323Gateway";
		// Prepared datastructure
		foreach($H323LIST as $H323IP) {
			$DATA = [
					"name"						=> $H323IP,
					"description"				=> "{$SITE} {$H323IP} {$ROUTERMODEL}",
					"callingSearchSpaceName"	=> "CSS_{$SITE}",
					"devicePoolName"			=> "DP_{$SITE}",
					"locationName"				=> "LOC_{$SITE}",
					"product"					=> "H.323 Gateway",
					"class"						=> "Gateway",
					"protocol"					=> "H.225",
					"protocolSide"				=> "Network",
					"signalingPort"				=> "1720",
					"tunneledProtocol"			=> "",
					"useTrustedRelayPoint"		=> "",
					"packetCaptureMode"			=> "",
					"callingPartySelection"		=> "",
					"callingLineIdPresentation"	=> "",
					"calledPartyIeNumberType"	=> "",
					"callingPartyIeNumberType"	=> "",
					"calledNumberingPlan"		=> "",
					"callingNumberingPlan"		=> "",
					];
			$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);
		}


		// 14 - Add a route group

		// Calculated variables
		$TYPE = "RouteGroup";
		// Prepared datastructure
		$DATA = [
				"name"					=> "RG_{$SITE}",
				"distributionAlgorithm"	=> "Top Down",
				"members"				=> [
											"member"	=> [],
											],
				];
		// Calculate multiple members to add to this array with order numbers
		$i = 1;
		foreach($H323LIST as $H323IP) {
			$H323MEMBER = [
							"deviceName"			=> $H323IP,
							// Increment order @ each iteration through previous loop!
							"deviceSelectionOrder"	=> $i++,
							"port"					=> "0",
							];
			array_push($DATA["members"]["member"],$H323MEMBER);
		}
		$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);


		// 15 - Update an existing device pool to add the new route group above

		// Calculated variables
		$TYPE = "DevicePool";
		// Update these fields in the device pool object for this site
		$DATA = [
				"name"					=> "DP_{$SITE}",
				"mediaResourceListName"	=> "MRGL_{$SITE}",
				"localRouteGroup"		=> [
											"name"		=> "Standard Local Route Group",
											"value"		=> "RG_{$SITE}",
											],
				];
		// Run the update operation
		try{
			print "Attempting to update object type {$TYPE} for {$SITE}:\n";
			$REPLY = $CUCM->update_object_type_by_assoc($DATA,$TYPE);
			print "{$TYPE} UPDATED: {$REPLY}\n";
		}catch (\Exception $E) {
			print "Exception updating object type {$TYPE} for site {$SITE}:\n" .
				  "{$E->getMessage()}\n" .
				  "Stack trace:\n" .
				  "{$E->getTraceAsString()}\n" .
				  "Data sent:\n";
				  print_r($DATA);
		}


		// 16 - Create our translation patterns from user input

		// Calculated variables
		$TYPE = "TransPattern";

		// Prepare and add datastructures
		foreach($DIDLIST as $PATTERN) {
			$DATA = [
					"routePartitionName"			=> $SITE,
					"pattern"						=> $PATTERN,
					"calledPartyTransformationMask"	=> "{$NPA}{$NXX}XXXX",
					"callingSearchSpaceName"		=> "CSS_{$SITE}",
					"description"					=> "{$SITE} dial pattern {$PATTERN}",
					"usage"							=> "Translation",
					];
			$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);

			$DATA = [
					"routePartitionName"			=> $SITE,
					"pattern"						=> "*{$PATTERN}",
					"calledPartyTransformationMask"	=> "*{$NPA}{$NXX}XXXX",
					"callingSearchSpaceName"		=> "CSS_{$SITE}",
					"description"					=> "{$SITE} voicemail pattern {$PATTERN}",
					"usage"							=> "Translation",
					];
			$this->wrap_add_object($DATA,$TYPE,$SITE,$CUCM);
		}

		print "</pre>"; \metaclassing\Utility::flush();
		return "CUCM Provision Function Completed";
	}

	public function html_callmanager_object_type_accordion()
	{
		// Load Travis's CUCM AXL wrapper library
		require_once "/opt/networkautomation/vendor/autoload.php";
		// Connect to THIS callmanager and use this schema version
        $URL    = "https://10.252.22.11:8443/axl"; // Prod CUCM
		//$URL    = "https://192.168.249.11:8443/axl"; // Lab CUCM
		$SCHEMA = BASEDIR . "/axl/schema/10.5/AXLAPI.wsdl";

		// Our CUCM connection
		$CUCM = new \CallmanagerAXL\Callmanager($URL,$SCHEMA);

		// Get valid data types out of call mangler
		$TYPES = $CUCM->object_types();
		$OBJLIST = $CUCM->get_all_object_types_by_site($this->data["sitecode"]);
		$OBJDUMP = \metaclassing\Utility::dumperToString($OBJLIST);
		//$STUFF = $CUCM->get_all_object_type_details_by_site($this->data["sitecode"]);

		$OUTPUT = <<<END
				<div class="margin0" width="60%">
					<div class="tabs">
						<ul>
							<li><a href="#tabs-{$this->data['id']}">{$this->data["sitecode"]}</a>
END;
		// Builds Tabs for each object Type 
        foreach($TYPES as $TYPE) 
            {
			$OUTPUT .= <<<END
							<li><a href="/information/information-raw.php?id={$this->data['id']}&method=html_callmanager_get_object_by_type&type={$TYPE}">{$TYPE}</a></li>
END;
		}
		$OUTPUT .= <<<END
						</ul>
						<div id="tabs-{$this->data['id']}"><a href="/information/information-view.php?id={$this->data['id']}" class="bluelink">Call Manager Objects Identified:<br> {$OBJDUMP}</div>
					</div>
				</div>
END;

		$OUTPUT .= "</div>\n";
		return $OUTPUT;
	}

	public function html_callmanager_get_object_by_type($GET)
	{
		// Make sure the stupid user passes us a type to query for
		if(!isset($GET["type"]) || !$GET["type"]) {
			return "Error, type not found in request.";
		}
		$TYPE = $GET["type"];

		// Load Travis's CUCM AXL wrapper library
		require_once "/opt/networkautomation/vendor/autoload.php";
		// Connect to THIS callmanager and use this schema version
        $URL    = "https://10.252.22.11:8443/axl"; // Prod CUCM
		//$URL    = "https://192.168.249.11:8443/axl"; // Lab CUCM
		$SCHEMA = BASEDIR . "/axl/schema/10.5/AXLAPI.wsdl";

		// Our CUCM connection
		$CUCM = new \CallmanagerAXL\Callmanager($URL,$SCHEMA);

		// Get valid data types out of call mangler
		$TYPES = $CUCM->object_types();
		if(!in_array($TYPE,$TYPES)) {
			return "Type passed {$TYPE} does not appear to be supported by the library";
		}

		// Suck all the UUIDs for valid objects for this site code
		$OBJLIST = $CUCM->get_all_object_types_by_site($this->data["sitecode"]);
		// And limit it to the type we care about
		$TYPELIST = $OBJLIST[$TYPE];
		if(!count($TYPELIST)) {
			return "Could not find any associated objects of the requested type for this site.";
		}
		// Loop through each UUID and grab its content from CUCM
		foreach($TYPELIST as $UUID => $NAME) {
			$OBJECT = $CUCM->get_object_type_by_uuid($UUID, $TYPE);
			\metaclassing\Utility::dumper($OBJECT); \metaclassing\Utility::flush();
		}
		return "I am so smart. S M R T.";
	}

}
