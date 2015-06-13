<?php
define("MONITOR_USER_EXPERIENCE","0");	// Disable UXM/Boomerangs for this page
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Monitoring","/monitoring/");
$HTML->breadcrumb("BGP",$HTML->thispage);

$HEAD_EXTRA = <<<EOT
<META HTTP-EQUIV=REFRESH CONTENT=300>
<script type="text/javascript" language="javascript">
   var http_request = false;
   function makePOSTRequest(url) {
      var parameters = "update=yes";
      http_request = false;
      if (window.XMLHttpRequest) {
         http_request = new XMLHttpRequest();
         if (http_request.overrideMimeType) {
            http_request.overrideMimeType('text/html');
         }
      } else if (window.ActiveXObject) {
         try {
            http_request = new ActiveXObject("Msxml2.XMLHTTP");
         } catch (e) {
            try {
               http_request = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e) {}
         }
      }
      if (!http_request) {
         alert('Cannot create XMLHTTP instance');
         return false;
      }

      http_request.onreadystatechange = alertContents;
      http_request.open('POST', url, true);
      http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      http_request.setRequestHeader("Content-length", parameters.length);
      http_request.setRequestHeader("Connection", "close");
      http_request.send(parameters);
   }

   function alertContents() {
      if (http_request.readyState == 4) {
         if (http_request.status == 200) {
			if (navigator.appName == 'Microsoft Internet Explorer') { $('.ui-tooltip').hide(); }
            result = http_request.responseText;
            document.getElementById('myspan').innerHTML = result;
            setTimeout('makePOSTRequest(\'bgpupdates.php\')',1000);
         } else {
            document.getElementById('myspan').innerHTML = "Error fetching content, reloading page...";
			window.location.reload(true);
         }
      }
   }
</script>
EOT;

$BODY_EXTRA = " onLoad=\"setTimeout('makePOSTRequest(\'bgpupdates.php\')',100);\"";

$HTML->set("HEAD_EXTRA",$HEAD_EXTRA);
$HTML->set("BODY_EXTRA",$BODY_EXTRA);

print $HTML->header("BGP Update Monitor");

?>
<table width="900">
	<tr>
		<td>
			<form action="bgpupdates.php" method="get">Search BGP Message Contents: <input type="text" name="search"> <input type="submit" value="Search!"></form>
		</td>
		<td align="right">
			<ul class="object-tools">
				<li>
					<a href="/reports/prefix-instability.php">Prefix Instability Report</a>
				</li>
				<li>
					<a href="/monitoring/bgpgraph.php">BGP ASN Adjacency Graph</a>
				</li>
			</ul>
		</td>
	</tr>
</table><br>
<span name="myspan" id="myspan">Querying BGP database, please wait...</span>
<?php
print $HTML->footer();
?>
