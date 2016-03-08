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

require_once "information/ipplan/network.class.php";

class IPPlan_Address	extends IPPlan_Network
{
	public $data;
	public $category = "IPPlan";
	public $type = "IPPlan_Address";
	public $customfunction = "";

	public function html_detail()
	{
		$OUTPUT = "";

		$WIDTH = array();	$i = 1;
		$WIDTH[$i++] = 35;  // ID
		$WIDTH[$i++] = 50;  // Type
		$WIDTH[$i++] = 130; // Address
		$WIDTH[$i++] = 250; // Name
		$WIDTH[$i++] = 200; // Linked Information
		$WIDTH[0] = array_sum($WIDTH);

		// Pre-information table links to edit or perform some action
		$OUTPUT .= <<<END
		<table width="{$WIDTH[0]}" border="0" cellspacing="0" cellpadding="1">
			<tr>
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
			<caption class="report">This IPPlan {$this->data['type']}</caption>
			<thead>
				<tr>
					<th class="report" width="{$WIDTH[$i++]}">ID</th>
					<th class="report" width="{$WIDTH[$i++]}">Type</th>
					<th class="report" width="{$WIDTH[$i++]}">Address</th>
					<th class="report" width="{$WIDTH[$i++]}">Name</th>
					<th class="report" width="{$WIDTH[$i++]}">Linked Information</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);
		$rowclass = "row".(($i % 2)+1);
		$datadump = \metaclassing\Utility::dumperToString($this->data);
		if ($_SESSION["DEBUG"])
		{
			$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$columns}">
						{$datadump}
					</td>
				</tr>
END;
		}
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		if (!intval($this->data['parent'])) { return "Error: No parent ID passed, please select a block or network with a valid parent!"; }
		$OUTPUT .= <<<END
			<div id="nosx_form">
			<form method="post" action="{$_SERVER['PHP_SELF']}">
			<table width="500" border="0" cellspacing="2" cellpadding="1">

				<tr><td>
					<strong>IPv4 Address Name:</strong>
					<input type="text" name="name" size="20" value="{$this->data['name']}">
				</td></tr>

				<tr><td>
					<strong>IPv4 Address:</strong>
					<input type="text" name="prefix" size="20" value="{$this->data['prefix']}">
				</td></tr>

				<input type="hidden" name="length" value="32">
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
