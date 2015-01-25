<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("IMEI Calculator",$HTML->thispage);
print $HTML->header("Search");

print <<<END
		<form name="imei" method="get" action="{$_SERVER['PHP_SELF']}">
			IMEI Number: <input type="text" size=30 name="imei" value="{$_GET['imei']}">
			<input type="submit" value="Calculate">
		</form><br>
END;
if (!isset($_GET['imei']))
{
	die($HTML->footer());
}

$IMEI = $_GET['imei'];
$IMEI_CHOPPED = substr($IMEI,0,-1);

$FIELD = "stringfield6";
$VALUE = $IMEI_CHOPPED . "%";

require_once("luhn.class.php");
$LUHN = new Luhn;
$IMEI_LAST_CHECK = $LUHN->calculate($IMEI_CHOPPED);

if ($IMEI == $IMEI_CHOPPED . $IMEI_LAST_CHECK)
{
	print "Provided IMEI {$IMEI} has the correct Luhn check digit {$IMEI_LAST_CHECK}<br>\n";
}else{
	print "Provided IMEI {$IMEI} has an incorrect Luhn check digit, should be {$IMEI_LAST_CHECK}<br>\n";
}

$SEARCH = array(
	"category"      => "Equipment",
	"type"          => "TerminalServer",
	$FIELD			=> $VALUE,
);

$RESULTS = Information::search($SEARCH);
$COUNT = count($RESULTS);

$RECORDCOUNT = count($RESULTS);
print "<br>Found {$RECORDCOUNT} matching terminal server records.<br><br>\n";

if ($COUNT)
{
	foreach($RESULTS as $ID)
	{
		$TERMSERV = Information::retrieve($ID);
		$IMEI_CHOPPED = substr($TERMSERV->data["wirelessid"],0,-1);
		$IMEI_LAST_CHECK = $LUHN->calculate($IMEI_CHOPPED);
		$IMEI_LAST_USER	= substr($IMEI,-1							);
		$IMEI_LAST_REAL	= substr($TERMSERV->data["wirelessid"],-1	);
		print "Matching Terminal Server Record: ID <a href=\"/information/information-view.php?id={$TERMSERV->data["id"]}\">{$TERMSERV->data["id"]}</a> IMEI {$TERMSERV->data["wirelessid"]}<br><br>\n";
		print "Luhn Check for {$IMEI_CHOPPED} Correct digit is: {$IMEI_LAST_CHECK} Database is: {$IMEI_LAST_REAL} User provided: {$IMEI_LAST_USER}<br>\n";
		if ($TERMSERV->data["wirelessid"] == $IMEI_CHOPPED . $IMEI_LAST_CHECK)
		{
			print "Database IMEI {$TERMSERV->data["wirelessid"]} has the correct Luhn check digit {$IMEI_LAST_CHECK}<br>\n";
		}else{
			print "Database IMEI {$TERMSERV->data["wirelessid"]} has an incorrect Luhn check digit, should be {$IMEI_LAST_CHECK}<br>\n";
		}
		unset($TERMSERV);
	}
}else{
	print "Search for IMEI {$IMEI} did not return any terminal server records in the database.<br>\n";
}

print $HTML->footer();

?>
