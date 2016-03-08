<?php

/**
 * include/aaa.inc.php
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category	default
 * @package		none
 * @author		John Lavoie
 * @copyright	2009-2016 @authors
 * @license		http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

// When this file is included, it assumes the $LDAP object is created and connected, and $USERNAME contains the username!

		/**************************
		* GROUP based permissions *
		**************************/
		if ($LDAP->user()->inGroup($USERNAME,"SwitchAdmins",1))									// Desktop Support Switch Admins
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
			$_SESSION["AAA"]["permission"]["tool.switch.edit"]							= 1;		// edit switch ports
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkImplementation",1))						// SWAT Network Implementation
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
			$_SESSION["AAA"]["permission"]["tool.switch.edit"]							= 1;		// edit switch ports
			$_SESSION["AAA"]["permission"]["tool.template"]								= 1;		// everybody uses the template tool
			$_SESSION["AAA"]["permission"]["report.iosversion"]							= 1;		// run the ios version report
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
			$_SESSION["AAA"]["permission"]["information.management.device.*.*"]			= 1;		// Full control of devices (add edit deactivate rescan etc)
			$_SESSION["AAA"]["permission"]["information.ipplan.*.view"]					= 1;		// Permit view on ipplan.* information
			$_SESSION["AAA"]["permission"]["information.ipplan.*.edit"]					= 1;		// Permit edit on ipplan.* information
			$_SESSION["AAA"]["permission"]["information.bgp.asn.view"]					= 1;		// Permit view on bgp asn)
			$_SESSION["AAA"]["permission"]["information.bgp.asn.edit"]					= 1;		// Permit edit bgp asn
			$_SESSION["AAA"]["permission"]["information.provisioning.*.view"]			= 1;		// Permit view on provisioning.* information
			$_SESSION["AAA"]["permission"]["information.provisioning.*.edit"]			= 1;		// Permit edit on provisioning.* information
			$_SESSION["AAA"]["permission"]["information.provisioning.*.action.*"]		= 1;		// Permit provisioning actions
			$_SESSION["AAA"]["permission"]["information.equipment.terminalserver.*"]	= 1;		// Permit view/edit/update terminal servers
			$_SESSION["AAA"]["permission"]["websvn.configrepo"]							= 1;		// Permit access to websvn config repo
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkEngineering",1)					||		// Network Engineering
			$LDAP->user()->inGroup($USERNAME,"IMNetworkOperations",1)					||		// Network Operations (KSS Tier 2)
			$LDAP->user()->inGroup($USERNAME,"IMServerAdminCore",1)						||		// Server Admin Core Team
			$LDAP->user()->inGroup($USERNAME,"ClientTechnologyServices",1)				||		// Citrix VDI Admins and desktop guys
			$LDAP->user()->inGroup($USERNAME,"IM.SecurityAnalyst",1)					||		// Infosec dudes!
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
			$_SESSION["AAA"]["permission"]["tool.diff"]									= 1;		// cherry checker
			$_SESSION["AAA"]["permission"]["report.iosversion"]							= 1;		// run the ios version report
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
			$_SESSION["AAA"]["permission"]["monitoring.bgp"]							= 1;		// bgpmonitor
			$_SESSION["AAA"]["permission"]["information.management.device.*.*"]			= 1;		// Full control of devices (add edit deactivate rescan etc)
			$_SESSION["AAA"]["permission"]["information.*.tasklist.*"]					= 1;		// Permit view add edit action on tasklist
			$_SESSION["AAA"]["permission"]["information.mpls.vpn.view"]					= 1;		// Permit view on mpls l3vpn information
			$_SESSION["AAA"]["permission"]["information.ipplan.*.view"]					= 1;		// Permit view on ipplan.* information
			$_SESSION["AAA"]["permission"]["information.datacenter.*.view"]				= 1;		// Permit view on datacenter.* information
			$_SESSION["AAA"]["permission"]["information.bgp.asn.*"]						= 1;		// Permit full control for BGP ASNs
			$_SESSION["AAA"]["permission"]["information.equipment.terminalserver.*"]	= 1;		// Permit view/edit/update terminal servers
			$_SESSION["AAA"]["permission"]["information.provisioning.*"]				= 1;		// Permit view add edit action on provisioning
			$_SESSION["AAA"]["permission"]["information.checklist.*.*"]					= 1;		// Permit view add edit action on checklists
			$_SESSION["AAA"]["permission"]["information.clr.*.*"]						= 1;		// Permit view add edit action on circuit layout reports
			$_SESSION["AAA"]["permission"]["information.security.*.view"]				= 1;		// Permit view of any security provisioning stuffs
			$_SESSION["AAA"]["permission"]["information.security.network_host.add"]		= 1;		// Permit add of security provisioning network_host
			$_SESSION["AAA"]["permission"]["information.security.network_host.edit"]	= 1;		// Permit edit of security provisioning network_host
			$_SESSION["AAA"]["permission"]["information.security.application.*.action.spreadsheet"]	= 1;		// Permit these guys to look at the spreadsheet for apps
			$_SESSION["AAA"]["permission"]["websvn.configrepo"]							= 1;		// Permit access to websvn config repo
			$_SESSION["AAA"]["permission"]["information.ipplan.address.add"]			= 1;		// Only add/edit networks or addresses, NOT BLOCKS
			$_SESSION["AAA"]["permission"]["information.ipplan.address.edit"]			= 1;
			$_SESSION["AAA"]["permission"]["monitoring.honeypot"]						= 1;		// honeypot live monitor
			$_SESSION["AAA"]["permission"]["pewpew"]									= 1;		// New iPew module
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMNetworkEngineering",1))							// Network Engineering Only
		{
			$_SESSION["AAA"]["permission"]["information.ipplan.network.add"]			= 1;
			$_SESSION["AAA"]["permission"]["information.ipplan.network.edit"]			= 1;
			$_SESSION["AAA"]["permission"]["information.datacenter.*.add"]				= 1;
			$_SESSION["AAA"]["permission"]["information.datacenter.*.edit"]				= 1;
			$_SESSION["AAA"]["permission"]["information.blackhole.hostile.view"]		= 1;		// Permit view blackhole
		}
		if ($LDAP->user()->inGroup($USERNAME,"IMAppAdmin",1)							||
			$LDAP->user()->inGroup($USERNAME,"KieCoreTeam",1)							)		// DCO IM Application Admins and solution designers
		{
			$_SESSION["AAA"]["permission"]["information.ipplan.*.view"]					= 1;		// Permit view on ipplan.* information
			$_SESSION["AAA"]["permission"]["information.security.*.view"]				= 1;		// Permit view of any security provisioning stuffs
			$_SESSION["AAA"]["permission"]["information.security.network_host.add"]		= 1;		// Permit add of security provisioning network_host
			$_SESSION["AAA"]["permission"]["information.security.network_host.edit"]	= 1;		// Permit edit of security provisioning network_host
			$_SESSION["AAA"]["permission"]["information.security.application.*.action.spreadsheet"]	= 1;		// Permit these guys to look at the spreadsheet for apps
		}
		/*************************
		* USER based permissions *
		*************************/
		if ($_SESSION["AAA"]["username"] == "andrew.someguy")									// Andrew is a CE guy,
		{
			$_SESSION["AAA"]["permission"]["report.siteservice"]						= 1;		// run the site service report
		}
		if ($_SESSION["AAA"]["username"] == "steve.kungfoo")									// Let steve run switch port viewer in read only mode
		{
			$_SESSION["AAA"]["permission"]["tool.switch.view"]							= 1;		// view switch ports
		}
		if ($_SESSION["AAA"]["username"] == "andrew"									||		// Only let the qualified engineers assign blocks
			$_SESSION["AAA"]["username"] == "jw"										||
			$_SESSION["AAA"]["username"] == "travis"									)
		{
			$_SESSION["AAA"]["permission"]["information.mpls.vpn.*"]					= 1;		// Permit add edit on mpls l3vpn information
			$_SESSION["AAA"]["permission"]["information.ipplan.block.add"]				= 1;		// Permit add ipplan block
			$_SESSION["AAA"]["permission"]["information.ipplan.block.edit"]				= 1;		// Permit edit ipplan block
			$_SESSION["AAA"]["permission"]["information.security.*"]					= 1;		// Permit edit of any security provisioning stuffs
			$_SESSION["AAA"]["permission"]["debug"]										= 1;		// Permit debug
            $_SESSION["AAA"]["permission"]["tool.log"]                                  = 1;		// Permit log reading
		}
		if ($_SESSION["AAA"]["username"] == "john.three"								||		// 3
			$_SESSION["AAA"]["username"] == LDAP_USER									||		// Tool Account (ONLINE LDAP AND OFFLINE DEV)
		    $_SESSION["AAA"]["username"] == "metaclassing"								)		// Offline (NO LDAP) development username
		{
			$_SESSION["AAA"]["permission"] = array();												// Clear out all permissions
			$_SESSION["AAA"]["permission"][".*"]										= 1;		// Godmode On (IDKFA BlackSheepWall)
			$_SESSION["DEBUG"]															= 1;		// Set debug level to 1 automatically!
		}
		// All users must be able to do boomerang beacon action/event for APM!
		$_SESSION["AAA"]["permission"]["information.boomerang.beacon.action.event"]		= 1;		// Permit users to trigger boomerang/beacon events!
