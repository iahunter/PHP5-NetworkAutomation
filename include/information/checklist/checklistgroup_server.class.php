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

require_once "information/checklist/checklistgroup.class.php";

class Checklist_ChecklistGroup_Server	extends Checklist_ChecklistGroup
{
	public $category = "Checklist";
	public $type = "Checklist_ChecklistGroup_Server";
	public $customfunction = "";

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		$COLUMNS = array("ID","Name");
		$OUTPUT .= $this->html_list_header_template("Checklist Group",$COLUMNS);
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= $this->html_list_footer();

		$CHILDTYPE = "Server";	// Server types

        $OUTPUT .= <<<END

            <table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
                <tr>
                    <td align="right">
                        <ul class="object-tools">
                            <li>
                                <a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add Server Checklist</a>
                            </li>
                        </ul>
                            </li>
                        </ul>
                    </td>
                </tr>
            </table>
END;

		$CHILDREN = $this->children($this->id,$CHILDTYPE . "%",$this->data["category"]);
		$i = 0;
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

}
