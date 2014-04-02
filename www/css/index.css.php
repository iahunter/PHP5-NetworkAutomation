<?php
header('Content-Type: text/css');
foreach(glob("*.css") as $class_filename) {
	require_once($class_filename);
}
?>
