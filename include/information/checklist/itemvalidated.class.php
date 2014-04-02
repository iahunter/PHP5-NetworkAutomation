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

class Checklist_ItemValidated	extends Information
{
	public $category = "Checklist";
	public $type = "Checklist_ItemValidated";
	public $customfunction = "";

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 550;	// Task
		$this->html_width[$i++] = 100;	// Complete / by
		$this->html_width[$i++] = 100;	// Validated / by
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$COLUMNS = array("ID","Task","Complete","Validated");
		$OUTPUT = $this->html_list_header_template("Server Build Checklist",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";
		$this->html_width();
		$CREATOR = $this->created_by();
		$ROWCLASS = "row".(($i % 2)+1); $i = 1;

		if ( empty($this->data["completed"]) || $this->data["completed"] == "No")
		{
			$COMPLETELINK = <<<END
<a href="/information/information-action.php?id={$this->data['id']}&action=complete">{$this->data["completed"]}</a>
END;
		}else{
			$COMPLETELINK = $this->data["completed"];
		}

		if ( empty($this->data["validated"]) || $this->data["validated"] == "No")
		{
			$VALIDATELINK = <<<END
<a href="/information/information-action.php?id={$this->data['id']}&action=validate">{$this->data["validated"]}</a>
END;
		}else{
			$VALIDATELINK = $this->data["validated"];
		}

		$OUTPUT .= <<<END

				<tr class="{$ROWCLASS}">
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-edit.php?id={$this->data['id']}">{$this->data['id']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['task']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$COMPLETELINK}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$VALIDATELINK}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();
		$OUTPUT .= $this->html_detail_buttons();
		$COLUMNS = array("ID","Task","Complete","Validated");
		$OUTPUT .= $this->html_list_header_template("Task List",$COLUMNS);
		$OUTPUT .= $this->html_list_row($i++);
		$COLUMNCOUNT = count($COLUMNS);
/*		$DUMP = trim(dumper_to_string($this->data));
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}">
					<td colspan="{$COLUMNCOUNT}">
						Details:
						<pre>{$DUMP}</pre>
					</td>
				</tr>
END;
/**/
		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children
		$OUTPUT .= $this->html_form_field_text("task"		,"Task"					);

		$SELECT = array(
			"No" => "No",
			"{$_SESSION["AAA"]["username"]}" => "Yes",
			"N/A" => "N/A",
		);
		$OUTPUT .= $this->html_form_field_select("completed","Completed?",  $SELECT);
		$OUTPUT .= $this->html_form_field_select("validated","Validated?",  $SELECT);

		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function complete()
	{
		$OUTPUT = "";
		$this->data["completed"] = "{$_SESSION["AAA"]["username"]}";
		$this->update();
		$OUTPUT .= "Updated {$this->data["id"]} task {$this->data["tasl"]} completed by {$this->data["completed"]}. Redirecting back to parent...";
		$OUTPUT .= $this->redirect_parent();
		return $OUTPUT;
	}

	public function validate()
	{
		$OUTPUT = "";
		$this->data["validated"] = "{$_SESSION["AAA"]["username"]}";
		$this->update();
		$OUTPUT .= "Updated {$this->data["id"]} task {$this->data["tasl"]} validated by {$this->data["validated"]}. Redirecting back to parent...";
		$OUTPUT .= $this->redirect_parent();
		return $OUTPUT;
	}

	public function redirect_parent($NEXT = "view" , $PRINT = 0)
	{
		$OUTPUT = "";
		$ID = $this->data["parent"];
		$OUTPUT .= <<<END
			click <a href="/information/information-{$NEXT}.php?id=$ID">here</a> to {$NEXT} to the parent information.<br><br>
<script>
setInterval(function(){
    window.location.href = "/information/information-{$NEXT}.php?id={$ID}";
},2000);
</script>
END;
		if (isset($PRINT)) { print $OUTPUT; }
		return $OUTPUT;
	}

}

?>
