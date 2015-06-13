<?php

/**
 * include/libjohn.inc.php
 *
 * Common library of use(less/ful) functions, likely "borrowed" from various posters on the internet
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

// This generates a nosiy error and log if the permission check fails.
function PERMISSION_REQUIRE($PERMISSION)
{
	if (!PERMISSION_CHECK($PERMISSION))
	{
		$MESSAGE = "UNAUTHORIZED ACCESS BY {$_SESSION["AAA"]["username"]} PERMISSION {$PERMISSION}";
		global $DB;
		$DB->log($MESSAGE);
		trigger_error($MESSAGE);
		print "Error: Your user session lacks the permission '{$PERMISSION}' required to perform this action.<br>\n";
		global $HTML;
		die($HTML->footer());
	}
}

// This returns true of ["AAA"]["permission"]["$PERMISSION"] --OR-- ["AAA"]["permission"]["WILD*CARD"] matches permission!
function PERMISSION_CHECK($PERMISSION)
{
	if (isset($_SESSION["AAA"]["permission"][$PERMISSION])) { return $_SESSION["AAA"]["permission"][$PERMISSION]; }
	// If a simple match fails, try the wildcard match against every permission element!
	foreach ($_SESSION["AAA"]["permission"] as $KEY => $VALUE)
	{
		$MATCH = "/$KEY/i";
		if (preg_match($MATCH,$PERMISSION,$REG)) { return $VALUE; }else{
			if ($_SESSION["DEBUG"] > 8)
			{
				print "<pre>$MATCH does not match $PERMISSION</pre>\n";
			}
		}
	}
	return false;
}

function is_assoc($var)
{
        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
}

function cisco_find_management_interface($CONFIG)
{
	if (!is_array($CONFIG))
	{
//		print "CONFIG IS NOT AN ARRAY, FIXING!\n";
		$CONFIG = preg_split( '/\r\n|\r|\n/', $CONFIG );
	}

	$POSSIBLE = array();
	if (!is_assoc($CONFIG))
	{
		foreach ($CONFIG as $LINE)
		{
			$LINE = trim($LINE);
			if ( preg_match('/.*source-interface.* (\S+)/',$LINE,$REG) )
			{
				if (isset($POSSIBLE[$REG[1]])) { $POSSIBLE[$REG[1]]++; }else{ $POSSIBLE[$REG[1]] = 1; }
			}
		}
	}else if (is_assoc($CONFIG))
	{
		foreach ($CONFIG as $LINE => $VALUE)
		{
			$LINE = trim($LINE);
			if ( preg_match('/.*source-interface (\S+)/',$LINE,$REG) )
			{
				if (isset($POSSIBLE[$REG[1]])) { $POSSIBLE[$REG[1]]++; }else{ $POSSIBLE[$REG[1]] = 1; }
			}
		}
	}
	arsort($POSSIBLE);
	foreach ($POSSIBLE as $MGMT_INT => $HITCOUNT)
	{
		return $MGMT_INT;	// Hack, find the first KEY of the newly sorted array!
	}
}

function cisco_check_ios_version($model, $version)
{
        $color = "red";

        if((preg_match('/.*WS-C2350.*/',$model,$reg1))  && (preg_match('/.*lanlitek9-mz.122-52.SE.*/',$version,$reg2)))         { $color = "green"; }
        if((preg_match('/.*WS-C2360.*/',$model,$reg1))  && (preg_match('/.*lanlitek9-mz.122-52.SE.*/',$version,$reg2)))         { $color = "green"; }
        if((preg_match('/.*WS-C3560.*/',$model,$reg1))  && (preg_match('/.*ipservicesk9-mz.122-55.SE.*/',$version,$reg2)))      { $color = "green"; }
        if((preg_match('/.*WS-C3750.*/',$model,$reg1))  && (preg_match('/.*ipservicesk9-mz.122-55.SE.*/',$version,$reg2)))      { $color = "green"; }
        if((preg_match('/.*WS-C45.*/',$model,$reg1))    && (preg_match('/.*cat4500e-universalk9.SPA.03.01.01.SG.150-1.*/',$version,$reg2))) { $color = "green"; }
        if((preg_match('/.*WS-C4948.*/',$model,$reg1))  && (preg_match('/.*cat4500-entservicesk9-mz.122-54.SG1.*/',$version,$reg2)))    { $color = "green"; }
        if((preg_match('/.*WS-C650.*/',$model,$reg1))   && (preg_match('/.*adv.*k9.*.122-33.SXI.*/',$version,$reg2)))           { $color = "green"; }
        if((preg_match('/.*ME-C6524.*/',$model,$reg1))  && (preg_match('/.*adv.*k9.*.122-33.SXI.*/',$version,$reg2)))           { $color = "green"; }
        if((preg_match('/.*CISCO760.*/',$model,$reg1))  && (preg_match('/.*adventerprisek9-mz.151-1.S.*/',$version,$reg2)))     { $color = "green"; }
        if((preg_match('/.*ASR100.*/',$model,$reg1))    && (preg_match('/.*adventerprisek9.03.02.02.S.151-1.S2.*/',$version,$reg2)))    { $color = "green"; }
        if((preg_match('/.*ASA55.*/',$model,$reg1))     && (preg_match('/.*asa82.-k8.*/',$version,$reg2)))			{ $color = "green"; }
        if((preg_match('/.*CISCO29.*/',$model,$reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/',$version,$reg2)))		{ $color = "green"; }
        if((preg_match('/.*CISCO28.*/',$model,$reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/',$version,$reg2)))		{ $color = "green"; }
        if((preg_match('/.*CISCO38.*/',$model,$reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/',$version,$reg2)))		{ $color = "green"; }
        if((preg_match('/.*CISCO39.*/',$model,$reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/',$version,$reg2)))		{ $color = "green"; }
        if((preg_match('/.*CISCO720.*/',$model,$reg1))  && (preg_match('/.*adv.*k9-mz.151-4.M.*/',$version,$reg2)))		{ $color = "green"; }
        if((preg_match('/^720.*/',$model,$reg1))	&& (preg_match('/.*adv.*k9-mz.151-4.M.*/',$version,$reg2)))		{ $color = "green"; }

        return $color;
}

function cisco_check_ipv6($model, $version)
{
        $good_model = "red";
        $good_version = cisco_check_ios_version($model, $version);

	$good_model = (preg_match('/.*WS-C2350.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C2360.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C2960.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C3560.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C3750.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C4948E.*/',$model,$reg1)) ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C4510.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*WS-C650.*/',$model,$reg1))   ? "green" : $good_model;
	$good_model = (preg_match('/.*ME-C6524.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*CISCO760.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*ASR100.*/',$model,$reg1))    ? "green" : $good_model;
	$good_model = (preg_match('/.*CISCO29.*/',$model,$reg1))   ? "green" : $good_model;
	$good_model = (preg_match('/.*CISCO39.*/',$model,$reg1))   ? "green" : $good_model;

	$good_model = (preg_match('/.*ASA55.*/',$model,$reg1))     ? "yellow" : $good_model;
	$good_model = (preg_match('/.*CISCO720.*/',$model,$reg1))  ? "yellow" : $good_model;
	$good_model = (preg_match('/^720.*/',$model,$reg1)) 	   ? "yellow" : $good_model;
	$good_model = (preg_match('/.*CISCO28.*/',$model,$reg1))   ? "yellow" : $good_model;
	$good_model = (preg_match('/.*CISCO38.*/',$model,$reg1))   ? "yellow" : $good_model;

	if ($good_model == "red")
	{
		return "No, Hardware";
	}

	if ($good_model == "yellow")
	{
		return "Limited, Hardware";
	}

	if ($good_version != "green")
	{
		return "Limited, Software";
	}

	return "OK";

}

function cisco_check_mpls($model, $version)
{
	$good_model = "red";

	$good_model = (preg_match('/.*WS-C650.*/',$model,$reg1))  ? "green" : $good_model;
	$good_model = (preg_match('/.*CISCO760.*/',$model,$reg1)) ? "green" : $good_model;
	$good_model = (preg_match('/.*ASR100.*/',$model,$reg1))   ? "green" : $good_model;
	$good_model = (preg_match('/.*CISCO720.*/',$model,$reg1))   ? "green" : $good_model;
	$good_model = (preg_match('/^720.*/',$model,$reg1))   ? "green" : $good_model;

	return $good_model;
}

function cisco_check_input_error($command_output)
{
	$outputlines = explode("\r\n",$command_output);
	foreach ($outputlines as $line)
	{
		if (preg_match('/^%.*/',$line,$reg))			{ return 0; }
		if (preg_match('/^ERROR.*/',$line,$reg))		{ return 0; }
		if (preg_match('/^Type help.*/',$line,$reg))		{ return 0; }
		if (preg_match('/^Unknown command.*/',$line,$reg))	{ return 0; }
	}
	return 1;
}


function inventory_to_model($show_inventory)
{
	$model = "Unknown";
	$invlines = explode("\r\n",$show_inventory);
	foreach ($invlines as $line)
	{
		// LEGACY PERL CODE: $x =~ /^\s*PID:\s(\S+).*SN:\s+(\S+)\s*$/;
		if (preg_match('/.*PID:\s(\S+)\s.*/',$line,$reg))
		{
			$model = $reg[1];
			return $model;
		}
		// Aruba WLC's:
		// SC Model#                 	: Aruba7030-US
		$REGEX = "/^SC Model.*: (.+)$/";
		if ( preg_match($REGEX,$line,$REG) )
		{
			$model = $reg[1];
			return $model;
		}
	}
	return $model;
}

function version_to_model($show_version)
{
	$model = "Unknown";
	$verlines  = explode("\r\n",$show_version);
	foreach ($verlines as $line)
	{
####		print "### COMPARING: $line\n";
		if (preg_match('/.*isco\s+(WS-\S+)\s.*/',$line,$reg))
		{ $model = $reg[1]; return $model; }
		if (preg_match('/.*isco\s+(OS-\S+)\s.*/',$line,$reg))
		{ $model = $reg[1]; return $model; }
		if (preg_match('/.*ardware:\s+(\S+),.*/',$line,$reg))
		{ $model = $reg[1]; return $model; }
		if (preg_match('/.*ardware:\s+(\S+).*/',$line,$reg))
		{ $model = $reg[1]; return $model; }
		if (preg_match('/^cisco\s+(\S+)\s+.*/',$line,$reg))
		{ $model = $reg[1]; return $model; }
	}
	return $model;
}

function inventory_to_serial($show_inventory)
{
	$serial = "Unknown";
	$invlines = explode("\r\n",$show_inventory);
	foreach ($invlines as $line)
	{
		// LEGACY PERL CODE: $x =~ /^\s*PID:\s(\S+).*SN:\s+(\S+)\s*$/;
		if (preg_match('/.*PID:\s(\S+).*SN:\s+(\S+)\s*$/',$line,$reg))
		{
			$serial = $reg[2];
			return $serial;
		}
	}
	return $serial;
}

function testarray($list_ip,$list_port,$timeout = 1,$debug = 0)
{
        $return = "";
        $img_green = "<img src=\"/images/green_status.jpg\">";
        $img_red   = "<img src=\"/images/red_status.jpg\">";

        $list_status = array();
        $return .= "<font size=\"2\">\n";
        foreach ($list_ip as $ip)
        {
                if ($debug) { print "Testing $ip"; }
                foreach ($list_port as $port)
                {
                        if (testconnect($ip,$port,$timeout)) { $list_status[$ip][$port] = $img_green; }else{ $list_status[$ip][$port] = $img_red; }
                        if ($debug) { print " $port"; }
                }
                if ($debug) { print "<br>\n"; }
        }
        $return .= "</font>\n";
        $return .= "<table><tr><th>IP / Port</th>";
        foreach ($list_port as $port) { $return .= "<td>$port</td>"; }
        $return .= "</tr>\n";
        foreach ($list_ip as $ip)
        {
                $return .= "<tr><td>$ip</td>";
                foreach ($list_port as $port)
                {
                        $return .= "<td>".$list_status[$ip][$port]."</td>";
                }
                $return .= "</tr>\n";
        }
        $return .= "</table>\n";
        return $return;
}

function testconnect($host,$port,$timeout = 1)
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

function john_flush ()
{
	if (php_sapi_name() != "cli") // DONT FLUSH THE FUCKING CLI!
	{
//		echo(str_repeat(' ',256));
		if (ob_get_length())
		{
			@ob_flush();
			@flush();
			@ob_end_flush();
		}
		@ob_start();
	}
}

/*************************
* Input Slash Stripping  *
*************************/
function strip($a)  { if ( get_magic_quotes_gpc() ) { return stripslashes($a); } else { return $a; } }
function escape($a) { if ( get_magic_quotes_gpc() ) { return addslashes($a); } else { return $a; } }

/*********************
* Ryan's Var Dumper  *
*********************/
function dumper($var)
{
   print "<pre>\n";
   print_r($var);
   print "</pre><br>\n";
}

function dumper_to_string($var)
{
	ob_start();
	dumper($var);
	$result = ob_get_clean();
	return $result;
}

function dBug_to_string($Debug)
{
	ob_start();
	new dBug($Debug);
	$result = ob_get_clean();
	return $result;
}

function cisco_parse_nested_list_to_array($CONFIG)
{
	$RETURN = array();

	$RETURN = cisco_filter_config($CONFIG);					// Filter our config to strip out unimportant bits

	$RETURN = parse_nested_list_to_array($RETURN);			// Parse the filtered config to an array

	return $RETURN;											// And return it
}

function parse_nested_list_to_array($LIST, $INDENTATION = " ")
{
	$RESULT = array();
	$PATH = array();

	$LINES = explode("\n",$LIST);

	foreach ($LINES as $LINE)
	{
		if ($LINE == "") { continue; print "Skipped blank line\n"; } // Skip blank lines, they dont need to be in our structure
		$DEPTH	= strlen($LINE) - strlen(ltrim($LINE));
		$LINE	= trim($LINE);
		// truncate path if needed
		while ($DEPTH < sizeof($PATH))
		{
			array_pop($PATH);
		}
		// keep label (at depth)
		$PATH[$DEPTH] = $LINE;
		// traverse path and add label to result
		$PARENT =& $RESULT;
		foreach ($PATH as $DEPTH => $KEY)
		{
			if (!isset($PARENT[$KEY]))
			{
				$PARENT[$LINE] = array();
				break;
			}
			$PARENT =& $PARENT[$KEY];
		}
	}
	$RESULT = recursive_remove_empty_array($RESULT);
//	ksort($RESULT);	// Sort our keys in the array for comparison ease // Do we really need this?
	return $RESULT;
}

function recursive_remove_empty_array($ARRAY)
{
	$RETURN = array();
	foreach($ARRAY as $KEY => $VALUE)
	{
		if (count($VALUE) == 0)
		{
			$RETURN[$KEY] = 1;
		}else{
			$RETURN[$KEY] = recursive_remove_empty_array($VALUE);
		}
	}
	return $RETURN;
}

function special_output($OUTPUT)
{
	$LOUD = 0;
	if ($LOUD) { print "$OUTPUT"; }
}

function cisco_filter_config($CONFIG)
{
	$LINES_IN = preg_split( '/\r\n|\r|\n/', $CONFIG );
	$LINES_OUT = array();
	$SKIP = "";
	$HOSTNAME = "";
	foreach($LINES_IN as $LINE)
	{
		// Filter out the BANNER MOTD lines
        if (preg_match("/banner \S+ (\S+)/",$LINE,$REG))   // If we encounter a banner motd or banner motd line
        {
            $SKIP = $REG[1];                  continue;     // Skip until we see this character
        }
        if ($SKIP != "" && trim($LINE) == $SKIP)            // If $SKIP is set AND we detect the end of our skip character
        {
            $SKIP = "";                       continue;     // Stop skipping and unset the character
        }
        if ($SKIP != "")                    { continue; }   // Skip until we stop skipping

		// Find the hostname to identify our prompt
		if (preg_match("/^hostname (\S+)/",$LINE,$REG)) { $HOSTNAME = $REG[1]; }
		// Filter out the prompt at the end if it exists
		if ($HOSTNAME != "" && preg_match("/^{$HOSTNAME}.+/",$LINE,$REG)) { continue; }

		// Ignore a bunch of unimportant often-changing lines that clutter up the config repository
		if (
			( trim($LINE) == ""										)	||	//	Ignore blank and whitespace-only lines
			( trim($LINE) == "exit"									)	||	//	Ignore exit lines (mostly provisioning lines)
			( preg_match('/.*no shut.*/'				,$LINE,$REG))	||	//	no shut/no shutdown lines from provisioning tool
			( preg_match('/.*no enable.*/'				,$LINE,$REG))	||	//	from provisioning tool
			( preg_match('/.*enable secret.*/'			,$LINE,$REG))	||	//	from provisioning tool
			( preg_match('/.*ip domain.lookup.*/'		,$LINE,$REG))	||	//	from provisioning tool
			( preg_match('/.*ip domain.name.*/'			,$LINE,$REG))	||	//	from provisioning tool
			( preg_match('/.*crypto key generate rsa.*/',$LINE,$REG))	||	//	from provisioning tool
			( preg_match('/.*log-adjacency-changes.*/'	,$LINE,$REG))	||	//	from provisioning tool
			( trim($LINE) == "aqm-register-fnf"						)	||	//	no idea where this comes from
			( trim($LINE) == "aaa session-id common"				)	||	//	from provisioning tool
			( trim($LINE) == "ip routing"							)	||	//	from provisioning tool
			( trim($LINE) == "cdp enable"							)	||	//	from provisioning tool
			( trim($LINE) == "no ip directed-broadcast"				)	||	//	from provisioning tool
			( trim($LINE) == "no service finger"					)	||	//	from provisioning tool
			( trim($LINE) == "no service udp-small-servers"			)	||	//	from provisioning tool
			( trim($LINE) == "no service tcp-small-servers"			)	||	//	from provisioning tool
			( trim($LINE) == "no service config"					)	||	//	from provisioning tool
			( trim($LINE) == "no clock timezone"					)	||	//	from provisionnig tool
//			( trim($LINE) == "end"									)	||	//	skip end, we dont need this yet
			( trim($LINE) == "<pre>" || trim($LINE) == "</pre>"		)	||	//	skip <PRE> and </PRE> output from html scrapes
			( substr(trim($LINE),0,1) == "!"						)	||	//	skip conf t lines
			( substr(trim($LINE),0,4) == "exit"						)	||	//	skip conf lines beginning with the word exit
			( preg_match('/.*config t.*/'				,$LINE,$REG))	||	//	skip show run
			( preg_match('/.*show run.*/'				,$LINE,$REG))	||	//	and show start
			( preg_match('/.*show startup.*/'			,$LINE,$REG))	||	//	show run config topper
			( preg_match('/^version .*/'				,$LINE,$REG))	||	//	version 12.4 configuration format
			( preg_match('/^boot-\S+-marker.*/'			,$LINE,$REG))	||	//	boot start and end markers
			( preg_match('/^Building configur.*/'		,$LINE,$REG))	||	//	ntp clock period in seconds is constantly changing
			( preg_match('/^ntp clock-period.*/'		,$LINE,$REG))	||	//	nvram config last messed up
			( preg_match('/^Current configuration.*/'	,$LINE,$REG))	||	//	current config size
			( preg_match('/.*NVRAM config last up.*/'	,$LINE,$REG))	||	//	nvram config last saved
			( preg_match('/.*uncompressed size*/'		,$LINE,$REG))	||	//	uncompressed config size
			( preg_match('/^!Time.*/'					,$LINE,$REG))		//	time comments
		   )
		{ continue; }

		// If we have UTC and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/.* UTC$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we have CST and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/.* CST$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we have CDT and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/. *CDT$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we find a control code like ^C replace it with ascii ^C
		$LINE = str_replace(chr(3),"^C",$LINE);

		// If we find the prompt, break out of this function, end of command output detected
		if (isset($DELIMITER) && preg_match($DELIMITER,$LINE,$REG))
		{
			break;
		}

		// If we find a line with a tacacs key in it, HIDE THE KEY!
		if ( preg_match('/(\s*server-private 10.252.12.10. timeout 2 key) .*/',$LINE,$REG) )
		{
			$LINE = $REG[1];	// Strip out the KEYS from a server-private line!
		}

		$LINE = rtrim($LINE);	// Trim whitespace off the right end!
		array_push($LINES_OUT, $LINE);
	}

	// REMOVE blank lines from the leading part of the array and REINDEX the array
	while ($LINES_OUT[0] == ""	&& count($LINES_OUT) > 2 ) { array_shift	($LINES_OUT); }

	// REMOVE blank lines from the end of the array and REINDEX the array
	while (end($LINES_OUT) == ""	&& count($LINES_OUT) > 2 ) { array_pop	($LINES_OUT); }

	// Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
	array_push($LINES_OUT, "");

	$CONFIG = implode("\n",$LINES_OUT);

	return $CONFIG;
}

function cisco_download_config($DEVICE)
{
	$TIME = time();
	$DESTINATION	= "/config/{$DEVICE['id']}.{$TIME}.config";
	$TFTPFILE		= "/tftpboot/config/{$DEVICE['id']}.{$TIME}.config";
	$DEVICE['name']	= preg_replace("/\//","-",$DEVICE['name']);	// Replace slashes in device names with hyphens!
	$CONFIGREPO		= BASEDIR."/config/{$DEVICE['name']}";
	// TRY SNMP TFTP config grab FIRST because its FAST(er) than CLI!
	$Config = new CiscoConfig($DEVICE["ip"],SNMP_RW);
	$Config->WriteNetwork($DESTINATION,"tftp",TFTPIP);
	if($Config->Error)
	{
		// If we hit an error, fall back to CLI!
		special_output(" SNMP error, trying Telnet/SSH:");
		$INFOBJECT = Information::retrieve($DEVICE["id"]);
		$INFOBJECT->scan();
		$SHOW_RUN = $INFOBJECT->data["run"];
		$DELIMITER = $INFOBJECT->data["pattern"];
		unset($INFOBJECT); // Save some memory
	}else{
		$SHOW_RUN = file_get_contents($TFTPFILE);
	}

	$RUNLEN = strlen($SHOW_RUN);
	if ($RUNLEN < 800)	// If we cant get the config at all, or length is less than 800 bytes, fuckit and die.
	{
		special_output(" config is $RUNLEN bytes, Could not get config!\n"); return 0;
	}else{
		special_output(" Got $RUNLEN bytes!");
	}

	$LINES_IN = preg_split( '/\r\n|\r|\n/', $SHOW_RUN );

	$LINES_OUT = array();
	foreach($LINES_IN as $LINE)
	{
		// Ignore a bunch of unimportant often-changing lines that clutter up the config repository
		if (
			(preg_match('/.*show run.*/'			,$LINE,$REG)) ||
			(preg_match('/.*show startup.*/'		,$LINE,$REG)) ||
			(preg_match('/^Building configur.*/'	,$LINE,$REG)) ||
			(preg_match('/^ntp clock-period.*/'		,$LINE,$REG)) ||
			(preg_match('/^Current configuration.*/',$LINE,$REG)) ||
			(preg_match('/.*NVRAM config last up.*/',$LINE,$REG)) ||
			(preg_match('/.*uncompressed size*/'	,$LINE,$REG)) ||
			(preg_match('/^!Time.*/'				,$LINE,$REG))
		   )
		{ continue; }

		// If we have UTC and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/.* UTC$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we have CST and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/.* CST$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we have CDT and its NOT the configuration last changed line, ignore it.
		if (
			(preg_match('/. *CDT$/'			,$LINE,$REG)) &&
			!(preg_match('/^.*onfig.*/'		,$LINE,$REG))
		   )
		{ continue; }

		// If we find a control code like ^C replace it with ascii ^C
		$LINE = str_replace(chr(3),"^C",$LINE);

		// If we find ": end", break out of this function, end of command output detected
		if (
			(preg_match('/^: end$/'			,$LINE,$REG))
		   )
		{ break; }

		// If we find the prompt, break out of this function, end of command output detected
		if (isset($DELIMITER) && preg_match($DELIMITER,$LINE,$REG))
		{
			break;
		}

		$LINE = rtrim($LINE);	// Trim whitespace off the right end!
		array_push($LINES_OUT, $LINE);
	}

	// REMOVE blank lines from the leading part of the array and REINDEX the array
	while ($LINES_OUT[0] == ""	&& count($LINES_OUT) > 2 ) { array_shift	($LINES_OUT); }

	// REMOVE blank lines from the end of the array and REINDEX the array
	while (end($LINES_OUT) == ""	&& count($LINES_OUT) > 2 ) { array_pop	($LINES_OUT); }

	// Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
	array_push($LINES_OUT, "");

	$SHOW_RUN = implode("\n",$LINES_OUT);
	$FH = fopen($CONFIGREPO,'w');
	fwrite($FH,$SHOW_RUN);
	fclose($FH);

	special_output(" Config Saved!\n");

	return 1;
}

// Find if a character $NEEDLE is in a string $HAYSTACK defaulting to case sensitive!
function in_string($needle, $haystack, $insensitive = false) {
    if ($insensitive) {
        return false !== stristr($haystack, $needle);
    } else {
        return false !== strpos($haystack, $needle);
    }
}

function preg_grep_keys($pattern, $input, $flags = 0)
{
    return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
}

function array_diff_assoc_recursive($array1, $array2) {
    $difference=array();
    foreach($array1 as $key => $value) {
        if( is_array($value) ) {
            if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                $difference[$key] = $value;
            } else {
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if( !empty($new_diff) )
                    $difference[$key] = $new_diff;
            }
        } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
            $difference[$key] = $value;
        }
    }
    return $difference;
}

function phrase_generator($WORDS)
{
	foreach ($WORDS as $KEY => $VALUE)
	{
		$WORDS[$KEY] = explode("\n",$WORDS[$KEY]);
		$WORDS[$KEY] = $WORDS[$KEY][ rand( 0 , count($WORDS[$KEY]) - 1 ) ];
	}
	return implode( " " , $WORDS );
}

function bofh_quote()
{
	$WORDS = array(
"Temporary
Intermittant
Partial
Redundant
Total
Multiplexed
Inherent
Duplicated
Dual-Homed
Synchronous
Bidirectional
Serial
Asynchronous
Multiple
Replicated
Non-Replicated
Unregistered
Non-Specific
Generic
Migrated
Localised
Resignalled
Dereferenced
Nullified
Aborted
Serious
Minor
Major
Extraneous
Illegal
Insufficient
Viral
Unsupported
Outmoded
Legacy
Permanent
Invalid
Deprecated
Virtual
Unreportable
Undetermined
Undiagnosable
Unfiltered
Static
Dynamic
Delayed
Immediate
Nonfatal
Fatal
Non-Valid
Unvalidated
Non-Static
Unreplicatable",
"Array
Systems
Hardware
Software
Firmware
Backplane
Logic-Subsystem
Integrity
Subsystem
Memory
Comms
Integrity
Checksum
Protocol
Parity
Bus
Timing
Synchronisation
Topology
Transmission
Reception
Stack
Framing
Code
Programming
Peripheral
Environmental
Loading
Operation
Parameter
Syntax
Initialisation
Execution
Resource
Encryption
Decryption
File
Precondition
Authentication
Paging
Swapfile
Service
Gateway
Request
Proxy
Media
Registry
Configuration
Metadata
Streaming
Retrieval
Installation
Library
Handler",
"Interruption
Destabilisation
Destruction
Desynchronisation
Failure
Dereferencing
Overflow
Underflow
NMI
Interrupt
Corruption
Anomoly
Seizure
Override
Reclock
Rejection
Invalidation
Halt
Exhaustion
Infection
Incompatibility
Timeout
Expiry
Unavailability
Bug
Condition
Crash
Dump
Crashdump
Stackdump
Problem
Lockout
Error
Problem
Warning
Signal"
	);
	return phrase_generator($WORDS);
}

function insult()
{
	$WORDS = array(
"lazy
stupid
insecure
idiotic
slimy
slutty
smelly
pompous
communist
dicknose
racist
elitist
white trash
drug-loving
butterface
tone-deaf
ugly
creepy",
"douche
ass
turd
rectum
butt
cock
shit
crotch
bitch
turd
prick
slut
taint
fuck
dick
boner
shart
nut
sphincter",
"pilot
canoe
captain
pirate
hammer
knob
box
cock-wrench
jockey
nazi
waffle
goblin
blossom
biscuit
clown
socket
monster
hound
dragon
balloon"
	);
	return phrase_generator($WORDS);
}

function futurama_quote()
{
	$JSONFILENAME = BASEDIR."/archive/bender/futurama.json";
	$QUOTES = json_decode( file_get_contents($JSONFILENAME) );
	$QUOTE = implode("\n",$QUOTES[ rand( 0 , count($QUOTES) - 1 ) ]);
	return $QUOTE;
}

?>
