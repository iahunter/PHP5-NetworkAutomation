<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Monitoring","/monitoring/");
$HTML->breadcrumb("Apache Error Log",$HTML->thispage);

$USER = "%"; if (isset($_GET['user'])) { $USER = "{$_GET['user']}"; }
$TOOL = "%"; if (isset($_GET['tool'])) { $TOOL = "{$_GET['tool']}"; }

$HEAD_EXTRA = <<<EOT
<META HTTP-EQUIV=REFRESH CONTENT=300>
<script type="text/javascript" language="javascript">
   var http_request = false;
   function makeGETRequest(url) {
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
      http_request.open('GET', url, true);
      http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
//    http_request.setRequestHeader("Content-length", parameters.length);
//    http_request.setRequestHeader("Connection", "close");
      http_request.send(parameters);
   }

   function alertContents() {
      if (http_request.readyState == 4) {
         if (http_request.status == 200) {
            result = http_request.responseText;
            document.getElementById('myspan').innerHTML = result;
            setTimeout('makeGETRequest(\'syslogupdates.php?user={$USER}&tool={$TOOL}\')',1000);
         } else {
            document.getElementById('myspan').innerHTML = "Error fetching content, reloading page...";
			window.location.reload(true);
         }
      }
   }
</script>
EOT;

$BODY_EXTRA = " onLoad=\"setTimeout('makeGETRequest(\'syslogupdates.php?user={$USER}&tool={$TOOL}\')',100);\"";

$HTML->set("HEAD_EXTRA",$HEAD_EXTRA);
$HTML->set("BODY_EXTRA",$BODY_EXTRA);

print $HTML->header("Apache Error Log Monitor");

?>
<span name="myspan" id="myspan">Querying LOG database, please wait...</span>
<?php
print $HTML->footer();
?>
