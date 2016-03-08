<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

function is_binary($str) { return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0; }

function recursive_array_find_key_value($ARRAY,$SEARCH)
{
	if ( is_array($ARRAY) )
	{
		foreach ($ARRAY as $KEY => $VALUE)
		{
			if ($SEARCH === $KEY)
			{
				return $VALUE;
			}

			if ( is_array($VALUE) )
			{
				$FOUND = recursive_array_find_key_value($VALUE,$SEARCH);
				if ($FOUND) { return $FOUND; }
			}
		}
	}
	return 0;
}

function recursive_array_type_value_search($ARRAY,$SEARCH)
{
	if ( is_array($ARRAY) )
	{
		foreach ($ARRAY as $KEY => $VALUE)
		{
			if ($KEY === "type" && $VALUE === $SEARCH)
			{
				return $ARRAY["value"];
			}
			if ( is_array($VALUE) )
			{
				$FOUND = recursive_array_type_value_search($VALUE,$SEARCH);
				if ($FOUND) { return $FOUND; }
			}
		}
	}
	return 0;
}

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
		\metaclassing\Utility::dumper($GROUPSTRUCT);
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
		if ( isset($USERSTRUCT[0]["usercertificate"]) )
		{
			foreach ($USERSTRUCT[0]["usercertificate"] as $KEY => $VALUE)
			{
				if ( is_binary($VALUE) )
				{
					$USERSTRUCT[0]["usercertificate"][$KEY] = "-----BEGIN CERTIFICATE-----\n" . chunk_split( base64_encode($VALUE) , 64 ) . "-----END CERTIFICATE-----\n";
					require_once("File/X509.php");
					$x509 = new File_X509();
					$cert = $x509->loadX509($USERSTRUCT[0]["usercertificate"][$KEY]);
//					\metaclassing\Utility::dumper($cert["tbsCertificate"]);
					$DN =		recursive_array_find_key_value(
									recursive_array_type_value_search(	$x509->getDN() ,
																		"id-at-commonName"
																	) , "printableString"
																);
					$ISSUER =	recursive_array_find_key_value(
									recursive_array_type_value_search(	$x509->getIssuerDN() ,
																		"id-at-commonName"
																	) , "printableString"
																);
//					$USERSTRUCT[0]["usercertificate"][$KEY] = \metaclassing\Utility::dBugToString($cert["tbsCertificate"]);
					$USERSTRUCT[0]["usercertificate"][$KEY] = "Serial "		. $cert["tbsCertificate"]["serialNumber"]->toString()
															. " From "		. $ISSUER
															. " Issued to "	. $DN
															. " on "		. $cert["tbsCertificate"]["validity"]["notBefore"]["utcTime"]
															. " expires "	. $cert["tbsCertificate"]["validity"]["notAfter"]["utcTime"];
				}
			}
		}else{
			print "User certificate section missing!\n";
		}
		\metaclassing\Utility::dumper($USERSTRUCT);
	}else{
		print "USER NOT FOUND IN AD!\n";
	}

}

print $HTML->footer();

?>
