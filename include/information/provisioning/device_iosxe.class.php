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

require_once "information/provisioning/device_ios.class.php";

class Provisioning_Device_IOSXE	extends Provisioning_Device_IOS
{
	public $type = "Provisioning_Device_IOSXE";

	public function config_multicast()
	{
		$OUTPUT = "";
		$OUTPUT .= \metaclassing\Utility::lastStackCall(new Exception);

		$OUTPUT .= "
ip multicast-routing distributed
ip multicast multipath
ip pim ssm default

int loopback0
  ip pim sparse-mode
 exit

";
		return $OUTPUT;
	}

	public function config_qos($SPEED,$INTERFACE)
	{
		$OUTPUT = "";
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
    priority level 1
    police rate percent {$QOS_PROFILE["voippercent"]}
  class CM_DSCP_AF4X
    bandwidth remaining percent {$QOS_PROFILE["q1percent"]}
      queue-limit {$QOS_QUEUE["q2"]} packets
      random-detect dscp-based
      random-detect dscp cs4  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af41 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af42 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
      random-detect dscp af43 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
  class CM_DSCP_AF3X_CS6_CS7
    bandwidth remaining percent {$QOS_PROFILE["q2percent"]}
      queue-limit {$QOS_QUEUE["q3"]} packets
      random-detect dscp-based
      random-detect dscp cs3  {$QOS_QUEUE["d3"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af31 {$QOS_QUEUE["d3"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af32 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d3"]}
      random-detect dscp af33 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d3"]}
      random-detect dscp cs6  {$QOS_QUEUE["d5"]} {$QOS_QUEUE["d6"]}
      random-detect dscp cs7  {$QOS_QUEUE["d5"]} {$QOS_QUEUE["d6"]}
  class CM_DSCP_AF2X
    bandwidth remaining percent {$QOS_PROFILE["q3percent"]}
      queue-limit {$QOS_QUEUE["q2"]} packets
      random-detect dscp-based
      random-detect dscp cs2  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af21 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af22 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
      random-detect dscp af23 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
  class CM_DSCP_AF1X
    bandwidth remaining percent {$QOS_PROFILE["q4percent"]}
      queue-limit {$QOS_QUEUE["q2"]} packets
      random-detect dscp-based
      random-detect dscp cs1  {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af11 {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}
      random-detect dscp af12 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
      random-detect dscp af13 {$QOS_QUEUE["d1"]} {$QOS_QUEUE["d2"]}
  class CM_IP_BEST_EFFORT_BE
    bandwidth remaining percent {$QOS_PROFILE["q5percent"]}
      queue-limit {$QOS_QUEUE["q1"]} packets
      random-detect dscp-based
      random-detect dscp 0    {$QOS_QUEUE["d2"]} {$QOS_QUEUE["d4"]}

! Parent shaper policy map
policy-map PM_SHAPE_{$SPEEDM}_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q
  class class-default
    shape average {$SPEEDK}000
      service-policy {$QOS_QUEUE["name"]}_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q

! Apply the parent shaper policy to an interface
interface {$INTERFACE}
  bandwidth {$SPEEDK}
  service-policy output PM_SHAPE_{$SPEEDM}_{$QOS_PROFILE["voippercent"]}_{$PROFILE}_6Q
 exit

";
		return $OUTPUT;
	}

}
