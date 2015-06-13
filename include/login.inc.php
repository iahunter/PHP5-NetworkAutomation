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

if (isset($_POST['username']))
{
	$USERNAME = strtolower($_POST["username"]);
	$CERTIFICATE = "";
}elseif(
		isset($_SERVER["SSL_CLIENT_VERIFY"		])	&&
		$_SERVER["SSL_CLIENT_VERIFY"] == "SUCCESS"	&&
		isset($_SERVER["SSL_CLIENT_V_REMAIN"	])	&&
		$_SERVER["SSL_CLIENT_V_REMAIN"] > 0			&&
		isset($_SERVER["SSL_CLIENT_S_DN_CN"])		)
{
	$USERNAME = strtolower($_SERVER["SSL_CLIENT_S_DN_CN"]);
	$CERTIFICATE = $_SERVER["SSL_CLIENT_CERT"];
//	print "trying cert auth!\n<br>USERNAME: {$USERNAME}\n<br>";
}else{
//	print "no creds supplied\n";
	$USERNAME = "";
	$CERTIFICATE = "";
}
if (isset($_POST['password']))	{ $PASSWORD = $_POST['password']; }else{ $PASSWORD = ""; }

$HEADER = "Please Log In";

/*******
* LDAP *
*******/
try {
	$LDAP = new LDAP(
					array(
						"base_dn"			=> LDAP_BASE,
						"admin_username"	=> LDAP_USER,
						"admin_password"	=> LDAP_PASS,
						"domain_controllers"=> array(LDAP_HOST),
						"ad_port"			=> LDAP_PORT,
						"account_suffix"	=> "@" . LDAP_DOMAIN,
					)
				);
} catch (adLDAPException $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

// Authenticate the user with a username and password, or a certificate!
if ( $USERNAME && $PASSWORD )	// If we are given username and password, attempt to authenticate
{
	if($LDAP->authenticate($USERNAME,$PASSWORD) && !preg_match('/\.admin$/',$USERNAME,$REG))
	{
		$AUTHENTICATED = "LDAP";
	}else{
		$AUTHENTICATED = 0;
		session_destroy();
		$HEADER = "Authentication Failure!\n";
		if (preg_match('/\.admin$/',$USERNAME,$REG)) { $HEADER = "Please do not login using windows administration (.ADMIN) accounts!"; }
	}
}elseif( $USERNAME && $CERTIFICATE )	// If we have a username and a certificate!
{
	// Make sure the username in our certificate is ACTUALLY in AD!
	$USERSTRUCT = $LDAP->user()->info( $USERNAME , array("*") );
	if ( $USERSTRUCT && !preg_match('/\.admin$/',$USERNAME,$REG) )
	{
		// TODO: Test cert here for other stuff like revocation and whatnot?
		$AUTHENTICATED = "PKI";
	}else{
		$AUTHENTICATED = 0;
		session_destroy();
		$HEADER = "Certificate Authentication Failure!\n";
		if (preg_match('/\.admin$/',$USERNAME,$REG)) { $HEADER = "Please do not login using windows administration (.ADMIN) accounts!"; }
	}
}else{
	$AUTHENTICATED = 0;
}


// If we have a username and authenticated successfully
if ( $USERNAME && $AUTHENTICATED )
{
		$_SESSION["DEBUG"				] = 0;				// Clear the debug flag
		$_SESSION["AAA"]["authenticated"] = $AUTHENTICATED;	// Successfully Authenticated User!
		$_SESSION["AAA"]["username"		] = $USERNAME;		// Store the username
		$_SESSION["AAA"]["realname"		] = $LDAP->user_to_realname($USERNAME);	// Real name from LDAP
		$MESSAGE = "Authentication succeeded for user {$USERNAME} from {$_SERVER["REMOTE_ADDR"]}";
		if ( $CERTIFICATE ) { $MESSAGE.= " by Certificate"; }else{ $MESSAGE .= " by Password"; }
		$DB->log($MESSAGE,1);

		// Include this file to set the permissions for $USERNAME matching $LDAP groups
		require_once("aaa.inc.php");

		// Redirect the authenticated user to their original destination
		header("Location: " . $_SERVER['HTTP_REFERER']);	// Changed to capture query string as well as URL.
		exit;
}

// If the user is not authenticated, print out the login form
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
