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

require_once "information/checklist/server.class.php";

class Checklist_Server_Decom	extends Checklist_Server
{
	public $type = "Checklist_Server_Decom";

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY AND active = 1 ORDER BY stringfield1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type'] . "%");
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

	public function html_list_header()
	{
		$COLUMNS = array("ID","Ticket","Site","Contact","BPO","Hostname","Created By","Modified");
		$OUTPUT = $this->html_list_header_template("Server Decom Checklist",$COLUMNS);
		return $OUTPUT;
	}

	public function html_form_extended()
	{
		$OUTPUT = "";

		return $OUTPUT;
	}

/*	public function html_detail_rows($i = 0)
	{
		$OUTPUT = "";

		$this->html_width();
		$CREATOR = $this->created_by();
		$ROWCLASS = "row".(($i++ % 2)+1);
		$j = 1;
		$OUTPUT .= <<<END

			</tbody>
END;
		return $OUTPUT;
	}
/**/
	public function initialize()
	{
		$TASKS = array(
			"SCOM Removed",
			"SCCM Removed",
			"WINS Removed",
			"DNS Removed",
			"AD  Removed",
			"OPSrepo Updated",
			"If Enterprise Application - Ensure the Product/DR fields are completed in Ops REPO",
			"VM deleted from disk VMware",
		);
		$this->addtasks($TASKS);
	}

}

?>
