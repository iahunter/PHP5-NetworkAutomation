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

require_once "information/security/firewall.class.php";

class Security_Firewall_Interface		extends Security_Firewall
{
	public $type = "Security_Firewall_Interface";
	public $customfunction = "";

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		return 1;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 200;	// Name
		$this->html_width[$i++] = 200;	// Zone
		$this->html_width[$i++] = 100;	// SecurityLevel
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
			<caption class="report">Firewall Interface List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Security Zone</th>
					<th class="report" width="{$this->html_width[$i++]}">Security Level</th>
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
		if ( isset($this->data["zone"]) && $this->data["zone"] > 0 ) { $ZONE = Information::retrieve($this->data["zone"]); $ZONENAME = $ZONE->data["name"]; }else{ $ZONENAME = "None"; }
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$ZONENAME}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["securitylevel"]}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$columns = count($this->html_width)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Firewall Interface Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Security Zone</th>
					<th class="report" width="{$this->html_width[$i++]}">Security Level</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);
		$rowclass = "row".(($i % 2)+1); $i++;
		$CREATED_BY	 = $this->created_by();
		$CREATED_WHEN	= $this->created_when();
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Created by {$CREATED_BY} on {$CREATED_WHEN}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);

		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public static function sort_by_security_level($a,$b)	// Must be public STATIC for usort to work!
	{
		if($a->data["securitylevel"] < $b->data["securitylevel"]) { return -1 ; }	// -1 move down if security level is lower
		if($a->data["securitylevel"] == $b->data["securitylevel"]) { return 0 ; }	// 0 if stay the same, security level equal
		if($a->data["securitylevel"] > $b->data["securitylevel"]) { return 1 ; }	// 1 move up if security level is higher
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("name"		,"Firewall Interface Name"								);
		$SEARCH = array(
					"category"		=> "Security",
					"type"			=> "Zone",
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for Zones ordered by stringfield1 (name)
		$OUTPUT .= $this->html_form_field_select("zone"		,"Security Zone"	,$this->assoc_select_name($RESULTS)	);
		$SELECT = array(
			"0" => "0",
			"50" => "50",
			"100" => "100",
		);
		$OUTPUT .= $this->html_form_field_select("securitylevel","Security Level"	,$SELECT);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
