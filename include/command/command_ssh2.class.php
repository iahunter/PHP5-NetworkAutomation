<?php

/**
 * include/command/command_ssh2.class.php
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

require_once "phpseclib/Net/SSH2.php";
//define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX);

class command_ssh2	extends command
{
	private $ssh;

	public function connect()
	{
		if ($this->connected)	{ return $this->connected; }

		$this->ssh = new Net_SSH2($this->data['hostname']);
		$this->settimeout(5);

		$this->connected = $this->ssh->login($this->data['username'], $this->data['password']);

























		$this->prompt = "";
		$this->findprompt();

		return $this->prompt;
	}

	protected function write($command)
	{
		if (!$this->connected)	{ return 0; }
		$this->ssh->write($command);
	}

	protected function read($expect)
	{
		if (!$this->connected)	{ return 0; }











		return $this->ssh->read($expect, NET_SSH2_READ_REGEX);
	}

	public function settimeout($timeout)
	{
		$this->data['timeout'] = $timeout;
		$this->ssh->setTimeout($this->data['timeout']);
		return $this->data['timeout'];
	}

	public function disconnect()
	{
		if (!$this->connected)	{ return; }

		$this->write("exit\n");

		unset($this->ssh);
		return;
	}
}

?>
