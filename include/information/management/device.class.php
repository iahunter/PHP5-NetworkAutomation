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

class Management_Device	extends Information
{
	public $category = "Management";
	public $type = "Management_Device";
	public $customfunction = "Scan";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"		,"stringfield0");
		$CHANGED += $this->customfield("ip"			,"stringfield1");
		$CHANGED += $this->customfield("protocol"	,"stringfield2");
		$CHANGED += $this->customfield("groups"		,"stringfield3");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['ip'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['protocol'	]);
		$DB->bind("STRINGFIELD3"	,$this->data['groups'	]);
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
		$this->html_width[$i++] = 50;	// ID
		$this->html_width[$i++] = 250;	// Name
		$this->html_width[$i++] = 150;	// IPv4 Management Address
		$this->html_width[$i++] = 100;	// Protocol
		$this->html_width[$i++] = 150;	// Last Scanned
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();
		$WIDTH = $this->html_width;

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$WIDTH[0]}">
			<caption class="report">Management Device List</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Mgmt IP</th>
					<th class="report" width="{$WIDTH[$i++]}">Protocol</th>
					<th class="report" width="{$WIDTH[$i++]}">Last Scanned</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$rowclass = "row".(($i % 2)+1);

		$this->html_width();
		$WIDTH = $this->html_width;

		$columns = count($WIDTH)-1;	$i = 1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['ip']}</a></td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['protocol']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['lastscan']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();

		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$rowclass = "row1";

		$COLUMNS = array("ID","Name","Management IP","Protocol","Last Scanned");
		$OUTPUT .= $this->html_list_header_template("Information Detail",$COLUMNS);

		$columns = count($this->html_width)-1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td colspan="{$columns}">
						{$datadump}
					</td>
				</tr>
			</tbody>
		</table><br>
END;

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text	("name"			,"Device Name"							);
		$OUTPUT .= $this->html_form_field_text	("ip"			,"Management IPv4 Address (A.B.C.D)"	);
		$OUTPUT .= $this->html_form_field_hidden("protocol"		,"none");
		// TODO Eventually we need to print out the form multiselect group box!
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function scan()	// Scans AND potentially reclassifies devices allowing them to change types as more specific ones become available!
	{
		$OUTPUT = "";
		$DEVICE = $this;
		do
		{
			$OUTPUT .= $DEVICE->rescan();
			$DEVICE = Information::retrieve($DEVICE->data['id']);
		}while ( get_class($this) != get_class($DEVICE) );
		return $OUTPUT;
	}

	public function rescan()	// This is a function to be overridden in ALL device children types!
	{
		// This function should scan the device and return the next type to attempt to scan!
		$this->data['newtype'] = "Management_Device_Network";	// Because there IS only 1 child, change types to it
		$this->update();	// Always run an update after a scan!
		$OUTPUT = "Changed type to {$this->data['newtype']}\n";
		return $OUTPUT;
	}

}

?>
