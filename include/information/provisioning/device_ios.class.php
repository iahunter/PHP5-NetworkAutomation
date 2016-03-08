<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
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

require_once "information/provisioning/device.class.php";

class Provisioning_Device_IOS	extends Provisioning_Device
{
	public $type = "Provisioning_Device_IOS";

	public function config_logging()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
service timestamps debug datetime msec
service timestamps log datetime msec
logging buffered 1000000 informational
no logging console
logging host 10.0.192.130
";
		if ($this->data['mgmtvrf'] != "")
		{
			$OUTPUT .= "logging source-interface {$this->data['mgmtint']} vrf {$this->data['mgmtvrf']}";
		}else{
			$OUTPUT .= "logging source-interface {$this->data['mgmtint']}";
		}
$OUTPUT .= "
line vty 0 4
  logging synchronous
 exit
line vty 5 15
  logging synchronous
 exit

archive
  log config
    logging enable
    logging size 200
    hidekeys
   exit
 exit

service password-encryption
no service finger
no service pad
no service udp-small-servers
no service tcp-small-servers
no service config
";
		if ($this->data['mgmtvrf'] != "")
		{
			$OUTPUT .= "
ntp server vrf {$this->data['mgmtvrf']} 10.123.1.123
ntp server vrf {$this->data['mgmtvrf']} 10.123.2.123
ntp server vrf {$this->data['mgmtvrf']} 10.123.3.123
";
		}else{
			$OUTPUT .= "
ntp server 10.123.1.123
ntp server 10.123.2.123
ntp server 10.123.3.123
";
		}
		$OUTPUT .= "
ntp source {$this->data['mgmtint']}
!Time zone should not be configured.
no clock timezone

";
		return $OUTPUT;
	}

	public function config_aaa()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= '
aaa new-model
username console privilege 15 secret 5 $1$Glk1$lol
username telecom privilege 15 secret 5 $1$4XCK$nope
enable secret 5 $1$fPpm$secret
';
		$OUTPUT .= "
no enable password
!
aaa group server tacacs+ AAA_GROUP_ADMIN
  server-private 10.252.40.75 timeout 3 key abc123lolol
  ip tacacs source-interface {$this->data['mgmtint']}
";
		if ($DEV_MGMTVRF != "") { $OUTPUT .= "ip vrf forwarding {$this->data['mgmtvrf']}"; }

		$OUTPUT .= "
!Authentication
aaa authentication login default local
aaa authentication login AAA_AUTH_ADMIN group AAA_GROUP_ADMIN local

!Authorization
aaa authorization exec default group AAA_GROUP_ADMIN local if-authenticated
aaa authorization commands 1 default group AAA_GROUP_ADMIN none
aaa authorization commands 15 default group AAA_GROUP_ADMIN none
!aaa authorization config-commands
aaa authorization network default none

!Accounting
aaa accounting exec default start-stop group AAA_GROUP_ADMIN
aaa accounting commands 1 default start-stop group AAA_GROUP_ADMIN
aaa accounting commands 15 default start-stop group AAA_GROUP_ADMIN
aaa accounting network default start-stop group AAA_GROUP_ADMIN
aaa accounting connection default start-stop group AAA_GROUP_ADMIN
aaa accounting system default start-stop group AAA_GROUP_ADMIN
no aaa accounting system guarantee-first

login on-failure log

ip access-list standard ACL_REMOTE_MGMT
  permit 10.0.0.0 0.255.255.255
  permit 172.16.0.0 0.15.255.255
  permit 192.168.0.0 0.0.255.255
 exit

!Line Config, Console
line con 0
  exec-timeout 60 0
  login authentication AAA_AUTH_ADMIN
  transport preferred none
  privilege level 15
!Line Config, VTY-SSH
line vty 0 4
  privilege level 15
  access-class ACL_REMOTE_MGMT in vrf-also
  exec-timeout 60 0
  login authentication AAA_AUTH_ADMIN
  transport input ssh
  transport preferred none
line vty 5 15
  privilege level 15
  access-class ACL_REMOTE_MGMT in vrf-also
  exec-timeout 60 0
  login authentication AAA_AUTH_ADMIN
  transport input ssh
  transport preferred none

service tcp-keepalives-in
service tcp-keepalives-out

