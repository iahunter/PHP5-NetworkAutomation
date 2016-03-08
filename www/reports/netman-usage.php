<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports");
$HTML->breadcrumb("Netman Utilization Report",$HTML->thispage);
print $HTML->header("Netman Utilization Report");

PERMISSION_REQUIRE("tool.log");

$QUERY = <<<END
	SELECT * FROM log
	WHERE level <= :DEBUG
	ORDER BY id DESC
END;

global $DB;
$DB->query($QUERY);
try {
	$DB->bind("DEBUG",$_SESSION["DEBUG"]);
	if (isset($CRITERIA))			{ $DB->bind("CRITERIA",$CRITERIA); }
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

/*******
* LDAP *
*******/
try {
	$LDAP = new LDAP(
					array(
						"base_dn"		   => LDAP_BASE,
						"admin_username"	=> LDAP_USER,
						"admin_password"	=> LDAP_PASS,
						"domain_controllers"=> array(LDAP_HOST),
						"ad_port"		   => LDAP_PORT,
						"account_suffix"	=> "@" . LDAP_DOMAIN,
					)
				);
} catch (adLDAPException $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

$USER_HITS = array();
$TOOL_HITS = array();
$ROW=1;
foreach($RESULTS as $RECORD)
{
	$DATE			= date("m/d/y H:i:s", strtotime($RECORD['date']));
	$USER_HITS[$LDAP->user_to_realname($RECORD['user'])]++;
//	$USER_HITS[$RECORD['user']]++;
	$TOOL_HITS[$RECORD['tool']]++;
}

arsort($USER_HITS);
arsort($TOOL_HITS);

print "<table width=1000 CELLPADDING=0 CELLSPACING=0 border=0><tr><td valign=top>";
print \metaclassing\HTML::quicktable_report("User Statistics", array("User","Log Hits"), $USER_HITS) . "\n";
print "</td><td valign=top>";
print \metaclassing\HTML::quicktable_report("Tool Statistics", array("Tool","Log Hits"), $TOOL_HITS) . "\n";
print "</td></tr></table>";

print $HTML->footer();
?>


