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

class Link extends Information
{
	public $category = "";
	public $type = "Link";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("link"	,"stringfield0");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['link'		]);
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("link"		,"Linked Information ID"			);
		$OUTPUT .= $this->html_form_field_text("name"		,"Name"								);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}
