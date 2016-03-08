<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$graph = unserialize ( base64_decode( $_GET["graph"] ) );
$graphviz = new Graphp\GraphViz\GraphViz();
$PNG = $graphviz->createImageData($graph);

header("Content-Type: image/png");
print $PNG;
