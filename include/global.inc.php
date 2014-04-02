<?php

/**
 * include/global.inc.php
 *
 * Global entry point setup for php5-NetworkAutomation
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
 
/*****************************
* Network Automation Objects *
*****************************/
require_once "debug.class.php";
require_once "dBug.php";
require_once "utility.class.php";
require_once "libjohn.inc.php";		//TODO REWRITE ME INTO THE NEW UTILITY OBJECT
require_once "ciscoconfig.class.php";	// SNMP cisco config grabber
//require_once "device.class.php";
require_once "cisco.class.php";
require_once "js.class.php";
require_once "html.class.php";
require_once "database.class.php";
require_once "session.class.php";
require_once "ldap.class.php";
require_once "ping.class.php";
require_once "information/information.class.php";
set_include_path(get_include_path().PATH_SEPARATOR.BASEDIR."/include/command/phpseclib/"); // Make PHPSecLib happy
require_once "command/command.class.php";

/**************
* CLI Library *
**************/
if (php_sapi_name() == "cli")
{
    require_once "commandline.class.php";
}

/*****************
* PEAR Libraries *
*****************/
set_include_path(get_include_path().PATH_SEPARATOR."/usr/share/php/");
require_once "Net/IPv4.php";
require_once "Net/IPv6.php";

/****************************
* Connect To MYSQL Database *
****************************/
$TRIES = 0;
while($TRIES < 3)
{
	$ERROR = 0;
	try {
		$DB = new Database();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}\n";
		trigger_error($MESSAGE);
		$ERROR++;
	}
	if (!$ERROR)
	{
		break;
	}else{
		unset($DB);
		$TRIES++;
		sleep(1);
	}
}
if ($TRIES >= 3)
{
	die("FATAL ERROR: Could not connect to mysql after 3 consecutive tries!\n");
}

/*************************
* Cread our Debug object *
*************************/
try {
	$DEBUG = new Debug();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

if (php_sapi_name() != "cli" && !defined("NO_AUTHENTICATION"))	// Do not start session for CLI initiated PHP!
{
	/********************
	* Start PHP Session *
	********************/
	try {
		$SESSION = new Session();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE);
	}

	/*******
	* LDAP *
	*******/
	try {
		$LDAP = new LDAP(
						array(
							"base_dn"           => LDAP_BASE,
							"admin_username"    => LDAP_USER,
							"admin_password"    => LDAP_PASS,
							"domain_controllers"=> array(LDAP_HOST),
							"ad_port"           => LDAP_PORT,
							"account_suffix"    => "@" . LDAP_DOMAIN,
						)
					);
	} catch (adLDAPException $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE);
	}

	/******
	* AAA *
	******/
	if (isset($_SESSION) && !isset($_SESSION["AAA"]))	// If we have a session AND dont have an AAA section, lets create one and ask them to log in!
	{
		$_SESSION["AAA"] = array();
		$_SESSION["AAA"]["authenticated"] = 0;
		$_SESSION["AAA"]["username"] = "Anonymous";
		$_SESSION["AAA"]["permission"] = array();
	}

	/***********************
	* HTML Utility Object  *
	***********************/
	try {
		$HTML = new HTML;
		$HTML->set("AAA_USERNAME", $_SESSION["AAA"]["username"]);
		$HTML->set("LOGOUT_LINK","<a href=\"/logout.php\">Log Out</a></font><br>\n");
		$HTML->set("FOOTERDEBUG", "");
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE . $HTML->footer());
	}

	/****************
	* URL Variables *
	****************/
	$THISPAGE = $HTML->thispage;
	$LASTPAGE = $HTML->lastpage;

	/*********************
	* Require Valid User *
	*********************/
	if (!$_SESSION["AAA"]["authenticated"]) // If we are NOT authenticated, and not running a CLI app, print out the form and collect the credentials!
	{
		require_once 'login.inc.php';	// This prints the form and processes ldap login credentials!
	}

}

?>
