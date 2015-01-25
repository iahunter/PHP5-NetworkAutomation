<?php

/**
 * include/ldap.class.php
 *
 * Extended version of the adLDAP object
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

require_once("adLDAP/adLDAP.php");

// Extend the base adLDAP class with our unlock function
class LDAP extends adLDAP\adLDAP
{
	public $QUERIES;

	public function authenticate($USERNAME,$PASSWORD)
	{
		global $DB;
		$AUTHENTICATED = 0;
		$USERIP = $_SERVER['REMOTE_ADDR'];

		$this->QUERIES++;
		$AUTHENTICATED = parent::authenticate($USERNAME,$PASSWORD);
		if (!$AUTHENTICATED)
		{
			$MESSAGE = "Authentication failed for user $USERNAME from $USERIP LDAP error " . $this->getLastError();
			$DB->log($MESSAGE,1);
		}
		return $AUTHENTICATED;
	}

	public function user_to_realname($USERNAME)
	{
		// These lines are addded to provide a local cache of ldap user-name mappings
		// Without this, we will hit the ldap server EVERY time
		// This can lead to dramatically higher load times on pages with ALOT of username lookups
		$USER_LDAPNAME_MAP_FILE = "/tmp/PHP_USER_LDAPNAME_MAP";						// Keep a temp file for caching a serialized structure of username-realname mappings
		$USER_REALNAME = unserialize(file_get_contents($USER_LDAPNAME_MAP_FILE));	// Load the file and de-serialize it to a structure
		if(isset($USER_REALNAME[$USERNAME])) { return $USER_REALNAME[$USERNAME]; }	// If we have a hit in this structure, return it immediately

		$this->QUERIES++;
		$REALNAME = "";
		foreach ($this->user()->info($USERNAME,array("*")) as $LDAP_RECORD)
		{
			$REALNAME = trim($LDAP_RECORD["givenname"][0] . " " . $LDAP_RECORD["sn"][0]);
		}
		if($REALNAME == "") { $REALNAME = $USERNAME; }
		$USER_REALNAME[$USERNAME] = $REALNAME;										// Since we didnt find their username in the cache, save their realname now
		file_put_contents($USER_LDAPNAME_MAP_FILE, serialize($USER_REALNAME));		// Serialize and store the object for future use
		return $REALNAME;
	}

	public function unlock_user($username)
	{
		$user = $this->user()->info($username, array("cn"));
		if ($user[0]['dn'] == NULL)
		{
			return (false);
		}

		$user_dn = $user[0]['dn'];
		$add['lockoutTime'] = array(0);
		$result = ldap_mod_replace($this->getLdapConnection(), $user_dn, $add);

		if ($result == false)
		{
			return (false);
		}
		return (true);
	}

}

?>
