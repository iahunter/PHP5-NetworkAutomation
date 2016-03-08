<?php
define("NO_AUTHENTICATION",1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

header("Content-Type: text/plain");

if ( isset($_GET["ips"]) && count($_GET["ips"]) )
{
	$IPS = $_GET["ips"];
}else{
	$IPS = array("10.251.48.10","10.251.48.11");
}

$FWLOGS = array();
foreach ($IPS as $IP)
{
	$COMMAND = new command(
							array(
								"ip"		=> $IP,
								"protocol"	=> "ssh2",
							)
						);
	$CLI = $COMMAND->getcli();
	if (!$CLI->connected)
	{
		print "The tool could not connect to device with IP {$IP} and get a prompt!<br>\n";
		die("Croak!\n");
	}

	$PROMPT = $CLI->prompt;

	$CLI->exec("enable\n" . TACACS_ENABLE);
	$CLI->exec("terminal pager 0");

	$LOG		= $CLI->exec("show log | I 106023");
	$LENGTH		= strlen( $LOG );
	$FWLOGS[$IP]= preg_split( "/\r\n|\r|\n/", $LOG );
	$LINES		= count( $FWLOGS[$IP] );

	$CLI->disconnect();
}

$HITS = array();

foreach ($FWLOGS as $IP => $LOGLINES)
{
	foreach($LOGLINES as $LINE)
	{
		// Mar 16 2015 20:57:10 V901-V101 : %ASA-4-106023: Deny tcp src outside:10.251.52.73/61688 dst inside:10.4.0.200/25 by access-group "ACL_V901:DMZ_V101:DATACENTER" [0x0, 0x0]
		$REGEX = "/(.+) (\S+) : %ASA-4-106023: Deny (\S+) src (\S+):(\S+)\/(\d+) dst (\S+):(\S+)\/(\d+) by .*/";
		if ( preg_match($REGEX,$LINE,$REG) )
		{
		/*	$REG[] = array(
			DATE
			CONTEXT NAME
			PROTOCOL
			EXT INTERFACE NAME
			SOURCE IP
			SOURCE PORT
			INT INTERFACE NAME
			DEST IP
			DEST PORT	);	*/
			$HIT = array();
			$HIT["firewall"]= $IP;
			$HIT["datetime"]= $REG[1];
			$HIT["context"]	= $REG[2];
			$HIT["protocol"]= $REG[3];
			$HIT["srcint"]	= $REG[4];
			$HIT["srcip"]	= $REG[5];
			$HIT["srcport"]	= $REG[6];
			$HIT["dstint"]	= $REG[7];
			$HIT["dstip"]	= $REG[8];
			$HIT["dstport"]	= $REG[9];
			$HIT["dstsocket"]	= "{$HIT["dstip"]}:{$HIT["dstport"]}";
			$HIT["connection"]	= "{$HIT["srcip"]} --> {$HIT["dstsocket"]}";
			array_push($HITS,$HIT);
			unset($HIT);
		}
	}
}

$DELIMITER = ",";
print "Firewall,DateTime,Context,Proto,SrcInt,SrcIP,SrcPort,DstInt,DstIP,DstPort,DstSocket,Connection\n";
foreach ($HITS as $HIT)
{
	print "{$HIT["firewall"]}"	. $DELIMITER;
	print "{$HIT["datetime"]}"	. $DELIMITER;
	print "{$HIT["context"]}"	. $DELIMITER;
	print "{$HIT["protocol"]}"	. $DELIMITER;
	print "{$HIT["srcint"]}"	. $DELIMITER;
	print "{$HIT["srcip"]}"		. $DELIMITER;
	print "{$HIT["srcport"]}"	. $DELIMITER;
	print "{$HIT["dstint"]}"	. $DELIMITER;
	print "{$HIT["dstip"]}"		. $DELIMITER;
	print "{$HIT["dstport"]}"	. $DELIMITER;
	print "{$HIT["dstsocket"]}"	. $DELIMITER;
	print "{$HIT["connection"]}"			;
	print "\n";
}