!Cryptographic Features

no ip http server
no ip http secure-server

ip ssh version 2
ip scp server enable

ip domain-name net.company.com
crypto key generate rsa general modulus 1024

ip ftp source-interface {$this->data['mgmtint']}
ip tftp source-interface {$this->data['mgmtint']}

ip tcp path-mtu-discovery

";
		return $OUTPUT;
	}

	public function config_management()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_MGMTIP4    = $this->data['mgmtip4'];
		$DEV_MGMTGW     = $this->data['mgmtgw'];
		$DEV_MGMTINT    = $this->data['mgmtint'];
		$DEV_MGMTVRF    = $this->data['mgmtvrf'];

		$DEV_MGMTIP4_ADDR = Net_IPv4::parseAddress($DEV_MGMTIP4)->ip;
		$DEV_MGMTIP4_MASK = Net_IPv4::parseAddress($DEV_MGMTIP4)->netmask;

		if ($DEV_MGMTVRF != "")
		{
			$OUTPUT .= "
vrf definition $DEV_MGMTVRF
  address-family ipv4
 exit

interface $DEV_MGMTINT
  vrf forwarding $DEV_MGMTVRF
  ip address $DEV_MGMTIP4_ADDR $DEV_MGMTIP4_MASK
  no shut
 exit

ip route vrf $DEV_MGMTVRF 0.0.0.0 0.0.0.0 $DEV_MGMTGW
";
		}else{
			$OUTPUT .= "
interface $DEV_MGMTINT
  ip address $DEV_MGMTIP4_ADDR $DEV_MGMTIP4_MASK
  no shut
 exit

ip route 0.0.0.0 0.0.0.0 $DEV_MGMTGW

";
		}
		return $OUTPUT;
	}

	public function config_snmp()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$SITENAME = $this->parent()->data['name'];
		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= "
snmp-server location {$SITENAME}
snmp-server contact Network Operations
ip access-list standard ACL_SNMP_RW
  permit 10.123.0.0 0.0.255.255
 exit
ip access-list standard ACL_SNMP_RO
  permit 172.30.0.0 0.0.255.255
  permit 10.123.0.0 0.0.255.255
  permit 10.202.0.0 0.0.255.255
  permit 10.242.0.0 0.0.255.255
  permit 10.243.0.0 0.0.255.255
  permit 10.245.0.0 0.0.255.255
  permit 10.246.0.0 0.0.255.255
  permit 10.247.0.0 0.0.255.255
  permit 10.248.0.0 0.0.255.255
  exit
snmp-server community NetworkRO RO ACL_SNMP_RO
snmp-server community NetworkRW RW ACL_SNMP_RW
snmp-server trap-source {$this->data['mgmtint']}
snmp-server source-interface informs {$this->data['mgmtint']}
snmp-server enable traps config
snmp-server ifindex persist
! SNMP Trap reciever netman via lancope flow replicator
";
		if ($DEV_MGMTVRF != "")
		{
			$OUTPUT .= "snmp-server host 10.0.192.130 vrf {$this->data['mgmtvrf']} public\n";
		}else{
			$OUTPUT .= "snmp-server host 10.0.192.130 public\n";
		}
		$OUTPUT .= "\n";

		return $OUTPUT;
	}

	public function config_netflow()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= <<<END
ip flow-cache timeout active 1
ip flow-export source {$this->data['mgmtint']}
ip flow-export version 5
! Solarwinds collectors via netflow F5 VIP via lancope flow replicator
ip flow-export destination 10.0.192.130 2055
ip flow-top-talkers
  top 20
  sort-by bytes
 exit

END;
		return $OUTPUT;
	}

	public function config_ospf()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
ip routing
router ospf 1
  log-adjacency-changes
  router-id {$this->data['loopback4']}
  auto-cost reference-bandwidth 200000
  timers throttle spf 50 200 5000
  timers throttle lsa 50 200 5000

 exit
interface Loopback0
  ip ospf 1 area 0
 exit

";
		return $OUTPUT;
	}

	public function config_mpls()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
ip cef
mpls ldp router-id loop0
mpls label protocol ldp
mpls ip
no mpls ip propagate-ttl forwarded
mpls ldp igp sync holddown 2000
mpls traffic-eng tunnels
mpls traffic-eng path-selection metric igp
mpls ldp session protection

