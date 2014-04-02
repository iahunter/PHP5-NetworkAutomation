<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("LDAP Query",$THISPAGE);
print $HTML->header("LDAP Query Tool");


if ($_GET['group'])
{
	$GROUP = $_GET['group'];
	dumper($LDAP->group()->info($GROUP,array("*")));

}else{

	if ($_GET['user'])
	{
		$USERNAME = $_GET['user'];
	}else{
		$USERNAME = $_SESSION["AAA"]["username"];
	}

	$REALNAME = $LDAP->user_to_realname($USERNAME);
	print "{$USERNAME}'s real name is: {$REALNAME}<br>\n";
	dumper($LDAP->user()->info($USERNAME,array("*")));

}

print $HTML->footer();

?>
