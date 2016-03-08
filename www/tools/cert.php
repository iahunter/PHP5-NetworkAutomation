<?php
define("MONITOR_USER_EXPERIENCE",0);	// Disable UXM/Boomerangs for this page
define("NO_AUTHENTICATION"		,1);	// Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML = new \metaclassing\HTML;
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","");
$HTML->breadcrumb("Cert Dump",$HTML->thispage);
print $HTML->header("Cert Dump");

$DUMP = $_SERVER;
foreach ($DUMP as $KEY => $VALUE)
{
	$REGEX = "/SSL_CLIENT_/";
	if ( !preg_match($REGEX,$KEY,$REG) )
	{
		unset($DUMP[$KEY]);
	}
}
ksort($DUMP);
\metaclassing\Utility::dumper($DUMP);

print $HTML->footer();

?>
