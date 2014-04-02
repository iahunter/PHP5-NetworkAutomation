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

class Security_Firewall	extends Information
{
	public $category = "Security";
	public $type = "Security_Firewall";
	public $customfunction = "Config";

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("link"	,"stringfield0");
		$CHANGED += $this->customfield("name"	,"stringfield1");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['link'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['name'		]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA["name"] == "")
		{
			$this->data['error'] .= "ERROR: name provided is not valid!\n";
			return 0;
		}

		if ( !isset($this->data["id"]) )	// If this is a NEW record being added, NOT an edit
		{
			$SEARCH = array(			// Search existing information with the same name!
					"category"		=> $this->category,
					"stringfield1"	=> $NEWDATA["name"],
					);
			$RESULTS = Information::search($SEARCH);
			$COUNT = count($RESULTS);
			if ($COUNT)
			{
				$DUPLICATE = reset($RESULTS);
				$this->data['error'] .= "ERROR: Found duplicate {$this->category}/{$this->type} ID {$DUPLICATE} with {$NEWDATA["name"]}!\n";
				return 0;
			}
		}

		return 1;
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 order by stringfield1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type']);
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
/**/
	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 200;	// Name
		$this->html_width[$i++] = 300;	// Description
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Firewall List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$columns = count($this->html_width)-1;	$i = 1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['id']}</td>
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["description"]}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$columns = count($this->html_width)-1;
		$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Firewall Details</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Name</th>
					<th class="report" width="{$this->html_width[$i++]}">Description</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row($i++);

		$rowclass = "row".(($i % 2)+1); $i++;
		$CREATED_BY	 = $this->created_by();
		$CREATED_WHEN	= $this->created_when();
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Created by {$CREATED_BY} on {$CREATED_WHEN}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);

		$OUTPUT .= $this->html_list_footer();

		// All the different types of child objects for estimating, in order.
		$CHILDTYPES = array();
		array_push($CHILDTYPES,"Firewall_Interface");
		foreach ($CHILDTYPES as $CHILDTYPE)
		{
			$OUTPUT .= <<<END

			<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data['id']}&category={$this->data['category']}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
