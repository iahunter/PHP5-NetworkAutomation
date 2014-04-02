<?php
define(NO_AUTHENTICATION,1);
require_once "/etc/networkautomation/networkautomation.inc.php";

$OUTPUT = "";

if (count($_GET))
{
	$OUTPUT .= "GET:\n";
	$OUTPUT .= dumper_to_string($_GET);
}else if (count($_POST)){
	$OUTPUT .= "POST:\n";
	$OUTPUT .= dumper_to_string($_POST);
}else{
	$OUTPUT .= "No GET/POST data recieved";
}

print $OUTPUT;

$FILE = 'api.txt';
file_put_contents($FILE, $OUTPUT, FILE_APPEND | LOCK_EX);

?>
