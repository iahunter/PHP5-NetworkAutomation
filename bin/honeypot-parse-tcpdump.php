#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

// Run with this command:
//		tcpdump -l -n -i eth0 ip dst net 123.456.72.0/25 | /opt/networkautomation/bin/blackhole-parse-tcpdump.php
// OR 2 separate commands in screenL
//		tcpdump -l -n -i eth0 ip dst net 123.456.72.0/25 > /tmp/TCPDUMP
//		tail -f /tmp/TCPDUMP | /opt/networkautomation/bin/blackhole-parse-tcpdump.php

$SUSPECT_HITS = array(); // Slightly different definition of suspects than other tools

$TIME = time();	// Get our start time, we rotate the suspect_hits array every 60 secondsish.

while ( !feof(STDIN) )			// Loop while there is a STDIN stream
{
	$LINE = fgets(STDIN, 8192);	// Get a line of input to process

/*	Sample input lines:
10:56:01.444958 IP 192.3.79.198.40982 > 123.456.72.56.23: Flags [.], ack 470991721, win 365, options [nop,nop,TS val 47864167 ecr 858817376], length 0
10:56:02.375564 IP 192.3.79.198.40982 > 123.456.72.56.23: Flags [F.], seq 0, ack 2, win 365, options [nop,nop,TS val 47864261 ecr 858817628], length 0
10:56:08.380378 IP 93.155.162.25.53656 > 123.456.72.56.23: Flags [S], seq 112635879, win 14600, options [mss 1460,sackOK,TS val 10937038 ecr 0,nop,wscale 2], length 0
10:56:08.549353 IP 93.155.162.25.53656 > 123.456.72.56.23: Flags [.], ack 168413823, win 3650, options [nop,nop,TS val 10937054 ecr 858819173], length 0
10:56:10.730300 IP 92.222.36.34.22 > 123.456.72.46.3306: Flags [R], seq 4235684790, win 0, length 0
10:56:11.776704 IP 93.155.162.25.53656 > 123.456.72.56.23: Flags [.], ack 15, win 3650, options [nop,nop,TS val 10937377 ecr 858819980], length 0
10:56:12.789357 IP 93.155.162.25.53656 > 123.456.72.56.23: Flags [F.], seq 0, ack 16, win 3650, options [nop,nop,TS val 10937479 ecr 858820231], length 0
10:56:17.702860 IP 217.146.176.105.443 > 123.456.72.57.35792: Flags [S.], seq 428496087, ack 3974365185, win 8192, options [mss 1460], length 0
/**/

/*	Sample output arrays:
Arrays
(
    [0] => 11:14:04.516458 IP 111.74.238.10.6000 > 123.456.72.11.22: Flags [R], seq 1680539649, win 0, length 0
    [1] => 11:14:04.516458
    [2] => 111.74.238.10
    [3] => 6000
    [4] => 123.456.72.11
    [5] => 22
    [6] => Flags [R], seq 1680539649, win 0, length 0
)
(
    [0] => 11:14:04.518390 IP 111.74.238.10.6000 > 123.456.72.60.22: Flags [R], seq 610795521, win 0, length 0
    [1] => 11:14:04.518390
    [2] => 111.74.238.10
    [3] => 6000
    [4] => 123.456.72.60
    [5] => 22
    [6] => Flags [R], seq 610795521, win 0, length 0
)
ERROR: DID NOT MATCH LINE: 11:14:07.710408 IP 37.139.14.86 > 123.456.72.9: ICMP 37.139.14.86 udp port 53 unreachable, length 40
(
    [0] => 11:14:08.491005 IP 195.211.154.178.45597 > 123.456.72.1.21320: Flags [S], seq 2180672893, win 65535, length 0
    [1] => 11:14:08.491005
    [2] => 195.211.154.178
    [3] => 45597
    [4] => 123.456.72.1
    [5] => 21320
    [6] => Flags [S], seq 2180672893, win 65535, length 0
)
(
    [0] => 11:14:10.540586 IP 111.74.238.10.6000 > 123.456.72.31.22: Flags [R], seq 1209466881, win 0, length 0
    [1] => 11:14:10.540586
    [2] => 111.74.238.10
    [3] => 6000
    [4] => 123.456.72.31
    [5] => 22
    [6] => Flags [R], seq 1209466881, win 0, length 0
)
(
    [0] => 11:14:14.040329 IP 219.78.160.218.57139 > 123.456.72.45.46648: UDP, length 20
    [1] => 11:14:14.040329
    [2] => 219.78.160.218
    [3] => 57139
    [4] => 123.456.72.45
    [5] => 46648
    [6] => UDP, length 20
)
/**/

	// not-whitespace timestamp - space - IP - space - (IP).(port) - space > space - (IP).(port) - :anything we no longer care.
	$REGEX = "/^(\S+)\s+IP\s+(\d+\.\d+\.\d+\.\d+)\.(\d+)\s>\s(\d+\.\d+\.\d+\.\d+)\.(\d+):\s(.+)$/";
	if ( preg_match($REGEX,$LINE,$MATCH) )
	{
		// process the output:
		//\metaclassing\Utility::dumper($MATCH);
		//$TIME		= $MATCH[1];	// Who cares?
		$SRC_IP		= $MATCH[2];
		//$SRC_PORT	= $MATCH[3];	// Who cares?
		$DST_IP		= $MATCH[4];
		$DST_PORT	= $MATCH[5];
		//$EXTRA	= $MATCH[6];	// Who cares?

		// Increment the number of hits we have seen this suspect ip for THIS TIME INTERVAL!
		if ( !isset($SUSPECT_HITS[$SRC_IP]) ) { $SUSPECT_HITS[$SRC_IP] = 0; }
		$SUSPECT_HITS[$SRC_IP]++;	// Increment hits for this src ip in this time interval

		// Check if we are worth tracking this suspect
		if ( $SUSPECT_HITS[$SRC_IP] >= 10 )
		{
			$HITCOUNT = $SUSPECT_HITS[$SRC_IP];
			unset($SUSPECT_HITS[$SRC_IP]);

			$CATEGORY   = "Blackhole";
			$TYPE       = "Suspect";
			$PARENT     = "";

			$QUERY = <<<END
				SELECT id AS hits FROM information
				WHERE category LIKE "{$CATEGORY}"
				AND type LIKE "{$TYPE}"
				AND stringfield1 = "{$SRC_IP}"
				AND active = 1
				AND modifiedwhen >= DATE_SUB(NOW(),INTERVAL 1 MINUTE);
END;
			$DB->query($QUERY);
			try {
				$DB->execute();
				$RESULTS = $DB->results();
			} catch (Exception $E) {
				$MESSAGE = "Exception: {$E->getMessage()}";
				trigger_error($MESSAGE);
				die("Database Query Error!\n");
			}
			$COUNT = count($RESULTS);

			if ( $COUNT >= 10 )
			{
				print "SUSPECT {$SRC_IP} has {$HITCOUNT} hits, but {$COUNT} {$TYPE} records in the last minute, skipping...\n";
				continue;
			}
			print "SUSPECT {$SRC_IP} has {$HITCOUNT} hits in this time interval! resetting...\n";

			$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);
			$ID = $INFOBJECT->insert();

			$INFOBJECT = Information::retrieve($ID);
			$INFOBJECT->initialize();
			$INFOBJECT->update();

			$INFOBJECT->data["source"]  = $SRC_IP;
			$INFOBJECT->data["target"]  = $DST_IP;
			$INFOBJECT->data["port"]    = $DST_PORT;

			$INFOBJECT->update();
			unset($INFOBJECT);
		}

	}else{
		// Ignore the line, arp/non-ip/etc.
//		print "ERROR: DID NOT MATCH LINE: {$LINE}";
	}

	// Process rotation of the suspect hits for each time interval
	$NOW = time();
	if ($NOW > $TIME + 60)	// If we are outside the 60 second time interval, rotate the SUSPECT_HITS array!
	{
		print "TIME INTERVAL EXPIRED! {$NOW} > {$TIME} + 60\n";
		\metaclassing\Utility::dumper($SUSPECT_HITS);
		$TIME = $NOW;
		unset($SUSPECT_HITS);
		$SUSPECT_HITS = array();
	}

	ob_flush();	// Hopefully clear out some unused memory to prevent crapping?
}

fclose(STDIN);

?>
