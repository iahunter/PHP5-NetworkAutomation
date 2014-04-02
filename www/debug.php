<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Debug","/debug.php");
print $HTML->header("Debug Mode");

PERMISSION_REQUIRE("debug");

if (!isset($_SESSION["DEBUG"])) { $_SESSION["DEBUG"] = 0; }

print "Debug level was " . $_SESSION["DEBUG"];

if (isset($_GET['debug']))
{
	$_SESSION["DEBUG"] = intval($_GET['debug']);
	print " and is now " . $_SESSION["DEBUG"];
}
print <<<END

	<form name="debug" method="get" action="{$_SERVER['PHP_SELF']}">
		<select name="debug">
			<option value="{$_SESSION['DEBUG']}">{$_SESSION['DEBUG']}</option>
			<option value="0">Off</option>
			<option value="1">1</option>
			<option value="2">2</option>
			<option value="3">3</option>
			<option value="4">4</option>
			<option value="5">5</option>
			<option value="6">6</option>
			<option value="7">7</option>
			<option value="8">8</option>
			<option value="9">9</option>
		</select>
		<input type="submit" name="Set Debug Level">
	</form>
END;

print $HTML->footer();
?>
