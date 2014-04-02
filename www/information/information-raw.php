<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['method']))
{
	$ID = $_GET['id'];
	$METHOD = $_GET['method'];
	$INFOBJECT = Information::retrieve($ID);
	print $INFOBJECT->$METHOD($_GET);
}else{
	print "Error: No information ID and METHOD passed!\n";
}

?>
