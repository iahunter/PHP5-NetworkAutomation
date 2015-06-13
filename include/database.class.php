<?php

/**
 * include/database.class.php
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

class Database
{
	public $QUERIES;
	public $QUERYTIME;
	public $CACHETIME;
	public $RECORDCOUNT;

	public $DB_HANDLE;
	public $DB_STATEMENT;

	public static function try_connect( $TRIES = 3 )
	{
		if ( $TRIES <= 0 ) { die("FATAL ERROR: Could not connect to mysql after multiple consecutive tries!\n"); }

		global $DB;
	    try {
	        $DB = new Database();
	    } catch (Exception $E) {
	        $MESSAGE = "Exception: {$E->getMessage()}\n";
	        trigger_error($MESSAGE);
	        unset($DB);
	        sleep(1);
			Database::try_connect($TRIES - 1);	// Recursion is cool because recursion is cool because recursion is cool
		}
	}

	public function __construct(
								$DATASOURCE = "",
								$USERNAME = DB_USERNAME,
								$PASSWORD = DB_PASSWORD
								)
	{
		$this->QUERIES = array();
		$this->QUERYTIME = 0;
		$this->CACHETIME = 0;
		$this->RECORDCOUNT = 0;
		if ( $DATASOURCE == "" ) { $DATASOURCE = "mysql:host=" . DB_HOSTNAME . ";dbname=" . DB_DATABASE; }
		$OPTIONS = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		);
		$this->DB_HANDLE = new PDO($DATASOURCE,$USERNAME,$PASSWORD,$OPTIONS);
	}

	public function close()
	{
		$this->DB_HANDLE = null;
	}

	public function query($QUERY)
	{
		$this->DB_STATEMENT = $this->DB_HANDLE->prepare($QUERY);
	}

	public function bind_array($ARRAY)
	{
		foreach ($ARRAY as $KEY=>$VALUE)
		{
			$this->bind($KEY,$VALUE);
		}
	}

	public function bind($PARAMETER, $VALUE, $TYPE = null)
	{
		if (is_null($TYPE))
		{
			$TYPE = PDO::PARAM_STR;
			if	(is_int		($VALUE))	{ $TYPE = PDO::PARAM_INT;	}
			if	(is_bool	($VALUE))	{ $TYPE = PDO::PARAM_BOOL;	}
			if	(is_null	($VALUE))	{ $TYPE = PDO::PARAM_NULL;	}
			if	(is_string	($VALUE))	{ $TYPE = PDO::PARAM_STR;	}
		}
		$this->DB_STATEMENT->bindValue(":".$PARAMETER, $VALUE, $TYPE);
	}

	public function execute()
	{
//		array_push($this->QUERIES,$this->DB_STATEMENT->queryString);
		$BASETIME = Utility::microtime_ticks();
		$this->DB_STATEMENT->execute();
		$DIFFTIME = Utility::microtime_ticks() - $BASETIME;

		$QUERY = array();
		$QUERY["query"] = $this->DB_STATEMENT->queryString;
		$QUERY["time"] = $DIFFTIME;
		array_push($this->QUERIES,$QUERY);
		$this->QUERYTIME += $DIFFTIME;
		$this->RECORDCOUNT += intval($this->DB_STATEMENT->rowCount());
	}

	public function results()
	{
		$RESULTS = $this->DB_STATEMENT->fetchAll(PDO::FETCH_ASSOC);
		return $RESULTS;
	}

	public function get_insert_id()
	{
		return $this->DB_HANDLE->lastInsertId();
	}

	public function log($MESSAGE,$LEVEL = 0,$DETAILS = null)
	{
		if (isset($_SESSION["AAA"]["username"]) && $_SESSION["AAA"]["username"] != "" && $_SESSION["AAA"]["username"] != "Anonymous")
		{
			$USERNAME = $_SESSION["AAA"]["username"];
		}else{
			$USERNAME = LDAP_USER;
		}
		$LOCATION = basename($_SERVER["SCRIPT_FILENAME"]);
		$QUERY = "INSERT INTO log (date,user,tool,level,description) VALUES (now(),:USERNAME,:LOCATION,:LEVEL,:MESSAGE)";
		$this->query($QUERY);
		$this->bind("USERNAME",$USERNAME);
		$this->bind("LOCATION",$LOCATION);
		$this->bind("LEVEL"   ,$LEVEL);
		$this->bind("MESSAGE" ,$MESSAGE );
		try {
		    $this->execute();
		} catch (Exception $E) {
		    $MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			dumper($E);
			$this->DB_STATEMENT->debugDumpParams();
			global $HTML;
			die($HTML->footer());
		}
	}

}

?>
