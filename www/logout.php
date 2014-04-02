<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Log In","/");
print $HTML->header("Logged Out");
session_destroy();
$HTML->set("LOGOUT_LINK","");
print "Session Cleared.<br>\n";
exit($HTML->footer());
?>
