<?php

/**
 * include/command/command.class.php
 *
 * Command line processing from devices
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

 
class Command {

	public $data;

	public $connected;
	public $prompt;

	public $patterns;
	public $pattern;

	public $service; // TELNET or SSH1 or SSH2

	public function __construct($DATA = null)
	{
		$this->connected = "";
		$this->prompt = "";
		$this->devicetype = "";

		// Load a set of common default prompt patterns!
		// These patterns are applied IN ORDER, so put the most specific FIRST!
		$this->patterns = array(
			/*
				Sample Prompts: ( Test with http://regex101.com/ )
					IOS	 -   KHONEMDCRRR01#
					IOS-XE  -   KHONEMDCRWA02#
					IOS-XR  -   RP/0/RSP0/CPU0:KHONEMDCRWA01#
					NXOS	-   KHONEMDCSWC01_ADMIN#
					ASA	 -   khonedmzrfw01/pri/act/901-IN#
			*/
				array(	'devicetype'	=> 'ciscoxr' ,
					'detect'	=> '/RP\/0\/RSP0\/CPU0:([\w\-]+)(.*)[#>]\s*$/' ,
					'match'		=> '/(.*)RP\/0\/RSP0\/CPU0:%s.*(>|#)\s*/'
					),

				array(	'devicetype'	=> 'cisco' ,
//					'detect'	=> '/([\w\-]+)(\/.*)?[#>]\s*$/' ,
					'detect'	=> '/(?!.*:)([\w\-\/]+)[#>]\s*$/' ,
					/*
									^--- Dont match anything up to a leading : (XR format)
											^--- Match a-z0-9 - and /
														^--- Terminate match with our prompt enders > and #
															^--- Ignore any trailing whitespace
					*/
					'match'		=> '/(.*)%s.*(>|#)\s*/'
					),

