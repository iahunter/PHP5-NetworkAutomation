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

class Security_Index	extends Information
{
	public $category = "Security";
	public $type = "Security_Index";
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
				"Hosts",
				"/information/information-list.php?category={$this->data["category"]}&type=Network_Host",
				"/images/firewall_host.png",
				array(
						"List/view add/edit"
				)
			) => "Host information is collected and linked to applications so that the location of the servers can be used when generating the configuration for a firewall.",
			$HTML->featureblock(
				"Services",
				"/information/information-list.php?category={$this->data["category"]}&type=Service",
				"/images/firewall_service.png",
				array(
						"List/view add/edit"
				)
			) => "Services describe the listening socket on a server running an application. This information is used to generate specific configuration allowing traffic for an application to reach the hosts running it.",
			$HTML->featureblock(
				"Applications",
				"/information/information-list.php?category={$this->data["category"]}&type=Application",
				"/images/firewall_application.png",
				array(
						"List/view add/edit"
				)
			) => "Application information is collected and links services and servers together to make an application instance. Applications may span multiple datacenters.",
			$HTML->featureblock(
				"Firewalls",
				"/information/information-list.php?category={$this->data["category"]}&type=Firewall",
				"/images/firewall.png",
				array(
						"List/view add/edit configure/audit"
				)
			) => "Firewalls provide policy enforcement between security zones.",
		);

		$OUTPUT .= <<<END
		<div style="width: 800px;">
			<div style="padding: 5px; ">
				<b>Welcome to the firewall automation and application documentation system!</b> This website is a collection of tools and
				services that will collect and relate application, host, and service information to document application communication
				and automate the configuration and management of firewalls.
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

		$OUTPUT .= $this->html_form_field_text("name"		,"Zone Name"									);
		$OUTPUT .= $this->html_form_field_text("description","Description"									);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

}

?>
