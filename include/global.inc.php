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
require_once "cisco.class.php";
require_once "js.class.php";
require_once "html.class.php";
require_once "database.class.php";
require_once "session.class.php";
require_once "ldap.class.php";
require_once "ping.class.php";
require_once "gearman_client.class.php";
require_once "information/information.class.php";
set_include_path(get_include_path().PATH_SEPARATOR.BASEDIR."/include/command/phpseclib/"); // Make PHPSecLib happy
require_once "command/command.class.php";

/*****************
* PEAR Libraries *
*****************/
set_include_path(get_include_path().PATH_SEPARATOR."/usr/share/php/");
require_once "Net/IPv4.php";
require_once "Net/IPv6.php";

/***************
* SQL Database *
***************/
Database::try_connect(3);	// Try to connect to our sql DB, give it 3 tries and then give up...

/***************
* Memory Cache *
***************/
if ( defined("CACHE_ENABLED") && CACHE_ENABLED)
{
	require_once("cache.class.php");

	$PARAMS = array(
					"scheme"=> CACHE_SCHEME,
					"host"  => CACHE_HOST,
					"port"  => CACHE_PORT,
					);
	$OPTIONS = array(
					"prefix"=> CACHE_PREFIX,
					);
	try {
		$CACHE = new Cache($PARAMS,$OPTIONS);
		$CACHE->auth(CACHE_AUTH);
	} catch (Exception $E) {
		$MESSAGE .= "Exception: {$E->getMessage()}\n";
		trigger_error($MESSAGE);
		unset($CACHE);
	}
}

/**************************
* Create our Debug object *
**************************/
try {
	$DEBUG = new Debug();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

/**************
* CLI Runtime *
**************/
if (php_sapi_name() == "cli")
{
    require_once "commandline.class.php";
}else{
/**************
* WWW Runtime *
**************/
	if ( isset($_GET) )
	{
		foreach ($_GET as $KEY => $VALUE)
		{
			if ( !is_array($VALUE) )	// Do not purify valid htmlized arrays!
			{
				$_GET[$KEY] = htmlspecialchars($VALUE);	// Fuck you cross site script kiddies
			}
		}
	}

	if (!defined("NO_AUTHENTICATION"))	// Do not start session for CLI initiated PHP!
	{
		// Start PHP Session
		try {
			$SESSION = new Session();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			die($MESSAGE);
		}

		// AAA
		if (isset($_SESSION) && !isset($_SESSION["AAA"]))	// If we have a session AND dont have an AAA section, lets create one and ask them to log in!
		{
			$_SESSION["AAA"] = array();
			$_SESSION["AAA"]["authenticated"] = 0;
			$_SESSION["AAA"]["username"] = "Anonymous";
			$_SESSION["AAA"]["permission"] = array();
		}

		// HTML Utility Object
		$HTML = new HTML;
		$HTML->set("AAA_USERNAME", $_SESSION["AAA"]["username"]);
		$HTML->set("LOGOUT_LINK","<a href=\"/logout.php\">Log Out</a></font><br>\n");
		$HTML->set("FOOTERDEBUG", "");
		if ( defined("MONITOR_USER_EXPERIENCE") && MONITOR_USER_EXPERIENCE )
		{
			$BOOMERANG = <<<END
<script src="/js/boomerang-0.9.1415235235.js" type="text/javascript"></script>
<script src="/js/boomerang.js.php" type="text/javascript"></script>
END;
			$HTML->set("MONITOR_USER_EXPERIENCE",$BOOMERANG);
		}

		// Require Valid User
		if (!$_SESSION["AAA"]["authenticated"]) // If we are NOT authenticated, and not running a CLI app, print out the form and collect the credentials!
		{
			require_once 'login.inc.php';	// This prints the form and processes ldap login credentials!
		}
	}
}

?>
