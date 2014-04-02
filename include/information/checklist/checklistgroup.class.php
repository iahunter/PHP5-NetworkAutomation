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

class Checklist_ChecklistGroup	extends Information
{
	public $category = "Checklist";
	public $type = "Checklist_ChecklistGroup";
	public $customfunction = "";

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 200;	// Name
		$this->html_width[0]	= array_sum($this->html_width);
	}

    public function html_list_buttons()
    {
        $OUTPUT = "";
        return $OUTPUT;
    }

	public function html_list_header()
	{
		$OUTPUT = "";
		$COLUMNS = array("ID","Name");
		$OUTPUT .= $this->html_list_header_template("Checklist Groups",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
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

		$COLUMNS = array("ID","Name");
		$OUTPUT .= $this->html_list_header_template("Checklist Group",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= $this->html_list_footer();

		$CHILDTYPE = "";	// All types of children possible!

        $OUTPUT .= <<<END

            <table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
                <tr>
                    <td align="right">
                        <ul class="object-tools">
                            <li>
                                <a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE} Checklist</a>
                            </li>
                        </ul>
                    </td>
                </tr>
            </table>
END;

		$CHILDREN = $this->children($this->id,$CHILDTYPE . "%",$this->data["category"]);
		$i = 1;
		if (!empty($CHILDREN))
		{
			$CHILD = reset($CHILDREN);
			$OUTPUT .= $CHILD->html_list_header();
			foreach ($CHILDREN as $CHILD)
			{
				$OUTPUT .= $CHILD->html_list_row($i++);
			}
			$OUTPUT .= $CHILD->html_list_footer();
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("name"			,"Checklist Group Name"										);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}