";
		return $OUTPUT;
	}

	public function config_multicast()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
ip multicast-routing
ip multicast multipath
ip pim ssm default

int loopback0
  ip pim sparse-mode
 exit

";
		return $OUTPUT;
	}

	public function config_bgp()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_BGPASN = $this->parent()->get_asn();

	$OUTPUT .= "
ip bgp-community new-format
router bgp $DEV_BGPASN
  bgp router-id {$this->data['loopback4']}
  bgp always-compare-med
  no bgp default ipv4-unicast
  bgp log-neighbor-changes
  bgp deterministic-med
 exit

";
		return $OUTPUT;
	}

	public function config_vrf($VPNID)
	{
		$VPN = $this->get_vpn_by_vpnid($VPNID);

		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		// Only do work if we find the VPN ID specified.
		if ($VPN)
		{
			$DEV_LOOP4  = $this->data['loopback4'];
			$DEV_BGPASN = $this->parent()->get_asn();
			$VRFNAME = "V".$VPN->data['vpnid'].":".$VPN->data['name'];

//TODO FIXME!
			// Only configure the VRF if it hasnt been configured!
//			if (!isset($this->data['configured'])) { $this->data['configured'] = array(); }
//			if (!isset($this->data['configured'][$VRFNAME])) { $this->data['configured'][$VRFNAME] = 0; }
			if (!$this->data['configured'][$VRFNAME])
			{
				$this->data['configured'][$VRFNAME]++;
				$OUTPUT .= "vrf definition $VRFNAME\n";
				$OUTPUT .= "  rd $DEV_LOOP4:$VPNID\n";
				$OUTPUT .= "  address-family ipv4\n";

//TODO FIXME! Add route targets to the stupid VPN information objects
		        $OUTPUT .= "    route-target import $DEV_BGPASN:{$VPN->data['vpnid']}\n";
		        $OUTPUT .= "    route-target export $DEV_BGPASN:{$VPN->data['vpnid']}\n";

/*				foreach (explode(" ",$VPN->data['routetargets']) as $VRF_RT)
				{
				        $OUTPUT .= "    route-target import $DEV_BGPASN:$VRF_RT\n";
				        $OUTPUT .= "    route-target export $DEV_BGPASN:$VRF_RT\n";
				}/**/
//				if ($VPNID >= 100 && $VPNID <= 199)
//				{
//					$OUTPUT .= "    maximum routes 10000 80\n";
//				}else{
//					$OUTPUT .= "    maximum routes 100 80\n";
//				}
		        $OUTPUT .= "    maximum routes 10000 80\n";

				$OUTPUT .= "   exit\n";
				$OUTPUT .= " exit\n";
				$OUTPUT .= "router bgp $DEV_BGPASN\n";

				$OUTPUT .= "  address-family ipv4 vrf $VRFNAME\n";
				$OUTPUT .= "   exit\n";
				$OUTPUT .= " exit\n";
			}
		}
		$OUTPUT .= "\n";
		return $OUTPUT;
	}

	public function config_vlan($VLANID, $VLANNAME = "")
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

//TODO FIXME!
		// Only configure the VLAN if it hasnt been configured!
