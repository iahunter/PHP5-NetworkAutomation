<?php
define("MONITOR_USER_EXPERIENCE","0");	// Disable UXM/Boomerangs for this page
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Monitoring","");
$HTML->breadcrumb("BGP Graph",$HTML->thispage);

print $HTML->header("BGP ASN Adjacencies");

print <<<END
	<b>Color Codes: </b>
	<font style="color: rgb(138,43,226);">KPN</font>
	<font style="color: orange;">FlexVPN</font>
	<font style="color: blue;">AT&T</font>
	<font style="color: green;">CenturyLink</font>
	<font style="color: red;">Telus</font>
	<b>NOTE: THIS PAGE TAKES A LONG TIME TO LOAD >40 SECONDS. PLEASE BE PATIENT!</b>
<br>
END;

print <<<END
	<img id="zoomzoom" src="/monitoring/bgpgraph.png.php?layout=neato" alt="" width="1800" height="950" data-zoom-image="/monitoring/bgpgraph.png.php?layout=neato">
	<script src="/js/jquery.elevatezoom.js"></script>
	<script>
		$('#zoomzoom').elevateZoom({constrainType:"height", constrainSize:500, zoomType: "lens", scrollZoom : true});
		$('#zoomzoom').elevateZoom({constrainType:"height", constrainSize:400, zoomType: "lens", containLensZoom: true, scrollZoom : true});
	</script>
END;

print $HTML->footer();
?>
