<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']))
{
	$ID = $_GET['id'];
	$INFOBJECT = Information::retrieve($ID);
	if (isset($INFOBJECT->data['parent']) && $INFOBJECT->data['parent'] > 0)
	{
		$HTML->breadcrumb("View","/information/information-view.php?id={$INFOBJECT->data['parent']}");
	}else{
		$HTML->breadcrumb("View","/information/information-list.php?category={$INFOBJECT->data['category']}&type={$INFOBJECT->data['type']}");
	}
	print $HTML->header("View Information");
	print $INFOBJECT->html_detail();
}else{
	$HTML->breadcrumb("View");
	print $HTML->header("View Information");
	print "Error: No information ID passed!\n";
}

print $HTML->footer();

?>
