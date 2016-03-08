<?php
define("MONITOR_USER_EXPERIENCE","0");	// Disable UXM/Boomerangs for this page
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
-$HTML->breadcrumb("Honeypot Monitoring","/monitoring/honeypot.php");
-$HTML->breadcrumb("Honeypot Attack Graph",$HTML->thispage);

$HEAD_EXTRA = <<<EOT
<META HTTP-EQUIV=REFRESH CONTENT=300>
<script type="text/javascript" language="javascript">
	var int=self.setInterval("reload()",1000);
	function reload(){
		$("#myimg").attr("src", "/monitoring/honeypotgraph.png.php");
	}
</script>
EOT;

$HTML->set("HEAD_EXTRA",$HEAD_EXTRA);
$HTML->set("BODY_EXTRA",$BODY_EXTRA);

print $HTML->header("Honeypot Attack Monitor");
print <<<END
	<b>Color Codes: </b><font style="color: blue;">Detected</font> -> <font style="color: red;">Hostile</font> -> <font style="color: rgb(138,43,226);">Blackholed</font></b>
	<div style="position:relative; width: 100%; height: 90%;">
		<img id="myimg" src="/monitoring/honeypotgraph.png.php" style="position:relative; width: 100%; max-height:100%;">
	</div>
END;

print $HTML->footer();
?>
