<?php
define("NO_AUTHENTICATION",1);    // Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

$NAME	= "kpdksle%sw%";	// Find KPD Kansas Switches
$IP		= "%";				// Dont care what the IP is

$SEARCH = array(
					"category"      => "Management",
					"type"          => "Device_Network_%",
					"stringfield0"  => "{$NAME}",
					"stringfield1"  => "{$IP}",
				);
$ID_MANAGED	= Information::search($SEARCH);			// Always returns an array of ID's

$OUTPUT = "";
foreach ($ID_MANAGED as $DEVICEID)					// Do something to every object ID found
{
	$DEVICE = Information::retrieve( $DEVICEID );	// Get our actual device out of the database
	$OUTPUT .= "Checking {$DEVICE->data["name"]}\n";// Print out our device name
	$CONFIG = $DEVICE->data["run"];					// Get our running config out of the info object
	unset($DEVICE);									// Clean up at the end to preserve memory on big jobs

	$STRUCTURE = cisco_parse_nested_list_to_array($CONFIG);			// Parse the space indented running config to a structure

	foreach($STRUCTURE as $SEGMENT_NAME => $SEGMENT)				// Break out configuration structure into nested segments splitting out the name first
	{
		if ( preg_match('/^interface (.*Ethernet.*)/'  ,$SEGMENT_NAME,$REG) )	// We found an interface configuration segment!
		{
			$OUTPUT .= "\tInterface {$REG[1]} - ";				// Print out the interface name we are examining
			if ( is_array($SEGMENT) )
			{
				foreach($SEGMENT as $STATEMENT_NAME => $STATEMENT)		// Break our segments into statements with name first
				{
					if ( preg_match('/mls qos trust (.*)/'	,$STATEMENT_NAME,$REG) )
					{
						$OUTPUT .= "Trusts {$REG[1]} - ";
					}
					if ( preg_match('/auto qos (.*)/'		,$STATEMENT_NAME,$REG) )
					{
						$OUTPUT .= "Uses AUTO QOS {$REG[1]}";
					}
				}
			}else{
//				print "{$REG[1]} IS NOT AN ARRAY!"; dumper($SEGMENT);
			}
			$OUTPUT .= "\n";
		}else{
//			$OUTPUT .= "Did not find interface {$SEGMENT_NAME}\n";
		}
	}
}
header("Content-Type: text/plain");
print $OUTPUT;

?>
