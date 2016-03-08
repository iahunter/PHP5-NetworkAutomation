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

// Composer autoload dependencies
require_once "/opt/networkautomation/vendor/autoload.php";

/*****************************
* Network Automation Objects *
*****************************/
require_once "database.class.php";
require_once "session.class.php";
require_once "permission.inc.php";
require_once "ldap.class.php";
require_once "gearman_client.class.php";
require_once "information/information.class.php";
require_once "command/command.class.php";

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
	$DEBUG = new \metaclassing\Debug();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

// Make sure we only load www runtime stuff if we are NOT on the CLI
if (php_sapi_name() != "cli") {

	// Purify GET information we are sent
	if ( isset($_GET) ) {
		foreach ($_GET as $KEY => $VALUE) {
			if ( !is_array($VALUE) ) {
				$_GET[$KEY] = htmlspecialchars($VALUE);
			}
		}
	}

	// Do not start session for apps that dont use authenticated requests
	if (!defined("NO_AUTHENTICATION")) {
		// Start PHP Session
		try {
			$SESSION = new Session();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			die($MESSAGE);
		}

		// If we have a session AND dont have an AAA section, lets create one and ask them to log in!
		if (isset($_SESSION) && !isset($_SESSION["AAA"]))
		{
			$_SESSION["AAA"] = array();
			$_SESSION["AAA"]["authenticated"]	= 0;
			$_SESSION["AAA"]["username"]		= "Anonymous";
			$_SESSION["AAA"]["permission"]		= array();
		}
		$_SESSION["AAA"]["ip"]					= $_SERVER["REMOTE_ADDR"];
		$_SESSION["AAA"]["useragent"]			= $_SERVER["HTTP_USER_AGENT"];

		// HTML Utility Object
		$HTML = new \metaclassing\HTML;
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

		// If we are NOT authenticated, and not running a CLI app, print out the form and collect the credentials!
		if (!$_SESSION["AAA"]["authenticated"]) {
			require_once 'login.inc.php';	// This prints the form and processes ldap login credentials!
		}
	}
}
