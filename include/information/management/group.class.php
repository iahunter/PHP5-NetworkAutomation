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
	public $type = "Device";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("vpnid"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function validate($NEWDATA)
	{
		$VPNID = intval($NEWDATA['vpnid']);
		if ($VPNID < 1 || $VPNID > 65535)
		{
			$this->data['error'] .= "ERROR: Invalid VPNID!\n";
			return 0;
		}

		return 1;
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['vpnid'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by ABS(stringfield0)";
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
		$this->html_width[$i++] = 50;	// VPNID
		$this->html_width[$i++] = 100;	// Name
		$this->html_width[$i++] = 200;	// Description
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();
		$WIDTH = $this->html_width;
		$WIDTH[0] = array_sum($WIDTH);

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$WIDTH[0]}">
			<caption class="report">BGP ASN List</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">VPN ID</th>
					<th class="report" width="{$WIDTH[$i++]}">VPN Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Description</th>
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
					<td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['vpnid']}</a></td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['name']}</td>
					<td class="report" width="{$WIDTH[$i++]}">{$this->data['description']}</td>
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
					<th class="report" width="{$WIDTH[$i++]}">VPN ID</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Description</th>
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
					<strong>VPN ID (100-999):</strong>
					<input type="text" name="vpnid" size="20" value="{$this->data['vpnid']}">
				</td></tr>

				<tr><td>
					<strong>VPN Name (ALL CAPS NO SPACES A-Z & _ ):</strong>
					<input type="text" name="name" size="50" value="{$this->data['name']}">
				</td></tr>

				<tr><td>
					<strong>VPN Description:</strong>
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

}

?>
