#!/usr/bin/php
<?php
require_once "/etc/networkautomation/config.inc.php";

// bgpmon host and ip to get updates from
$host = "127.0.0.1";
$port = "50001";

$fp=fsockopen($host,$port);

$data = "";

#eat the first 5 characters.
#$crap = fread($fp,5);

print "Starting BGPmon insert process\n";

while (1)
{
	$line = fread($fp,1);
	$data .= $line;

#LOCAL#	if (preg_match('/(.*)(<BGP_MESSAGE[^>]+>.*?<\/BGP_MESSAGE>)(.*)/',$data,$reg))
	if (preg_match('/.*(<BGP_MONITOR_MESSAGE[^>]+>.*?<\/BGP_MONITOR_MESSAGE>).*/',$data,$reg))
	{
		$data = str_replace($reg[1],"",$data);

		if (preg_match('/.*UPDATE.*/',$reg[1],$asdf))
		{
			$query = "INSERT INTO bgpmon VALUES ('','".$reg[1]."')";
			$sqlcon = mysql_connect(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD);
			$sqldb  = @mysql_select_db(DB_DATABASE);
			while (!$sqlcon || !$sqldb)
			{
				if (!$sqlcon)
				{
					print "Unable to connect DB!\n";
				}
				if (!$sqldb)
				{
					print "Unable to select DB!\n";
				}
				sleep(5);
				$sqlcon = mysql_connect(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD);
				$sqldb  = @mysql_select_db(DB_DATABASE);
			}
			mysql_query($query);
			mysql_close($sqlcon);
			print "$query\n";
		}else{
#			print "";
#			print "Got $reg[1] but not an update... ignoring.\n";
		}
#print $query."\n\n";
#print $data."\n\n";
	}else{
#####		print "Got $line but not enough data to match regex.\n";
	}
}



?>
