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

class Image		extends Information
{
	public $category = "";
	public $type = "Image";
	public $customfunction = "";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("linked"		,"stringfield0");
		$CHANGED += $this->customfield("name"		,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }	// If any of the fields have changed, run the update function.
	}

	public function validate($NEWDATA)
	{
		if (!$NEWDATA['name'])
		{
			$this->data['error'] .= "ERROR: Task list name is required!\n";
			return 0;
		}
		return 1;
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['linked'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'			]);
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 400;	// Image Name
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Image Name");
		$OUTPUT = $this->html_list_header_template("Image List",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$i = 1;
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		$OUTPUT .= $this->html_detail_buttons();
		$COLUMNS = array("ID","Image Name");
		$COLUMNCOUNT = count($this->html_width)-1;	$i = 1;
		$OUTPUT .= $this->html_list_header_template("Image Details",$COLUMNS);
		$OUTPUT .= $this->html_list_row();

		$rowclass = "row1";
		$IMGSRC = $this->html_display_image("image");
		if (isset($this->data['image']))
		{
			$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$COLUMNCOUNT}">
						{$IMGSRC}
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

		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_form_field_text("name"			,"Image Name"							);
		if (isset($this->data['id']))
		{
			$OUTPUT .= $this->html_form_field_upload("image","Select Image to Upload");
		}else{
			$OUTPUT .= $this->html_form_comment("Please create the object in the database before attaching an image to it.");
		}
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
