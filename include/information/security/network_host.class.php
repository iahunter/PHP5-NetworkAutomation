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

require_once "information/security/network.class.php";

class Security_Network_Host extends Security_Network
{
	public $category = "Security";
	public $type = "Security_Network_Host";
	public $customfunction = "";

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		if ( !filter_var($NEWDATA["ip4"], FILTER_VALIDATE_IP) )
		{
			$this->data['error'] .= "ERROR: {$NEWDATA["ip4"]} does not appear to be a valid IPv4 address!\n";
			return 0;
		}

		// If we were provided a non-blank IPv6 address AND its not valid...
		if ( isset($NEWDATA["ip6"]) && $NEWDATA["ip6"] != "" && !filter_var($NEWDATA["ip6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) )
		{
			$this->data['error'] .= "ERROR: {$NEWDATA["ip6"]} does not appear to be a valid IPv6 address!\n";
			return 0;
		}
/**/
		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing information with the same name!
					"category"		=> $this->category,
					"stringfield1"	=> $NEWDATA["name"],
					);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)
			{
				$DUPLICATE = reset($RESULTS);
				$this->data['error'] .= "ERROR: Found duplicate {$this->category}/{$this->type} ID {$DUPLICATE} with {$NEWDATA["name"]}!\n";
				return 0;
			}
		}

		$DEBUG = new Debug(DEBUG_EMAIL);
		if (isset($this->data["name"])) { $NAME = $this->data["name"]; }else{ $NAME = $NEWDATA["name"]; }
		$DEBUG->message("SECURITY HOST UPDATED! ID {$this->data["id"]}<br>\n<a href='" . BASEURL . "information/information-view.php?id={$this->data["id"]}'>Host {$NAME} / {$NEWDATA["ip4"]}</a>!\n",0);

		return 1;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Host Name"						);
		$OUTPUT .= $this->html_form_field_text("ip4"		,"IPv4 Address (1.2.3.4)"			);
		$OUTPUT .= $this->html_form_field_text("ip6"		,"IPv6 Address (1:2:A:B:C:D:E:F)"	);
		$SEARCH = array(			// Search existing information for all hostgroups
					"category"		=> "Security",
					"type"			=> "Zone",
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for HostGroups ordered by stringfield1 (name)
		$OUTPUT .= $this->html_form_field_select("zone",	"Security Zone"	,$this->assoc_select_name($RESULTS)	);
		$OUTPUT .= $this->html_form_field_text("description","Description"						);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config_object($EXTRA = "")
	{
		$OUTPUT = "";

		$OUTPUT .= Utility::last_stack_call(new Exception);
		$OUTPUT .= "!Network {$this->data["id"]} CONFIGURATION: {$this->data["ip4"]} {$this->data["ip6"]} {$this->data["zone"]} {$this->data["description"]}\n";
		$OUTPUT .= "object network OBJ_NET_{$this->data["id"]}\n";
		$OUTPUT .= "  description ID {$this->data["id"]} NAME {$this->data["name"]} DESCRIPTION {$this->data["description"]}\n";
		$OUTPUT .= "  host {$this->data["ip4"]}\n";
		if ($EXTRA) { $OUTPUT .= "  {$EXTRA}\n"; }
		$OUTPUT .= " exit\n";

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$OUTPUT .= "  " . Utility::last_stack_call(new Exception);
		$OUTPUT .= "  !Network {$this->data["id"]} CONFIGURATION: {$this->data["ip4"]} {$this->data["ip6"]} {$this->data["zone"]} {$this->data["description"]}\n";
		$OUTPUT .= "  network-object object OBJ_NET_{$this->data["id"]}\n";

		return $OUTPUT;
	}

}

?>
