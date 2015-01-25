<?php

/**
 * include/cisco.class.php
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
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

/**
 * class Cisco
 *
 * The Cisco class is capable of storing, parsing, and generating associative containers of useful information
 * from the text output of a Cisco devices command line interface.
 *
 * @category  default
 * @package   none
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2011 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */
class Cisco
{
   private $iface;
   private $config;
   private $data;
   private $device;
   private $abbr = array(
      'Eth'=> 'Ethernet',
      'Fa' => 'FastEthernet',
      'Gi' => 'GigabitEthernet',
      'Te' => 'TenGigabitEthernet',
      'Lo' => 'Loopback',
      'Po' => 'Port-channel'
   );

   public function abbreviate($interface)
   {
      $patterns = array('/loopback/i','/tengigabitethernet/i','/gigabitethernet/i','/gig /i',
                        '/port-channel/i','/fastethernet/i','/ethernet/i');
      $abbrev   = array('Lo','Te','Gi','Gi','Po','Fa','Eth');
      return preg_replace($patterns,$abbrev,$interface);
   }

	public function dnsabbreviate($INTERFACE)
	{
		$INTERFACE = strtolower($INTERFACE);
		$INTERFACE = preg_replace("/gigabitethernet/","gig",$INTERFACE);
		$INTERFACE = preg_replace("/gigabiteth/"	,"gig",$INTERFACE);
		$INTERFACE = preg_replace("/gi\//","gig\/"	,$INTERFACE);
		$INTERFACE = preg_replace("/fastethernet/"	,"fa",$INTERFACE);
		$INTERFACE = preg_replace("/port\-channel/"	,"po",$INTERFACE);
		$INTERFACE = preg_replace("/loopback/"		,"lo",$INTERFACE);
		$INTERFACE = preg_replace("/ethernet/"		,"eth",$INTERFACE);
		$INTERFACE = preg_replace("/tunnel/"		,"tun",$INTERFACE);
		$INTERFACE = preg_replace("/bvi/"			,"bvi",$INTERFACE);
		$INTERFACE = preg_replace("/atm/"			,"atm",$INTERFACE);
		$INTERFACE = preg_replace("/serial/"		,"se",$INTERFACE);
		$INTERFACE = preg_replace("/dialer/"		,"di",$INTERFACE);
		$INTERFACE = preg_replace("/multilink/"		,"mu",$INTERFACE);
		$INTERFACE = preg_replace("/\//"			,"-",$INTERFACE);
		$INTERFACE = preg_replace("/\./"			,"-",$INTERFACE);
		$INTERFACE = preg_replace("/:/"				,"-",$INTERFACE);
		return $INTERFACE;
	}

   public function debreviate($value) {
      if (!isset($value)) { return; }
      return $this->abbr[$value];
   }

   public function unabbreviate($interface)
   {
      preg_match('/^([A-Za-z]+)(.*)\s*$/',$interface,$match);
      $abbriname = $this->debreviate($match[1]);
      $fulliname = ($abbriname) ? $abbriname.$match[2] : $interface;
      return $fulliname;
   }

   public function dnsinterface($value) {
      if (!isset($value)) { return; }

   }
 
   public function data($host,$key,$value = NULL) {
      if (!isset($host) || !isset($key)) { return; }
      if (is_null($value)) { return $this->data[$host][$key]; }
      else { $this->data[$host][$key] = $value; }
   }

   public function device($host,$key,$value = NULL) {
      if (!isset($host) || !isset($key)) { return; }
      if (is_null($value)) { return $this->device[$host][$key]; }
      else { $this->device[$host][$key] = $value; }
   }

   public function config($key,$value = NULL) {
      if (!isset($key)) { return; }
      if (is_null($value)) { return $this->config[$key]; }
      else { $this->config[$key] = $value; }
   }

   public function iface($key,$value = NULL) {
      if (!isset($key)) { return; }
      if (is_null($value)) { return $this->iface[$key]; }
      else { $this->iface[$key] = $value; }
   }

