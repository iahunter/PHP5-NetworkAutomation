<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");

if($_SERVER['REQUEST_METHOD'] == "GET"  && isset($_GET ['id']) && isset($_GET ['action'])) { $ID = $_GET ['id']; $ACTION = $_GET ['action']; $ARRAY = $_GET;  }
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['id']) && isset($_POST['action'])) { $ID = $_POST['id']; $ACTION = $_POST['action']; $ARRAY = $_POST; }

if (!$ID)	{ print "Error: No information ID     passed!\n"; die($HTML->footer()); }
if (!$ACTION)	{ print "Error: No information action passed!\n"; die($HTML->footer()); }

$HTML->breadcrumb("Action $ACTION","/information/information-view.php?id=$ID");
print $HTML->header("Action $ACTION Information");

$INFOBJECT = Information::retrieve($ID);
$PERMISSION = "information";
if (!empty($INFOBJECT->data['category'])) { $PERMISSION .= ".{$INFOBJECT->data['category']}"; }
$PERMISSION .= ".{$INFOBJECT->data['type']}";
$PERMISSION .= ".action";
if(permission_check($PERMISSION))
{
	print $INFOBJECT->action($ACTION,$ARRAY);
	$MESSAGE = "Information Action ID:{$ID} CATEGORY:{$INFOBJECT->data['category']} TYPE:{$INFOBJECT->data['type']} ACTION:{$ACTION}";
	$DB->log($MESSAGE);
}else{
	print "Error: You lack permission $PERMISSION to perform this action!\n";
}

print $HTML->footer();

?>
