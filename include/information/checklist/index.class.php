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

class Checklist_Index	extends Information
{
	public $category = "Checklist";
	public $type = "Checklist_Index";
	public $customfunction = "";

	public function html_list()
	{
		$OUTPUT = "";

		$RESULTS = $this->list_query();

			$SEARCH = array(			// Search for a category index!
			"category"	=> $this->data["category"],
			"type"		=> "Index",
		);
		$RESULTS = Information::search($SEARCH);

		// If we have an instance of the index, print out its html_detail
		if (count($RESULTS) > 0)
		{
			$INFOBJECT = Information::retrieve(reset($RESULTS));
			$OUTPUT .= $INFOBJECT->html_detail($i++);
		}else{
			$OUTPUT .= $this->html_list_buttons();
		}

		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		global $HTML;

		$BLOCKS = array(
			$HTML->featureblock(
				"Server Build Checklist",
				"/information/information-list.php?category={$this->data["category"]}&type=Server",
				"/images/servericon.png",
				array(
						"Server Build Checklists"
				)
			) => "Building Windows, Linux, and ESX Host Servers",
			$HTML->featureblock(
				"Server Decom Checklist",
				"/information/information-list.php?category={$this->data["category"]}&type=Server_Decom",
				"/images/decommissioning.png",
				array(
						"Server Decom Checklists"
				)
			) => "Decommissioning Servers.",
		);

		$OUTPUT .= <<<END
		<div style="width: 800px;">
			<div style="padding: 5px; ">
				<b>I am working to improve this tool!</b> There are going to be some interim steps to convert the information here to a new format!
				If you have any questions please contact John Lavoie.
			</div>
		</div>
		<br>
		<div style="table;">
END;
		$i = 0;
		foreach ($BLOCKS as $BLOCK => $DESCRIPTION)
		{
			if (++$i % 2)
			{
			$OUTPUT .= <<<END
			<div style="display: table-row; valign: top;">
					<div style="display: table-cell; padding: 5px;">{$BLOCK}</div>
					<div style="display: table-cell; padding: 5px; width: 250px;">{$DESCRIPTION}</div>
			</div>
END;
			}else{
			$OUTPUT .= <<<END
			<div style="display: table-row; valign: top;">
					<div style="display: table-cell; padding: 5px; width: 250px;">{$DESCRIPTION}</div>
					<div style="display: table-cell; padding: 5px;">{$BLOCK}</div>
			</div>
END;

			}
		}
		$OUTPUT .= "</div>\n";

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Name"									);
		$OUTPUT .= $this->html_form_field_text("description","Description"							);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
