<?php

/**
 * include/command/command_telnet.class.php
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

require_once "command.class.php";




class command_telnet	extends command
{
	private $telnet;

	public function connect()
	{
		if ($this->connected)	{ return $this->connected; }
		$this->settimeout(5);
		$this->telnet = fsockopen($this->data['hostname'], 23, $errno, $errstr, $this->data['timeout']);


		if (!$this->telnet) { fclose($this->telnet); return 0; }else{ $this->connected = 1; }

		// Send our login credentials stored in the data structure.
		$this->settimeout(14);
		$BASETIME = microtime(); $BASETIME = explode(" ", $BASETIME); $BASETIME = $BASETIME[1] + $BASETIME[0];
		while ($CHALLENGE = $this->read("/(.*:)/"))
		{
			$DIFFTIME = microtime(); $DIFFTIME = explode(" ", $DIFFTIME); $DIFFTIME = $DIFFTIME[1] + $DIFFTIME[0];
			$TIMEDELTA = ($DIFFTIME - $BASETIME);
			if ($TIMEDELTA > $this->data['timeout']) { break; }

			if(preg_match("/(.*sername:)/i", $CHALLENGE, $matches))
			{
				$this->write($this->data['username'] . "\r\n");
			}

			elseif(preg_match("/(.*assword:)/i", $CHALLENGE, $matches))
			{
				$this->write($this->data['password'] . "\r\n");
				break;
			}
			else
			{
//				dumper($CHALLENGE);
			}
		}

		$this->prompt = "";
		$this->findprompt();

		return $this->prompt;
	}

	protected function write($command)
	{
		if (!$this->connected)	{ return 0; }
		fputs($this->telnet, $command);
	}

	protected function read($expect)
	{
		if (!$this->connected)	{ return 0; }
		$BUFFER = "";
		$BASETIME = microtime(TRUE);
		while ( floor(microtime(TRUE) - $BASETIME) < $this->data['timeout'])
		{
			$BUFFER .= fread($this->telnet,2048);
			if(preg_match($expect, $BUFFER, $matches)) { break; }
		}
		return $BUFFER;
	}

	public function settimeout($timeout)
	{
		$this->data['timeout'] = $timeout;

		return $this->data['timeout'];
	}

	public function disconnect()
	{
		if (!$this->connected)	{ return; }

		$this->write("exit\r\n");
		fclose($this->telnet);
		unset($this->telnet);
		return;
	}
}

?>
