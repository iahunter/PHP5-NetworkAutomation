<?php

/**
 * include/debug.class.php
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
 
define("DEBUG_SUMMARY"	,0);  // Print summary debug text
define("DEBUG_TEXT"		,1);  // Print text inline
define("DEBUG_COMMENT"	,2);  // Print html <!-- comments -->
define("DEBUG_HTML"		,3);  // Print html formatted
define("DEBUG_DATABASE"	,4);  // Log messages to database table
define("DEBUG_EMAIL"	,5);  // Log messages to email address

Class Debug
{
	public $format;
	public $messages;

	public function __construct($FORMAT = DEBUG_SUMMARY)
	{
		$this->format = $FORMAT;
		$this->messages = array();
	}

	public function message($MESSAGE,$LEVEL = 1)
	{
		if ($LEVEL >= $_SESSION["DEBUG"])
		{
			$MESSAGE = "DEBUG-{$LEVEL} {$MESSAGE}";
			if ($this->format == DEBUG_SUMMARY)
			{
				array_push($this->messages, $MESSAGE);
			}
			if ($this->format == DEBUG_TEXT)
			{
				array_push($this->messages, $MESSAGE);
				print "$MESSAGE\n";
			}
			if ($this->format == DEBUG_COMMENT)
			{
				array_push($this->messages, $MESSAGE);
				print "<!-- $MESSAGE -->\n";
			}
			if ($this->format == DEBUG_HTML)
			{
				array_push($this->messages, $MESSAGE);
				print "<pre>$MESSAGE</pre>\n";
			}
			if ($this->format == DEBUG_DATABASE)
			{
				global $DB;
				$DB->log($MESSAGE,$LEVEL);
			}
			if ($this->format == DEBUG_EMAIL)
			{
				$DB->log($MESSAGE,$LEVEL);
				$LOGTO   = EMAIL_TO;
				$LOGFROM = EMAIL_FROM;
				$LOGHEADER = "From: NetworkTool <{$LOGFROM}>\r\nX-Mailer: php";
				$LOGSUB  = "Tool Log Debug";
				global $LDAP;
				$REALNAME = $LDAP->user_to_realname($USERNAME);
				$LOGBODY = "User: $USERNAME ($REALNAME)\nTool: $LOCATION\nMessage: $MESSAGE\n";
				if ($DETAILS) { $LOGBODY .= "Details: $DETAILS\n";}
				mail($LOGTO, $LOGSUB, $LOGBODY, $LOGHEADER);
			}
		}
	}

	public function format($FORMAT = DEBUG_SUMMARY)
	{
		$this->format = $FORMAT;
	}


}
