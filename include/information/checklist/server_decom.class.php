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
			"VM deleted from disk VMware",
		);
		$this->addtasks($TASKS);
	}

}

?>
