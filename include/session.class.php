<?php

/**
 * include/session.class.php
 *
 * Session handler
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

require_once "database.class.php";

Class Session
{
	public $DB;
	public $DATA;

	public function __construct()
	{
		$this->DB = new Database;

		session_set_save_handler(
			array($this, "open"),
			array($this, "close"),
			array($this, "read"),
			array($this, "write"),
			array($this, "destroy"),
			array($this, "garbage")
		);

		ini_set("session.auto_start",			0);
		ini_set("session.gc_probability",		1);
		ini_set("session.gc_divisor",			100);
		ini_set("session.gc_maxlifetime",		604800);
		ini_set("session.referer_check",		"");
		ini_set("session.entropy_file",			"/dev/urandom");
		ini_set("session.entropy_length",		16);
		ini_set("session.use_cookies",			1);
		ini_set("session.use_only_cookies",		1);
		ini_set("session.use_trans_sid",		0);
		ini_set("session.hash_function",		1);
		ini_seT("session.hash_bits_per_character",	5);
		ini_set("session.cookie_lifetime",		259200); // 72 hours

		session_cache_limiter("nocache");
		session_set_cookie_params(0);
		session_name("nosx_netman_session");

		session_start();
	}

	public function open()
	{
		if ($this->DB) { return 1; }	// Make sure we have a valid database connection!
		return 0;
	}

	public function close()
	{
		return 1;
	}

	public function read($ID)
	{
		$QUERY = "SELECT data FROM session where id = :ID";
		$this->DB->query($QUERY);
		try {
			$this->DB->bind("ID",$ID);
			$this->DB->execute();
			$RESULTS = $this->DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}

		$COUNT = count($RESULTS);
		$MESSAGE = "Session {$ID} Read, {$COUNT} Records Returned.";
//		trigger_error($MESSAGE);
		$this->DATA = "";
		if($COUNT > 0)
		{
			$this->DATA = reset($RESULTS)["data"];
		}
		return $this->DATA;
	}

	public function write($ID,$DATA)
	{
		if ($this->DATA == $DATA) { return 1; /* IF our session did NOT change, do NOT write it to the database! */ }
		$QUERY = "REPLACE INTO session VALUES (:ID, :DATA, now())";
		$this->DB->query($QUERY);
		try {
			$this->DB->bind("ID",$ID);
			$this->DB->bind("DATA",$DATA);
			$this->DB->execute();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		$COUNT = $this->DB->DB_STATEMENT->rowCount();
		$MESSAGE = "Session {$ID} Written, {$COUNT} Records Altered.";
//		trigger_error($MESSAGE);
		return 1;
	}

	public function destroy($ID)
	{
		$QUERY = "DELETE FROM session where id = :ID";
		$this->DB->query($QUERY);
		try {
			$this->DB->bind("ID",$ID);
			$this->DB->execute();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		if (isset($_SESSION["AAA"]["username"]) && $_SESSION["AAA"]["username"] != "" && $_SESSION["AAA"]["username"] != "Anonymous")
		{
			$MESSAGE = "Session Destroyed for user {$_SESSION["AAA"]["username"]} from {$_SERVER['REMOTE_ADDR']}";
			$this->DB->log($MESSAGE,1);	// This is the log message for users that logout
		}
		return 1;
	}

	public function garbage($TIMEOUT)
	{
		$DEADTIME = time() - $TIMEOUT;

		//// Go through the sessions we are about to delete and print them out! ////
		$QUERY = "SELECT * FROM session where UNIX_TIMESTAMP(lastseen) < {$DEADTIME}";
		$this->DB->query($QUERY);
		try {
			$this->DB->bind("DEADTIME",$DEADTIME);
		    $this->DB->execute();
		    $RESULTS = $this->DB->results();
		} catch (Exception $E) {
		    $MESSAGE = "Exception: {$E->getMessage()}";
		    trigger_error($MESSAGE);
		    global $HTML;
		    die($MESSAGE . $HTML->footer());
		}
		$COUNT = count($RESULTS);
		if($COUNT > 0)
		{
			$MESSAGE = "Running garbage collection with $TIMEOUT second timeout, {$COUNT} rows have been found:";
//			trigger_error($MESSAGE);
			foreach($RESULTS as $SESSION)
			{
				$MESSAGE = "SESSION {$SESSION["id"]} = ";
				$SES = Session::unserialize($SESSION["data"]);
				if (isset($SES["AAA"]))
				{
					$MESSAGE .= $SES["AAA"]["username"];
				}
//				trigger_error($MESSAGE);
			}
		}

		//// Delete the sessions! ////
		$QUERY = "DELETE FROM session where UNIX_TIMESTAMP(lastseen) < {$DEADTIME}";
		$this->DB->query($QUERY);
		try {
			$this->DB->bind("DEADTIME",$DEADTIME);
			$this->DB->execute();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
//			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		$COUNT = intval($this->DB->DB_STATEMENT->rowCount());
		$MESSAGE = "Ran garbage collection with $TIMEOUT second timeout, {$COUNT} rows have been deleted.";
//		if ($COUNT > 0) { trigger_error($MESSAGE); }

		return 0;
	}

    public static function unserialize($session_data) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

}

?>