/*				array(	'devicetype'	=> 'cisconxos' ,
					'detect'	=> '/([\w\-]+)(\/.*)?[#>]\s*$/' ,
					'match'		=> '/(.*)%s.*(>|#)([^ \n\r^M]+)/'
					)
/**/
			);

		if (empty($DATA))
		{
			$this->data = array();
		}elseif(is_object($DATA)){
			if (!isset($DATA->data['hostname'])) { $DATA->data['hostname'] = $DATA->data['ip'];	}
			if (!isset($DATA->data['username'])) { $DATA->data['username'] = TACACS_USER;		}
			if (!isset($DATA->data['password'])) { $DATA->data['password'] = TACACS_PASS;		}
			$this->data = $DATA;
		}elseif(is_array($DATA)){
			if (!isset($DATA['hostname'])) { $DATA['hostname'] = $DATA['ip']; }
			if (!isset($DATA['username'])) { $DATA['username'] = TACACS_USER; }
			if (!isset($DATA['password'])) { $DATA['password'] = TACACS_PASS; }
			$this->data = $DATA;
		}elseif(is_numeric($DATA)){
			$INFOBJECT = Information::retrieve($DATA);
			$this->data['hostname'] = $INFOBJECT->data['ip'];
			$this->data['protocol'] = $INFOBJECT->data['protocol'];
			$this->data['username'] = TACACS_USER;
			$this->data['password'] = TACACS_PASS;
			unset($INFOBJECT); // Clear out our objects to save on memory
		}elseif(is_string($DATA)){
			$this->data['hostname'] = $DATA;
			$this->data['username'] = TACACS_USER;
			$this->data['password'] = TACACS_PASS;
		}
	}

	public function getcli($SERVICE = "")
	{
		$SERVICES = array("ssh2","ssh1","telnet");	// Define our valid services

		// If they dont give us a service in our array...
		if (!in_array($SERVICE,$SERVICES))
		{
			// If we have a protocol in the database, try that first!
			if (in_array($this->data['protocol'],$SERVICES))
			{
				$CLI = $this->getcli($this->data['protocol']);
				if ($CLI->connected) { return $CLI; }
			}

			// If we can connect to port 22 lets start checking SSH!
			if	($this->tcpprobe($this->data['hostname'],22,1))
			{
				$CLI = $this->getcli("ssh2");
				if ($CLI->connected) { return $CLI; }

				$CLI = $this->getcli("ssh1");
				if ($CLI->connected) { return $CLI; }
			}

			// Otherwise if we can fall back to telnet and try...
			if($this->tcpprobe($this->data['hostname'],23,1))
			{
				$CLI = $this->getcli("telnet");
				if ($CLI->connected) { return $CLI; }
			}

			// But if that doesnt work just give up.
			return 0;
		}

		$NEWCLASS = "command_" . $SERVICE;
		include_once("command/command_" . $SERVICE . ".class.php");
		if(class_exists($NEWCLASS))
		{
			if(is_subclass_of($NEWCLASS, 'command'))
			{
				$OBJECT = new $NEWCLASS($this->data);
			}else{
				print "ERROR, $NEWCLASS is not a subclass of command\n";
			}
		}else{
				print "ERROR, $newclass is not a valid class\n";
		}

		$OBJECT->connect();
		$OBJECT->service = $SERVICE;
		return $OBJECT;
	}

	protected function findprompt()
	{
//print "<pre>entering find prompt()</pre>\n"; john_flush;
		if (!$this->connected)	{ return 0; }
		if (!$this->patterns)	{ return 0; }
		$this->settimeout(5);

		// If we cant find the prompt in 5 calls to read, give up.
		$try = 0;
		while ($try++ < 5)
		{
//print "<pre>looking for prompt try $try</pre>\n"; john_flush;
			$DATA = $this->read("/.*[>|#]/");
			$LINES = explode("\n", $DATA);
			foreach ($LINES as $LINE)
			{
				foreach ($this->patterns as $PATTERN)
				{
					if (preg_match($PATTERN['detect'],$LINE,$MATCH))	// Test to see if its a standard cisco IOS prompt
					{
						$this->prompt = $MATCH[1];		// I think we found the prompt.
						$this->pattern = $PATTERN;		// Use this pattern for matching
/*
print "<pre>I think I found a prompt $this->prompt</pre>\n"; john_flush;
print "<pre>Escaped prompt is:" . preg_quote($this->prompt,"/") . "</pre>\n"; john_flush;
print "<pre>Testing to see if $this->prompt is really a prompt</pre>\n"; john_flush;
/**/
						$this->write("\n");				// So lets send a new line and check.
						if ( $this->read( sprintf( $this->pattern['match'],preg_quote($this->prompt,"/") ) ) )
						{
/*
print "<pre>Success! I believe $this->prompt is really a prompt!</pre>\n"; john_flush;
print "<pre>Escaped prompt is:" . sprintf($this->pattern['match'],preg_quote($this->prompt,"/")) . "</pre>\n"; john_flush;
dumper($this->pattern); john_flush();
/**/
							return $this->prompt;
						}
					}
				}
			}
		}
		return 0;
	}

	public function exec($command, $maxtries = 4)
	{
		if (!$this->connected)	{ return; }
		if (!$this->prompt)		{ return; }

		$this->settimeout(15);
		$this->write($command . "\n");
		$DELIMITER = sprintf($this->pattern['match'],preg_quote($this->prompt,"/"));
		$OUTPUT = "";	$TRIES = 0;
		while(!preg_match($DELIMITER,$OUTPUT,$REG) && $TRIES++ <= $maxtries)
		{
			$OUTPUT .= $this->read($DELIMITER);
		}
		return $OUTPUT;
	}

	protected function tcpprobe($host,$port,$timeout = 1)
	{
		if ( false == ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) { return false; }
		if ( false == (socket_set_nonblock($socket))) { return 0; }
		$time = time();
		while (!@socket_connect($socket, $host, $port))
		{
			$err = socket_last_error($socket);
			if ($err == 115 || $err == 114)
			{
				if ((time() - $time) >= $timeout)
				{
					socket_close($socket);
					return false;
				}
				sleep(1);
				continue;
			}
			return false;
		}
		socket_close($socket);
		return true;
	}
/**/

// END OF OBJECT
}

?>