   public function parse_config_interface($iname) {
      if (!$iname) { return; }
      $build = array();
      $idata = $this->config['interface'][$iname];

      foreach ($idata as $setting) {
         if (preg_match('/^switchport mode (\S+)/',$setting,$match)) { 
            $this->iface[$iname]['mode'] = $match[1];
         }
         else if (preg_match('/^switchport access vlan (\d+)/',$setting,$match)) { 
            $this->iface[$iname]['vlan.access'] = $match[1];
         }
         else if (preg_match('/^switchport trunk allowed vlan (?:add)?(.*)\s*$/',$setting,$match)) {
            $vlans = explode(',',$match[1]);
            if (!isset($this->iface[$iname]['vlan.trunk'])) { 
               $this->iface[$iname]['vlan.trunk'] = array(); 
            }
            $this->iface[$iname]['vlan.trunk'] = 
               array_merge($this->iface[$iname]['vlan.trunk'],(array)$vlans);
         }
         else if (preg_match('/^description (.*)\s*$/',$setting,$match)) {
            $this->iface[$iname]['desc'] = $match[1];
         }
         else if (preg_match('/^shutdown\s*$/',$setting,$match)) {
            $this->iface[$iname]['shut'] = 1;
         }
//TODO this needs to be turned into an array for stupid interfaces with more than one IP address.
         if (preg_match('/^ip address (\S+) (\S+)/',$setting,$match)) {		// IOS
            $this->iface[$iname]['ipv4addr'] = $match[1];
            $this->iface[$iname]['ipv4mask'] = $match[2];
         }
         if (preg_match('/^ipv4 address (\S+) (\S+)/',$setting,$match)) {	// IOS-XR
            $this->iface[$iname]['ipv4addr'] = $match[1];
            $this->iface[$iname]['ipv4mask'] = $match[2];
         }
         if (preg_match('/^ip address (\S+)\/(\S+)/',$setting,$match)) {	// NXOS
            $this->iface[$iname]['ipv4addr'] = $match[1];
         }
      }

      if (($this->iface[$iname]['mode'] == 'trunk') && (isset($this->iface[$iname]['vlan.trunk']))) {
         $this->iface[$iname]['vlan'] = implode(',',$this->iface[$iname]['vlan.trunk']);
      }
      else { $this->iface[$iname]['vlan'] = $this->iface[$iname]['vlan.access']; }

      ### initialize status
      $this->iface[$iname]['status'] = 'unknown'; 
   }

   public function parse_config($info) {
      if (!$info) { return; }
      foreach ($info as $line) {
         if (preg_match('/^interface\s+(\S+)/i',$line,$match)) { 
            $current = $match[1]; 
            $this->config['interface'][$current] = array();
         }
         else if (preg_match('/^!\s*$/',$line,$match)) {
            unset($current); 
         }
         else if (isset($current)) {
            $modline = preg_replace('/^\s*(.*?)\s*$/','$1',$line);
            array_push($this->config['interface'][$current],$modline);
         }
      } 

      foreach ($this->config['interface'] as $iname => $idata) {
         $this->parse_config_interface($iname); 
      }
   }

   public function parse_version($info) {
      if (!$info) { return; }
      foreach ($info as $line) {
         if (preg_match('/^interface\s+(\S+)/i',$line,$match)) {
            $current = $match[1];
            $this->config['interface'][$current] = array();
         }
      }
   }

   public function parse_show_interface($info) {
      if (!$info) { return; }
      foreach ($info as $line) {
         if (preg_match('/^\s*5 minute \S+ rate (\d+) (\S+)\//',$line,$match)) {
            $bits_bytes = $match[2];
            $rate = ($bits_bytes == "bytes") ? ($match[1] * 8) : $match[1];
            $return['rate'] += $rate;
         }
      }
      return $return;
   }

   public function parse_show_ip_interface_brief($info) {
      if (!$info) { return; }
      foreach ($info as $line) {
         if (preg_match('/^(\S+)\s+([\d\.]+)\s+/',$line,$match)) {
            $int = $match[1];
            $ip  = $match[2];
            $return[$int]['ip'] = $ip;
         }
      }
      return $return;
   }

/* DO NOT USE THIS FUNCTION! *\
   public function parse_interface_status($info) {
      foreach ($info as $line) {
            if (!$status) { $status = "unknown"; }

#            print "$int, $name, $status, $vlan, $duplex, $speed\n";

            $int = preg_replace($abbrname,$fullname,$int);

            $this->iface[$int]['status'] = $status;
         }
      }
   }
/**/

   public function parse_interface_status($input) {
      if (!$input) { return; }

      $data = $this->parse_space_delimited_command($input,$index);

      foreach ($data as $interface) {
         $int    = $this->unabbreviate($interface['port']);

//       $name   = $interface['name']; // This is pulled from the config. dont use it.
//       $vlan   = $interface['vlan']; // This is pulled from the config too... we override it here.

         $this->iface[$int]['status'] = $interface['status'];
         $this->iface[$int]['duplex'] = $interface['duplex'];
         $this->iface[$int]['speed']  = $interface['speed'];
         $this->iface[$int]['type']   = $interface['type'];
      }
   }

   public function parse_show_interface_status($input, $index = null)
   {
      $data = $this->parse_space_delimited_command($input,$index);
      $return = array();
      foreach ($data as $int => $info) { $return[$this->abbreviate($int)] = $info; }
      return $return;
   }

