<?php

/**
 * include/login.inc.php
 *
 * This class parses and stores various CLI output from Cisco devices.
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
 
if (isset($_POST['username']))	{ $USERNAME = strtolower($_POST['username']); }else{ $USERNAME = ""; }
if (isset($_POST['password']))	{ $PASSWORD = $_POST['password']; }else{ $PASSWORD = ""; }

$HEADER = "Please Log In";

if ($USERNAME && $PASSWORD) // If we are given username and password, attempt to authenticate
{
	if($LDAP->authenticate($USERNAME,$PASSWORD) && !preg_match('/\.admin$/',$USERNAME,$REG))
	{
		$_SESSION["DEBUG"				] = 0;			// Clear the debug flag
		$_SESSION["AAA"]["authenticated"] = 1;			// Successfully Authenticated User!
		$_SESSION["AAA"]["username"		] = $USERNAME;	// Store the username
		$_SESSION["AAA"]["password"		] = $PASSWORD;	// Password
		$_SESSION["AAA"]["realname"		] = $LDAP->user_to_realname($USERNAME);	// Real name from LDAP

		/**************************
		* GROUP based permissions *
		**************************/
		if ($LDAP->user()->inGroup($USERNAME,"IMServerAdminHex",1))								// Offshore Server Admins
		{
			$_SESSION["AAA"]["permission"]["information.checklist.*.*"]					= 1;		// Permit * on checklists
		}
		if ($LDAP->user()->inGroup($USERNAME,"CESwitchAdmins",1))								// Client Engagement Switch Admins
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
			$_SESSION["AAA"]["permission"]["tool.switch.edit"]							= 1;		// edit switch ports
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMCEAll",1)								||		// IM Client Engagement
			$LDAP->user()->inGroup($USERNAME,"IMLeadAll",1)								||		// IM Lead
			$LDAP->user()->inGroup($USERNAME,"IMCELeadership",1)						||		// IM CE Leadership
			$LDAP->user()->inGroup($USERNAME,"IMManagers",1)							)		// IM Managers
		{
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMLogistics",1)							)		// Logistics (ALL)
		{
			$_SESSION["AAA"]["permission"]["information.bgp.asn.view"]					= 1;		// Permit view on bgp asn
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMLogisticsEstimating",1)					)		// Logistics Estimators
		{
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkImplementation",1))						// SWAT Network Implementation
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
			$_SESSION["AAA"]["permission"]["tool.switch.edit"]							= 1;		// edit switch ports
			$_SESSION["AAA"]["permission"]["tool.template"]								= 1;		// everybody uses the template tool
			$_SESSION["AAA"]["permission"]["report.iosversion"]							= 1;		// run the ios version report
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
			$_SESSION["AAA"]["permission"]["information.management.device.*.*"]			= 1;		// Full control of devices (add edit deactivate rescan etc)
			$_SESSION["AAA"]["permission"]["information.bgp.asn.view"]					= 1;		// Permit view on bgp asn
			$_SESSION["AAA"]["permission"]["information.provisioning.*.view"]			= 1;		// Permit view on provisioning.* information
			$_SESSION["AAA"]["permission"]["information.provisioning.*.action.*"]		= 1;		// Permit provisioning actions
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkEngineering",1)					||		// Network Engineering
			$LDAP->user()->inGroup($USERNAME,"IMNetworkOperations",1)					||		// Network Operations (KSS Tier 2)
			$LDAP->user()->inGroup($USERNAME,"IMServerAdminCore",1)						||		// Server Admin Core Team
			$_SESSION["AAA"]["username"] == "russell.holiday"							)		// Rusty Hook (Honorary Engineer)
		{
			$_SESSION["AAA"]["permission"]["tool.racktables"]							= 1;		// RackTables DCIM
			$_SESSION["AAA"]["permission"]["tool.rescan"]								= 1;		// Rescan devices
			$_SESSION["AAA"]["permission"]["tool.delete"]								= 1;		// Delete devices
			$_SESSION["AAA"]["permission"]["tool.dns"]									= 1;		// generate DNS records
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
			$_SESSION["AAA"]["permission"]["tool.switch.edit"]							= 1;		// edit switch ports
			$_SESSION["AAA"]["permission"]["tool.template"]								= 1;		// everybody uses the template tool
			$_SESSION["AAA"]["permission"]["tool.ipv6plan"]								= 1;		// ipv6 subnet planning tool
			$_SESSION["AAA"]["permission"]["tool.search"]								= 1;		// new database search tool
			$_SESSION["AAA"]["permission"]["report.iosversion"]							= 1;		// run the ios version report
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
			$_SESSION["AAA"]["permission"]["monitoring.bgp"]							= 1;		// bgpmonitor
			$_SESSION["AAA"]["permission"]["information.management.device.*.*"]			= 1;		// Full control of devices (add edit deactivate rescan etc)
			$_SESSION["AAA"]["permission"]["information.*.tasklist.*"]					= 1;		// Permit view add edit action on tasklist
			$_SESSION["AAA"]["permission"]["information.mpls.vpn.view"]					= 1;		// Permit view on mpls l3vpn information
			$_SESSION["AAA"]["permission"]["information.ipplan.*.view"]					= 1;		// Permit view on ipplan.* information
			$_SESSION["AAA"]["permission"]["information.datacenter.*.view"]				= 1;		// Permit view on datacenter.* information
			$_SESSION["AAA"]["permission"]["information.bgp.asn.view"]					= 1;		// Permit view on bgp asn
			$_SESSION["AAA"]["permission"]["information.bgp.asn.add"]					= 1;		// Permit add bgp asn
			$_SESSION["AAA"]["permission"]["information.bgp.asn.edit"]					= 1;		// Permit edit bgp asn
			$_SESSION["AAA"]["permission"]["information.equipment.terminalserver.*"]	= 1;		// Permit view/edit/update terminal servers
			$_SESSION["AAA"]["permission"]["information.provisioning.*"]				= 1;		// Permit view add edit action on provisioning
			$_SESSION["AAA"]["permission"]["information.checklist.*.*"]					= 1;		// Permit view add edit action on checklists
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkEngineering",1))							// Network Engineering Only
		{
			$_SESSION["AAA"]["permission"]["information.ipplan.address.add"]			= 1;		// Only add/edit networks or addresses, NOT BLOCKS
			$_SESSION["AAA"]["permission"]["information.ipplan.address.edit"]			= 1;
			$_SESSION["AAA"]["permission"]["information.ipplan.network.add"]			= 1;
			$_SESSION["AAA"]["permission"]["information.ipplan.network.edit"]			= 1;
			$_SESSION["AAA"]["permission"]["information.datacenter.*.add"]				= 1;
			$_SESSION["AAA"]["permission"]["information.datacenter.*.edit"]				= 1;
		}
		/*************************
		* USER based permissions *
		*************************/
		if ($_SESSION["AAA"]["username"] == "steve")										// Let steve run switch port viewer in read only mode
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
		}
		if ($_SESSION["AAA"]["username"] == "andrew"								||		// Only let the qualified engineers assign blocks
			$_SESSION["AAA"]["username"] == "jw"									)
		{
			$_SESSION["AAA"]["permission"]["information.mpls.vpn.*"]					= 1;		// Permit add edit on mpls l3vpn information
			$_SESSION["AAA"]["permission"]["information.ipplan.block.add"]				= 1;		// Permit add ipplan block
			$_SESSION["AAA"]["permission"]["information.ipplan.block.edit"]				= 1;		// Permit edit ipplan block
			$_SESSION["AAA"]["permission"]["debug"]										= 1;		// Permit debug
		}
		if ($_SESSION["AAA"]["username"] == "management"								||		// 
			$_SESSION["AAA"]["username"] == "bigwig"									||		// 
			$_SESSION["AAA"]["username"] == "bossman"									)		// 
		{
			$_SESSION["AAA"]["permission"] = array();												// Clear out all permissions
			$_SESSION["AAA"]["permission"]["debug"]										= 0;		// Deny debug
			$_SESSION["AAA"]["permission"]["ldap"]										= 0;		// Deny LDAP query tool
			$_SESSION["AAA"]["permission"][".*"]										= 1;		// Permit everything else (Godmode=ON)
		}
		if ($_SESSION["AAA"]["username"] == "john.lavoie"								||		// 3
		    $_SESSION["AAA"]["username"] == "somebody.else"								)		// somebody else
		{
			$_SESSION["AAA"]["permission"] = array();												// Clear out all permissions
			$_SESSION["AAA"]["permission"][".*"]										= 1;		// Godmode On (IDKFA BlackSheepWall)
			$_SESSION["DEBUG"]															= 1;		// Set debug level to 1 automatically!
		}

		// Redirect them to their original destination
//		header("Location: " . $_SERVER['REQUEST_URI']);		// Did not send query string so this was replaced.
		header("Location: " . $_SERVER['HTTP_REFERER']);	// Changed to capture query string as well as URL.
		exit;
	}else{
		session_destroy();
		$HEADER = "Authentication Failure!\n";
		if (preg_match('/\.admin$/',$USERNAME,$REG)) { $HEADER = "Please do not login using windows administration (.ADMIN) accounts!"; }
	}
}

if (!$_SESSION["AAA"]["authenticated"])
{
	$HTML->breadcrumb(" ","");
	print $HTML->header($HEADER);

	print <<<END
	<form name="login" method="post" action="{$_SERVER['PHP_SELF']}">
	Username: <input type="text" size=50 name="username"><br>
	Password: <input type="password" size=50 name="password"><br>
	<input type="submit" value="Log In">
	</form>
END;
	$HTML->set("LOGOUT_LINK","");
	exit($HTML->footer());
}

?>
