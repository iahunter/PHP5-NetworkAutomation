<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$DEBUGOUTPUT = "";

if ( isset($_GET["id"]) )
{
	$DEBUGOUTPUT .= $HTML->timer_diff() . "Getting Information\n";
	$DEVICE = Information::retrieve($_GET['id']);
	$DEBUGOUTPUT .= $HTML->timer_diff() . "DONE!\n";

	if ( isset($DEVICE->data["ip"]) )
	{
		$HOST = $DEVICE->data["ip"];
	}else{
		die();	// Could not get a database hit for device ip and protocol
	}

	$DEBUGOUTPUT .= $HTML->timer_diff() . "Host is {$HOST}\n";

	switch ($DEVICE->data["protocol"])
	{
		case "ssh2":
			$PORT = 22;
			break;
		case "ssh1":
			$PORT = 22;
			break;
		case "telnet":
			$PORT = 23;
			break;
		default:
			$PORT = "";
			break;
	}

	$DEBUGOUTPUT .= $HTML->timer_diff() . "Port is {$PORT}\n";

}else{
	if (isset($_GET['host'	]	)) { $HOST	= $_GET['host'	];	}else{ die();		}
	if (isset($_GET['port'	]	)) { $PORT	= $_GET['port'	];	}else{ $PORT  = "";	}
}

// On with the show!

if ($PORT)	// If they gave us a port, do a TCP probe
{
	$TEXT = "tcp {$PORT}";
	$DEBUGOUTPUT .= $HTML->timer_diff() . "Probing TCP/{$PORT}\n";
	if (!\metaclassing\Utility::tcpProbe($HOST,$PORT))
	{
	    $COLOR = "red";
		$DEBUGOUTPUT .= $HTML->timer_diff() . "Probe Failed!\n";
	}else{
	    $COLOR = "green";
		$DEBUGOUTPUT .= $HTML->timer_diff() . "Probe Succeeded!\n";
	}
}else{		// Otherwise, just do ICMP
	$DEBUGOUTPUT .= $HTML->timer_diff() . "Probing ICMP\n";
	$PING = new \JJG\Ping($HOST);
	$LATENCY = $PING->ping("exec");
	if (!$LATENCY)
	{
		$TEXT = "Timed Out";
		$COLOR = "red";
		$DEBUGOUTPUT .= $HTML->timer_diff() . "Probe Failed!\n";
	}else{
		$TEXT = "{$LATENCY}ms";
		$DEBUGOUTPUT .= $HTML->timer_diff() . "Probe Succeeded in {$LATENCY}ms!\n";
		if ($LATENCY <  100						) { $COLOR = "green" ; }
		if ($LATENCY >= 100 && $LATENCY < 500	) { $COLOR = "yellow"; }
		if ($LATENCY >= 500						) { $COLOR = "orange"; }
	}
}

$DEBUGOUTPUT .= $HTML->timer_diff() . "Work complete, rendering image\n";
if (!isset($_GET['detail']	)) { $TEXT = ""; }	// If we dont want details, null out the text field before printing the image.

if ( isset($_GET["debug"]) )
{
	print "<pre>{$DEBUGOUTPUT}</pre>";
}else{
	header("Content-type: image/png");
	print \metaclassing\Utility::drawSmallStatus($TEXT,$COLOR);
}

?>
