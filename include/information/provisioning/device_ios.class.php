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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= '
aaa new-model
username console privilege 15 secret changeme
username telecom privilege 15 secret  changeme
enable secret changeme
';
		$OUTPUT .= "
no enable password
!
aaa group server tacacs+ AAA_GROUP_ADMIN
  server-private 10.252.12.108 timeout 3 key changeme
  server-private 10.252.12.109 timeout 3 key changeme
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
  permit 74.126.50.0 0.0.0.255
  permit 192.174.72.0 0.0.7.255
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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$SITENAME = $this->parent()->data['name'];
		$DEV_MGMTVRF = $this->data['mgmtvrf'];

		$OUTPUT .= "
snmp-server location {$SITENAME}
snmp-server contact Network Operations
ip access-list standard ACL_SNMP_RW
  permit 172.30.0.246
  permit 10.123.0.0 0.0.255.255
 exit
ip access-list standard ACL_SNMP_RO
  permit 74.126.50.12
  permit 74.126.50.129
  permit 10.0.112.0 0.0.15.255
  permit 10.0.210.0 0.0.1.255
  permit 10.202.0.0 0.0.255.255
  permit 10.250.224.0 0.0.15.255
  permit 172.17.251.0 0.0.0.255
  permit 172.30.0.0 0.0.255.255
 exit
snmp-server community changeme RO ACL_SNMP_RO
snmp-server community changeme RW ACL_SNMP_RW
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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

		$DEV_BGPASN = $this->parent->get_asn();

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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
				if ($VPNID >= 100 && $VPNID <= 199)
				{
					$OUTPUT .= "    maximum routes 10000 80\n";
				}else{
					$OUTPUT .= "    maximum routes 100 80\n";
				}
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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);
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
		$OUTPUT .= Utility::last_stack_call(new Exception);

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
		$OUTPUT .= Utility::last_stack_call(new Exception);
		$OUTPUT .= "
ip domain-lookup


ip name-server 10.252.26.4
ip name-server 10.252.26.5

";
		return $OUTPUT;
	}

}

?>
