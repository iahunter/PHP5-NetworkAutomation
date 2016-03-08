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

class Provisioning_Interface	extends Information
{
	public $category = "Provisioning";
	public $type = "Provisioning_Interface";
	public $customfunction = "Config";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'		]);
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 170;	// Name
		$this->html_width[$i++] = 300;	// Description
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
			<caption class="report">Provisioning Interface List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Interface Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Interface Description</th>
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
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['description']}</td>
				</tr>
END;
		return $OUTPUT;
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
			<caption class="report">Provisioning Interface Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);

		$datadump = \metaclassing\Utility::dumperToString($this->data);

		$rowclass = "row".(($i % 2)+1); $i++;
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Interface Details:<pre>{$datadump}</pre></td></tr>
END;

		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

			<script type="text/javascript" language="javascript">

				function changeLayer(layer) {
					if (layer == '') {
						document.getElementById('l2interface').style.display = 'none';
						document.getElementById('l3interface').style.display = 'none';
					}
					if (layer == '2') {
						document.getElementById('l2interface').style.display = 'block';
						document.getElementById('l3interface').style.display = 'none';
					}
					if (layer == 3) {
						document.getElementById('l2interface').style.display = 'none';
						document.getElementById('l3interface').style.display = 'block';
					}
				}

				$(document).ready(function() { changeLayer('{$this->data['layer']}'); });

			</script>
END;
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();  // Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("name"				,"Full Interface Name (GigabitEthernet5/7.100)");
		$OUTPUT .= $this->html_form_field_textarea("description"	,"Interface Description");
		$OUTPUT .= <<<END
				<tr><td><strong>Layer 2 or 3 Interface?: </strong>
					<select name="layer" size="1" onchange="changeLayer(this.options[this.selectedIndex].value)">
END;
		if (isset($this->data['layer']))
		{
			$OUTPUT .= <<<END
			<option value="{$this->data['layer']}">{$this->data['layer']}</option>
END;
		}else{
			$OUTPUT .= <<<END
			<option value=""></option>
END;
		}
		if (preg_match("/_rtr_/i",$this->parent()->data['type'],$MATCH))
		{
			$OUTPUT .= <<<END
				<option value="3">3</option>
END;
		}
		if (preg_match("/_mls_/i",$this->parent()->data['type'],$MATCH))
		{
			$OUTPUT .= <<<END
				<option value="2">2</option>\n<option value="3">3</option>
END;
		}
		if (preg_match("/_swi_/i",$this->parent()->data['type'],$MATCH))
		{
			$OUTPUT .= <<<END
				<option value="2">2</option>
END;
		}
		$OUTPUT .= <<< END

					</select>
				</td></tr>
END;
		$SELECT = array(""=>"None") + \metaclassing\Utility::assocRange(1,22) + \metaclassing\Utility::assocRange(101,109);
		$OUTPUT .= $this->html_form_field_select("lag"	,"LAG Group",$SELECT);

		$OUTPUT .= <<<END
			</table>
			<div id="l2interface">
			<table width="700" border="0" cellspacing="2" cellpadding="1">
END;
		$SELECT = array(
			""			=> "",
			"host"		=> "Host",
			"network"	=> "Network",
		);
		$OUTPUT .= $this->html_form_field_select("spanningtree"	,"Spanningtree Port Type",$SELECT);
		$OUTPUT .= $this->html_form_field_text("vlan"			,"VLAN");
		$OUTPUT .= $this->html_form_field_text("voicevlan"		,"Voice VLAN");
		$OUTPUT .= <<<END
			</table>
			</div>
			<div id="l3interface">
			<table width="700" border="0" cellspacing="2" cellpadding="1">
END;
		$OUTPUT .= $this->html_form_field_text("ip4"			,"Interface IPv4/length");
		$OUTPUT .= $this->html_form_field_text("ip6"			,"Interface IPv6/length");
		$SELECT = array(
			""			=> "default",
			"1524"		=> "1524",
			"9202"		=> "9202",
		);
		$OUTPUT .= $this->html_form_field_select("mtu"	,"Interface MTU",$SELECT);

		if (preg_match("/_mls_/i",$this->parent()->data['type'],$MATCH))
		{
			$OUTPUT .= $this->html_form_field_text("hsrpip"			,"HSRP IPv4 Address");
			$SELECT = array(
				""			=> "default",
				"105"		=> "105",
			);
			$OUTPUT .= $this->html_form_field_select("hsrppriority"	,"HSRP Priority",$SELECT);
		}
		if (preg_match("/_rtr_/i",$this->parent()->data['type'],$MATCH))
		{
			$OUTPUT .= $this->html_form_field_text("qosbandwidth"	,"Interface Bandwidth (mbps)");
			$SELECT = array(
				"1"			=> "1",
			);
			$OUTPUT .= $this->html_form_field_select("qospolicy"	,"QOS Policy",$SELECT);
			$SELECT = array(
				"50"		=> "50",
				"80"		=> "80",
			);
			$OUTPUT .= $this->html_form_field_select("qosrealtime"	,"QOS % Realtime",$SELECT);
		}
		$SELECT = array(
			""			=> "None",
			"1"			=> "1",
		);
		$OUTPUT .= $this->html_form_field_select("ospf"	,"OSPF Process",$SELECT);

		if (preg_match("/_p_/",$this->parent()->data['type'],$MATCH) || preg_match("/_pe_/",$this->parent()->data['type'],$MATCH))
		{
			$SELECT = array(
				""				=> "No",
				"yes"			=> "Yes",
			);
			$OUTPUT .= $this->html_form_field_select("rsvp"	,"RSVP",$SELECT);
			$OUTPUT .= $this->html_form_field_select("ldp"	,"LDP",$SELECT);
			$OUTPUT .= $this->html_form_field_select("pim"	,"PIM",$SELECT);
		}
		$SELECT =  $this->parent()->get_vrf_array();
		$OUTPUT .= $this->html_form_field_select("vpnid"		,"VPN Membership",$SELECT);
		$OUTPUT .= <<<END
			</table>
			</div>
			<table width="700" border="0" cellspacing="2" cellpadding="1">
END;
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$OUTPUT .= <<<END
<pre>
! Found device ID {$this->parent()->data['id']} named {$this->parent()->data['name']}
! Found interface ID {$this->data['id']} named {$this->data['name']}.

conf t


END;

		$OUTPUT .= $this->parent()->config_interface($this);

		$OUTPUT .= <<<END
end

</pre>
END;

		return $OUTPUT;
	}

}