END;
			$CHILDREN = $this->children($this->id,$CHILDTYPE . "%",$this->category);

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
		}

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		$OUTPUT .= $this->html_form_header();
		//$OUTPUT .= $this->html_toggle_active_button();	// Permit the user to deactivate any devices and children

		$OUTPUT .= $this->html_form_field_text("name"		,"Firewall Name"								);
		$OUTPUT .= $this->html_form_field_text("description","Description"									);
		$OUTPUT .= $this->html_form_extended();
		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function config()
	{
		$OUTPUT = "";

		$OUTPUT .= "<pre>\n";
		$OUTPUT .= Utility::last_stack_call(new Exception);
		$OUTPUT .= "!\n! Firewall ID {$this->data["id"]} Name: {$this->data["name"]} Description: {$this->data["description"]}\n!\n";

		/*****************************************************************
		*	Get our attached interfaces and sort them by security level
		*****************************************************************/
		$SEARCH = array(
					"category"		=> "Security",
					"type"			=> "Firewall_Interface",	// Look for firewall interfaces
					"parent"		=> $this->data["id"],		// Attached to this firewall
				);
		$RESULTS = Information::search($SEARCH,"stringfield1"); // Search for HostGroups ordered by stringfield1 (name)
		$INTERFACES = array();
		foreach ($RESULTS as $RESULT) { array_push( $INTERFACES , Information::retrieve($RESULT) ); }
		usort($INTERFACES, array("Security_Firewall_Interface","sort_by_security_level"));	// Sort from low security level to high!

		/*****************************************************************
		*	Build interface pairs (ordered LOW->HIGH)
		*****************************************************************/
		$INTERFACE_ITERATOR = new ArrayIterator($INTERFACES);
		while( $INTERFACE_ITERATOR->valid() )
		{
			$OUTSIDE = $INTERFACE_ITERATOR->current();
			$INTERFACE_ITERATOR->next();
			if ( $INTERFACE_ITERATOR->valid() )	// If the next element is valid, push the inside,outside pair into the array!
			{
				$INSIDE = $INTERFACE_ITERATOR->current();
				$OUTPUT .= "! Building rules for interface pair: SOURCE:{$OUTSIDE->data["name"]} --to-> DESTINATION:{$INSIDE->data["name"]} \n";
				$ACL = "ACL_{$OUTSIDE->data["name"]}_{$INSIDE->data["name"]}";
				/********************************************************************************************
				*	Find host groups for the OUTSIDE zone													*
				*********************************************************************************************/
				$SEARCH = array(
							"category"		=> "Security",
							"type"			=> "Group_Host",			// Look for host groups
							"stringfield2"	=> $OUTSIDE->data["zone"],	// In the outside security zone
						);
				$OUTSIDE_HOSTGROUPS = Information::search($SEARCH);
				/********************************************************************************************
				*	Find host groups for the INSIDE zone													*
				*********************************************************************************************/
				$SEARCH = array(
							"category"		=> "Security",
							"type"			=> "Group_Host",			// Look for host groups
							"stringfield2"	=> $INSIDE->data["zone"],	// In the outside security zone
						);
				$INSIDE_HOSTGROUPS = Information::search($SEARCH);
				/****************************************************************************************************
				*	Find application components with SRCHOST in INSIDE_HOSTGROUPS and DSTHOST in OUTSIDE_HOSTGROUPS *
				*****************************************************************************************************/
				$OUTSIDE_SEARCH	= implode( ","	,	$OUTSIDE_HOSTGROUPS	);
				$INSIDE_SEARCH	= implode( "," 	,	$INSIDE_HOSTGROUPS	);
				//$OUTPUT .= "OUTSIDE SEARCH LIST: $OUTSIDE_SEARCH\n";	$OUTPUT .= "INSIDE SEARCH LIST: $INSIDE_SEARCH\n";
				global $DB; // Our Database Wrapper Object
				$QUERY = "
					SELECT id FROM information
					WHERE type LIKE :TYPE
					AND category LIKE :CATEGORY
					AND active = 1
					AND stringfield2 IN({$OUTSIDE_SEARCH})
					AND stringfield3 IN({$INSIDE_SEARCH})
					ORDER BY parent";
				$DB->query($QUERY);
				try {
					$DB->bind("TYPE","Application_Component");
					$DB->bind("CATEGORY","Security");
					$DB->execute();
					$RESULTS = $DB->results();
				} catch (Exception $E) {
					$MESSAGE = "Exception: {$E->getMessage()}";
					trigger_error($MESSAGE);
					global $HTML;
					die($MESSAGE . $HTML->footer());
				}
//				$OUTPUT .= "QUERY: $QUERY\n";
//				dumper($RESULTS);	// Hopefully this is an array of application_components with src and dest matching our interface zones!
				$COUNT = count($RESULTS);
				$OUTPUT .= "! Found {$COUNT} application components requiring rules in this firewall\n";
				/************************************************************************************************
				*	We have now found all the interface components applicable to this firewall interface pair	*
				*	So, lets tell those suckers to go configure themselves!										*
				************************************************************************************************/
				foreach($RESULTS as $RESULT)
				{
					$OUTPUT .= "\n!\n";
					$APPLICATION_COMPONENT = Information::retrieve($RESULT["id"]);
					$OUTPUT .= $APPLICATION_COMPONENT->config($ACL);
					$ASDF = Information::create("device_ios_rtr","Provisioning");
					//dumper( $ASDF->parse_nested_list_to_array( $ASDF->filter_config($APPLICATION_COMPONENT->config($ACL) ) ) );
					//dumper( $ASDF->filter_config($APPLICATION_COMPONENT->config($ACL) ) );
				}
			}
		}
		//print "<hr size=1>\n";
		$OUTPUT .= "</pre>\n";

		return $OUTPUT;
	}

}

?>
