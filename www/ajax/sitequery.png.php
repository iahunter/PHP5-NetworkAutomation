<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

if (isset($_GET['site']))
{
	$SITE = $_GET['site'];
}else{
	header("Content-type: image/png");
	$TEXT = "ERROR: NO SITE NAME"; $COLOR = "red";
	print Utility::draw_small_status($TEXT,$COLOR);
	die();
}

$SERVICEQUERY = array();
$SERVICEQUERY["internet"	]	= "%ip nat outside%"	;
$SERVICEQUERY["flexvpn"		]	= "%FLEX_IPSEC_PROFILE%";
$SERVICEQUERY["ezvpn"		]	= "%EZVPN_CLIENT%"		;
$SERVICEQUERY["verizon"		]	= "%remote-as 65000%"	;
$SERVICEQUERY["centurylink"	]	= "%remote-as 209%"		;
$SERVICEQUERY["telus"		]	= "%telus%"				;
$SERVICEQUERY["att"			]	= "%remote-as 13979%"	;

if (isset($_GET['service']))
{
	$SERVICE = $_GET['service'];
}else{
	header("Content-type: image/png");
	$TEXT = "ERROR: NO SERVICE"; $COLOR = "red";
	print Utility::draw_small_status($TEXT,$COLOR);
	die();
}

if (!isset($SERVICEQUERY[$SERVICE]))
{
	header("Content-type: image/png");
	$TEXT = "ERROR: UNKNOWN SERVICE $SERVICE"; $COLOR = "red";
	print Utility::draw_small_status($TEXT,$COLOR);
	die();
}

$SEARCH = array(
				"category"      => "Management",
				"type"          => "device_network_%",
				"stringfield0"	=> "{$SITE}%",
				"stringfield5"	=> $SERVICEQUERY[$SERVICE],
				);
$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);
if ($COUNT)
{
	$TEXT = $SERVICE . " " . $COUNT; $COLOR = "blue";
}else{
	$TEXT = $SERVICE . " " . $COUNT; $COLOR = "gray";
}

if (!isset($_GET['detail'])) { $TEXT = ""; }	// If we dont want details, null out the text field before printing the image.

header("Content-type: image/png");
print Utility::draw_small_status($TEXT,$COLOR)

?>
