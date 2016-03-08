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

class BGP_ASN	extends Information
{
	public $data;
	public $category = "BGP";
	public $type = "BGP_ASN";
	public $customfunction = "Report";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"	,"stringfield0");
		$CHANGED += $this->customfield("asn"	,"stringfield1");
		$CHANGED += $this->customfield("name"	,"stringfield2");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function validate($NEWDATA)
	{
		$ASN = intval($NEWDATA['asn']);
		if ($ASN < 1 || $ASN > 65535)
		{
			$this->data['error'] .= "ERROR: Invalid ASN, must be 1-65535!\n";
			return 0;
		}

		$SEARCH = array(
						"category"      => "BGP",
						"type"          => "ASN",
						"stringfield1"  => "{$ASN}",
                        );
		$RESULTS = Information::search($SEARCH);
		foreach ($RESULTS as $AS)
		{
			print "Getting info for ASN ID {$AS}\n";
			$ASOBJ = Information::retrieve($AS);
			if ( $ASOBJ->data["id"] != $this->data["id"] )
			{
				$this->data['error'] .= "ERROR: {$ASN} is already linked with the following information record:";
				$this->data['error'] .= \metaclassing\Utility::dumperToString($ASOBJ);
				return 0;
			}
		}

		return 1;
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$this->data["asn"] = intval($this->data["asn"]);	// Make sure we store the ASN as an integer!
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['asn'			]);
		$DB->bind("STRINGFIELD2"	,$this->data['name'			]);
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by ABS(stringfield1)";
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
		$this->html_width[$i++] = 50;	// ASN
		$this->html_width[$i++] = 200;	// Name
		$this->html_width[$i++] = 400;	// Description
		$this->html_width[$i++] = 250;	// Last Modified
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
			<caption class="report">BGP ASN List</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">ASN</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Description</th>
					<th class="report" width="{$WIDTH[$i++]}">Last Modified</th>
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
		$datadump = \metaclassing\Utility::dumperToString($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['asn']}</a></td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['name']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['description']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['modifiedwhen']} by {$this->data['modifiedby']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();
		$WIDTH = $this->html_width;

		// Pre-information table links to edit or perform some action
		$OUTPUT .= <<<END
		<table width="{$WIDTH[0]}" border="0" cellspacing="0" cellpadding="1">
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
		$columns = count($WIDTH)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$WIDTH[0]}">
			<caption class="report">BGP ASN Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">ASN</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Description</th>
					<th class="report" width="{$WIDTH[$i++]}">Last Modified</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END
			<div id="nosx_form">
			<form method="post" action="{$_SERVER['PHP_SELF']}">
			<table width="500" border="0" cellspacing="2" cellpadding="1">

				<tr><td>
					<strong>Autonomous System Number (1-65535):</strong>
					<input type="text" name="asn" size="20" value="{$this->data['asn']}">
				</td></tr>

				<tr><td>
					<strong>ASN Site Names (Space Delimited):</strong>
					<input type="text" name="name" size="50" value="{$this->data['name']}">
				</td></tr>

				<tr><td>
					<strong>ASN Description:</strong>
					<input type="text" name="description" size="50" value="{$this->data['description']}">
				</td></tr>
END;

//////////////////////////////////////////////////////END OF FIELDS//////////////////////////////////////////////////////////////
		if($this->data['id'])
		{
			$OUTPUT .= <<<END
				<tr><td>
					<input type="hidden" name="id"		value="{$this->data['id']}">
			        	<input type="submit"			value="Edit Information">
				</td></tr>
END;
		}else{
			$OUTPUT .= <<<END
				<tr><td>
					<input type="hidden" name="category"	value="{$this->data['category']}">
					<input type="hidden" name="type"	value="{$this->data['type']}">
					<input type="hidden" name="parent"	value="{$this->data['parent']}">
			        	<input type="submit"			value="Add Information">
				</td></tr>
END;
		}
		$OUTPUT .= <<<END
			</table>
			</form>
		</div>
END;

		return $OUTPUT;
	}

	public function report()
	{
		$OUTPUT = "";
		$SEARCH = array(
						"category"		=> "management",
						"type"			=> "device_network_%",
						"stringfield5"	=> "%router bgp%",
						);
		$RESULTS = Information::search($SEARCH);

		// Get all the ASNs configured on devices now:
		$BGPDATA = array();
		foreach ($RESULTS as $RESULT)
		{
			$INFOBJECT = Information::retrieve($RESULT);
			if(preg_match('/.*router\ bgp\ ([0-9]+).*/',$INFOBJECT->data['run'],$REG))
			{
				$ASN = $REG[1];
				if (!isset($BGPDATA[$ASN])) { $BGPDATA[$ASN] = array(); }
				array_push($BGPDATA[$ASN],$DEVICENAME);
			}
			unset($INFOBJECT);	// Save memory with big information sets
		}

		$RECORDCOUNT=count($RESULTS);
		$ASNCOUNT = count($BGPDATA);
		$OUTPUT .= "Found {$RECORDCOUNT} devices running BGP in {$ASNCOUNT} unique AS numbers<br><pre>";

		// Go through the found ASN's in device configs and match up with database.
		foreach($BGPDATA as $ASN => $DEVICES)
		{
			$OUTPUT .= count($BGPDATA[$ASN])." devices in AS $ASN - DB Sites:";
			global $DB;
			$QUERY = "select stringfield2 from information where category = 'bgp' and type = 'asn' and stringfield1 = :ASN ORDER BY ABS(stringfield2), id";
			$DB->query($QUERY);
			try {
				$DB->bind("ASN",$ASN);
				$DB->execute();
				$RESULTS = $DB->results();
			} catch (Exception $E) {
				$MESSAGE = "Exception: {$E->getMessage()}";
				trigger_error($MESSAGE);
				global $HTML;
				die($MESSAGE . $HTML->footer());
			}
			foreach($RESULTS as $RECORD)
			{
				$OUTPUT .= " {$RECORD['stringfield2']}";
			}
			$OUTPUT .= "\n";
			foreach($DEVICES as $DEVICE)
			{
				$OUTPUT .= "\t[{$ASN}] $DEVICE\n";
			}
			$OUTPUT .= "\n";
		}
		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

	public static function array_bgp_by_asn()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select * from information where type like :TYPE and category like :CATEGORY and active = 1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE","asn");
			$DB->bind("CATEGORY","bgp");
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		$OUTPUT = array();
		foreach($RESULTS as $ASN)
		{
			$OUTPUT[$ASN['stringfield1']] = $ASN;
		}
		return $OUTPUT;
	}

}
