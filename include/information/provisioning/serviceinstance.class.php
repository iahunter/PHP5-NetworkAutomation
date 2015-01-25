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

class Provisioning_ServiceInstance	extends Information
{
	public $category = "Provisioning";
	public $type = "Provisioning_ServiceInstance";
	public $customfunction = "Configure";

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
		if(isset($this->data['newtype']) && $this->data['type'] != $this->data['newtype'])
		{
			print "Device Changed Type To {$this->data['newtype']} Please edit this object and provide additional information!<br><br>\n";
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
		$this->html_width[$i++] = 220;	// Type
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Name","Type");
		$OUTPUT = $this->html_list_header_template("Service Instance List",$COLUMNS);
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
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['type']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		$OUTPUT .= $this->html_detail_buttons();
		$COLUMNS = array("ID","Name","Type");
		$COLUMNCOUNT = count($this->html_width)-1;	$i = 1;
		$OUTPUT .= $this->html_list_header_template("Service Instance Details",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$DUMP = trim(dumper_to_string($this->data));
		$rowclass = "row1";
		$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$COLUMNCOUNT}">{$DUMP}</td>
				</tr>
END;
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();  // Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("name"			,"Service Instance Name"							);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function html_form_extended()
	{
		$OUTPUT = "";
		$SELECT = array(
			"ServiceInstance_EBGP_Peer"			=> "eBGP Peer",
			"ServiceInstance_BGP_Advertisment"	=> "BGP Advertisment",
			"ServiceInstance_WAN_T1"			=> "WAN T1",
			"ServiceInstance_WAN_Ethernet"		=> "WAN Ethernet",
			"ServiceInstance_WAN_FlexVPN"		=> "WAN FlexVPN",
			"ServiceInstance_VoiceGateway"		=> "Voice Gateway",
			"ServiceInstance_WCCP_Riverbed"		=> "WCCP Riverbed",
			"ServiceInstance_Switch_Stack"		=> "Switch Stack",
		);
		$OUTPUT .= $this->html_form_field_select("newtype","Service Instance Type",$SELECT);
		return $OUTPUT;
	}

	public function configure()
	{
		$OUTPUT = "";

		$OUTPUT .= <<<END
<pre>
! Found device ID {$this->parent()->data['id']} named {$this->parent()->data['name']}
! Found service instance ID {$this->data['id']} named {$this->data['name']}.

conf t


END;

		$OUTPUT .= $this->config_serviceinstance($this);

		$OUTPUT .= <<<END

end

</pre>
END;

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$OUTPUT .= <<<END

! Found device ID {$this->parent()->data['id']} named {$this->parent()->data['name']}
! Found service instance ID {$this->data['id']} named {$this->data['name']}.

END;
		$OUTPUT .= $this->config_serviceinstance($this);
		$OUTPUT .= <<<END


END;
		return $OUTPUT;
	}

	public function config_serviceinstance()
	{
		$OUTPUT = "";
		$OUTPUT .= "This is the base object, this function should be overridden in the children!\n";
		return $OUTPUT;
	}

}