//		if (!isset($this->data['configured'])) { $this->data['configured'] = array(); }
//		if (!isset($this->data['configured']['VLAN'.$VLANID])) { $this->data['configured']['VLAN'.$VLANID] = 0; }
		if (!$this->data['configured']['VLAN'.$VLANID])
		{
			$this->data['configured']['VLAN'.$VLANID]++;
			$OUTPUT .= "vlan $VLANID\n";
			if ($VLANNAME) {	$OUTPUT .= "  name $VLANNAME\n";	}
			$OUTPUT .= " exit\n";
		}
		$OUTPUT .= "\n";
		return $OUTPUT;
	}

	public function config_spanningtree()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= "spanning-tree mode rapid-pvst\n";
		if (preg_match("/.*swd0\d*[13579]$/i",$this->data['name'],$MATCH))
		{
			$OUTPUT .= "! Detected swd0(odd-number), STP primary root\n";
			$OUTPUT .= "spanning-tree vlan 1-4094 root primary\n";
		}
		if (preg_match("/.*swd0\d*[02489]$/i",$this->data['name'],$MATCH))
		{
			$OUTPUT .= "! Detected swd0(even-number), STP secondary root\n";
			$OUTPUT .= "spanning-tree vlan 1-4094 root secondary\n";
		}
		$OUTPUT .= "vtp mode transparent\n";
		return $OUTPUT;
	}

	public function config_motd()
	{
		$OUTPUT = "
banner motd ^

    __  PKS KTG
   //\\  _______
  //  \\//~//.--|
  Y   /\\~~//_  |
 _L  |_((_|___L_|
(/\)(____(_______)

$(hostname)

I understand that this system is to be used by authorized personnel only,
and that system usage is monitored.  By continuing, I represent that
I am an authorized user, and expressly consent to such monitoring;
if this monitoring reveals possible criminal activity, system personnel
may provide the evidence gathered to law enforcement officials.

^

";
		return $OUTPUT;
	}

	public function config_loopback()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$DEV_LOOP4      = $this->data['loopback4'];
		$OUTPUT .= "
interface Loopback0
  ip address $DEV_LOOP4 255.255.255.255
  no shut
 exit

";
		return $OUTPUT;
	}

	public function config_dns()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$OUTPUT .= "
ip domain-lookup


ip name-server 10.252.26.4
ip name-server 10.252.26.5

";
		return $OUTPUT;
	}

	public function get_qos_profile($PROFILE)
	{
		$QOS_PROFILES = array(
			"P1" => array(				// Profile P1
				"voippercent"	=> 40,	// Priority Percent for VOIP
				"q1percent"		=> 40,	// Bandwidth percent remaining Q1 (Video af4x)
				"q2percent"		=> 39,	// Bandwidth percent remaining Q2 (Bulk Data af3x, cs6,7)
				"q3percent"		=> 16,	// Bandwidth percent remaining Q3 (Transactional af2x)
				"q4percent"		=> 1,	// Bandwidth percent remaining Q4 (Scavenger af1x)
				"q5percent"		=> 3,	// Bandwidth percent remaining Q5 (Best Effort be)
			),
		);
		return $QOS_PROFILES[$PROFILE];
	}

	public function get_qos_queue($SPEED)
	{
		$QOS_QUEUES = array(
			120 => array(
				"name" => "PM_DSCP_1M_TO_120M",
				// Queue depth to begin random drop
				"d1" => 52,		// Floor
				"d2" => 171,	// Low
				"d3" => 250,	// Medium
				"d4" => 500,	// High
				"d5" => 511,	// Max
				"d6" => 512,	// Cieling
				// Queue depth to tail drop
				"q1" => 512,	// Low
				"q2" => 1024,	// Medium
				"q3" => 2048,	// High
			),
			500 => array(
				"name" => "PM_DSCP_120M_TO_500M",
				// Queue depth to begin random drop
				"d1" => 140,	// Floor
				"d2" => 216,	// Low
				"d3" => 640,	// Medium
				"d4" => 650,	// High
				"d5" => 1279,	// Max
				"d6" => 1280,	// Cieling
				// Queue depth to tail drop
				"q1" => 2048,	// Low
				"q2" => 4096,	// Medium
				"q3" => 8192,	// High
			),
			1000 => array(
				"name" => "PM_DSCP_500M_TO_1000M",
				// Queue depth to begin random drop
				"d1" => 216,	// Floor
				"d2" => 250,	// Low
				"d3" => 770,	// Medium
				"d4" => 2592,	// High
				"d5" => 5183,	// Max
				"d6" => 5184,	// Cieling
				// Queue depth to tail drop
				"q1" => 4096,	// Low
				"q2" => 8192,	// Medium
				"q3" => 16384,	// High
			),
			10000 => array(
				"name" => "PM_DSCP_1000M_TO_10000M",
				// Queue depth to begin random drop
				"d1" => 900,	// Floor
				"d2" => 1400,	// Low
				"d3" => 2100,	// Medium
				"d4" => 4300,	// High
				"d5" => 9600,	// Max
				"d6" => 9690,	// Cieling
				// Queue depth to tail drop
				"q1" => 8192,	// Low
				"q2" => 16384,	// Medium
				"q3" => 32768,	// High
			),
		);
		foreach ($QOS_QUEUES as $MAXSPEED => $QUEUE)
		{
			$LASTQUEUE = $QUEUE;
			if ($SPEED <= $MAXSPEED) { break; }
		}
		return $LASTQUEUE;
	}

	public function config_qos($SPEED,$INTERFACE)
	{
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);
		$SPEEDM = intval($SPEED);
		$SPEEDK = floatval($SPEED) * 1024;
		if ($SPEEDM < 1 || $SPEEDM > 10000) { $OUTPUT .= "ERROR: QOS requested but no policy available for {$SPEEDM}mbps!\n"; return $OUTPUT; }

		$PROFILE = "P1";
		$QOS_PROFILE = $this->get_qos_profile($PROFILE);
		$QOS_QUEUE = $this->get_qos_queue($SPEEDM);

		$OUTPUT .= "
! Global traffic classes
class-map match-any CM_DSCP_EF_CS5
  match ip dscp cs5  ef
  match ip precedence 5
class-map match-any CM_DSCP_AF4X
  match  dscp cs4  af41  af42  af43
  match ip precedence 4
class-map match-any CM_DSCP_AF3X_CS6_CS7
  match  dscp cs3  af31  af32  af33
  match  dscp cs6
  match  dscp cs7
  match ip precedence 3
  match ip precedence 6
  match ip precedence 7
class-map match-any CM_DSCP_AF2X
  match  dscp cs2  af21  af22  af23
  match ip precedence 2
class-map match-any CM_DSCP_AF1X
  match  dscp cs1  af11  af12  af13
  match ip precedence 1
class-map match-any CM_IP_BEST_EFFORT_BE
  match ip precedence 0

! Bandwidth and profile specific policy map
policy-map {$QOS_QUEUE["name"]}_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q
  class CM_DSCP_EF_CS5
    priority percent {$QOS_PROFILE["voippercent"]}
  class CM_DSCP_AF4X
    bandwidth remaining percent {$QOS_PROFILE["q1percent"]}
      queue-limit {$QOS_QUEUE["q2"]}
      random-detect dscp-based
      random-detect dscp cs4  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af41 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af42 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
      random-detect dscp af43 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
  class CM_DSCP_AF3X_CS6_CS7
    bandwidth remaining percent {$QOS_PROFILE["q2percent"]}
      queue-limit {$QOS_QUEUE["q3"]}
      random-detect dscp-based
      random-detect dscp cs3  {$QOS_QUEUE["d3"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af31 {$QOS_QUEUE["d3"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af32 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d3"]} 1
      random-detect dscp af33 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d3"]} 1
      random-detect dscp cs6  {$QOS_QUEUE["d5"]} {$QOS_QUEUE["d6"]} 10
      random-detect dscp cs7  {$QOS_QUEUE["d5"]} {$QOS_QUEUE["d6"]} 10
  class CM_DSCP_AF2X
    bandwidth remaining percent {$QOS_PROFILE["q3percent"]}
      queue-limit {$QOS_QUEUE["q2"]}
      random-detect dscp-based
      random-detect dscp cs2  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af21 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af22 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
      random-detect dscp af23 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
  class CM_DSCP_AF1X
    bandwidth remaining percent {$QOS_PROFILE["q4percent"]}
      queue-limit {$QOS_QUEUE["q2"]}
      random-detect dscp-based
      random-detect dscp cs1  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af11 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1
      random-detect dscp af12 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
      random-detect dscp af13 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]} 1
  class CM_IP_BEST_EFFORT_BE
    bandwidth remaining percent {$QOS_PROFILE["q5percent"]}
      queue-limit {$QOS_QUEUE["q1"]}
      random-detect dscp-based
      random-detect dscp 0    {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]} 1

! Parent shaper policy map
policy-map PM_SHAPE_{$SPEEDM}M_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q
  class class-default
    shape average {$SPEEDK}000
      service-policy {$QOS_QUEUE["name"]}_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q

! Apply the parent shaper policy to an interface
interface {$INTERFACE}
  bandwidth {$SPEEDK}
  service-policy output PM_SHAPE_{$SPEEDM}M_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q
 exit

";
		return $OUTPUT;
	}

}

