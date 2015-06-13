<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("LDAP Query",$HTML->thispage);
print $HTML->header("LDAP Query Tool");

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

if ($_GET['group'])
{
	$GROUP = $_GET['group'];
	$GROUPSTRUCT = $LDAP->group()->info( $GROUP , array("*") );
	if ( $GROUPSTRUCT["count"] )
	{
		dumper($GROUPSTRUCT);
	}else{
		print "GROUP NOT FOUND IN AD!\n";
	}
}else{

	if ($_GET['user'])
	{
		$USERNAME = $_GET['user'];
	}else{
		$USERNAME = $_SESSION["AAA"]["username"];
	}

	$REALNAME = $LDAP->user_to_realname($USERNAME);
	print "{$USERNAME}'s real name is: {$REALNAME}<br>\n";
	$USERSTRUCT = $LDAP->user()->info( $USERNAME , array("*") );
	if ( $USERSTRUCT )
	{
		dumper($USERSTRUCT);
	}else{
		print "USER NOT FOUND IN AD!\n";
	}

}

print $HTML->footer();

?>