   public function parse_space_delimited_command($input,$index = null)
   {
      ### Build array from lines of data.
      ###================================
      $list = explode("\n",$input);
 
      ### Remove the first line since it only contains the command for showing output.
      ###=============================================================================
      array_shift($list);
 
      ### Extract the next line of the array and use as the header.
      ### Skip any lines that start with a 'dash' (assumed text formatting)
      ### or lines that are completely blank.
      ###==================================================================
      while (preg_match('/^[\-\s]*$/',$list[0]) && isset($list[0])) {  $foo = array_shift($list); }
      $header = array_shift($list);

      ### CISCO BUG FIX (show interface status):
      ### On most IOS devices the Speed header does not left justify align with the
      ### listed speed.  We need to detect this and replace the proper string.
      ###==========================================================================

      if (preg_match('/Duplex  Speed Type.*$/',$header)) {
		$header = preg_replace("/\ Speed/","Speed",$header);
      }

      list($pattern,$patternmap) = $this->build_header_pattern($header);

      ### If no index was provided, it's safe to assume that the first value should
      ### be the index for the data.
      ###==========================================================================
      if (!$index) { $index = $patternmap[1]; }

      ### Begin pattern matching against the input data.
      ### Skip all lines that are just dashs/whitespace.
      ###===============================================

      foreach ($list as $line) {
         if (preg_match('/^[\-\s]*$/',$line)) { continue; }
         if (preg_match($pattern,$line,$match)) {
            foreach ($patternmap as $pos => $name) {
               $results[$name] = trim($match[$pos]);
            }
            $return[$results[$index]] = $results;
         }
      }
 
      return $return;
   }
 
   public function build_header_pattern($header)
   {
      ### Split the fields on whitespace.
      ###================================
      $fieldlist = preg_split('/\s+/',$header);
 
      ### Build our list of fields, excluding any fields that were blank.
      ###================================================================
      $fields = array();
      foreach ($fieldlist as $field) {
         if ($field) { $fields[] = $field; }
      }
 
      ### Loop through the fields and determine what position they occur in the header.
      ### Find the length of each field by subtracting the last position from the current.
      ###=================================================================================
      $count = 0;
      foreach ($fields as $field) {
         if (!$field) { continue; }
 
         $info[$count]['position'] = strpos($header,$field);
         $info[$count]['name']     = $field;
 
         $prev = $info[$count - 1];
 
         $length[$prev['name']] = $info[$count]['position'] - $prev['position'];
 
         $count++;
      }
 
      ### The first value we assigned was irrelevent, so drop it.
      ###========================================================
      array_shift($length);
 
      ### We need to add the last value to our list, since it has an to end-of-line length
      $length[$fields[count($fields)-1]] = "*";
 
      ### Assemble the values from the length array and build a match string
      ### to feed to a preg_match command.
      ###===================================================================
      $pattern = "/^";
 
      $count = 1;
      foreach ($length as $name => $value) {
         $patternmap[$count] = strtolower($name);
         $pattern .= (($value == '*') ? "(.*)" : "(.{".$value."})");
         $count++;
      }
      $pattern .= "\$/";

      return array($pattern,$patternmap);
   }
 
   public function parse_show_etherchannel_detail($input)
   {
      $return = array();
 
      if (!$input) { return $return; }
 
      $group = "";
 
      foreach (explode("\n",$input) as $line) {
         if (preg_match('/^Group:\s+(\d+)\s*$/',$line,$match)) {
            $group = $match[1];
         }
         else if (preg_match('/^Port:\s+(\S+)\s*$/',$line,$match)) {
            $port = $match[1];
            $return["Port-channel".$group]['members'][] = $port;
         }
      }
      return $return;
   }

   public function parse_mac_address_table($data)
   {
      foreach ($data as $line) {
/* *\
NORMAL show mac! |  20    0027.0d35.1a08    DYNAMIC     Gi0/49
6500 show mac!   |*  194  0021.281a.d7fa   dynamic  Yes          0   Gi4/10
nxos show mac!   |* 1        5cf3.fcef.a47e    dynamic   20         F    F  Po139
/**/
#        if (preg_match('/^\s*(\d+)\s+(\S+).*?(\S+)\s*$/',$line,$match))
         if (preg_match('/^\D*(\d+)\s+(\S+).*?(\S+)\s*$/',$line,$match))
         {
            list($all,$vlan,$mac,$iname) = $match;
            preg_match('/^([A-Za-z]+)(.*)\s*$/',$iname,$match);
            $abbriname = $this->debreviate($match[1]);
            $fulliname = ($abbriname) ? $abbriname.$match[2] : $iname;
            $return[$fulliname][$mac] = $vlan;
         }
    }
    return $return;
  }

}

?>
