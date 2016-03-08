<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");
$HTML->breadcrumb("Toggle Active");
print $HTML->header("Information Active Toggle");

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	if (isset($_GET['id']))	{ $ID = $_GET['id']; }else{ die("Error: No information ID passed!\n" . $HTML->footer() ); }
	$INFOBJECT = Information::retrieve($ID);
	$PERMISSION = "information";
	if (!empty($INFOBJECT->data['category'])) { $PERMISSION .= ".{$INFOBJECT->data['category']}"; }
	$PERMISSION .= ".{$INFOBJECT->data['type']}";
	$PERMISSION .= ".edit";
	if(permission_check($PERMISSION))
	{
		$MESSAGE = $INFOBJECT->toggle_active();
		
		$ACTIVE = intval($INFOBJECT->data['active']);
		if ($ACTIVE)
		{
			$NEXTSTEP = "/information/information-view.php?id={$ID}";
		}else{
			$PARENT = intval($INFOBJECT->data['parent']);
			if ($PARENT)
			{
				$NEXTSTEP = "/information/information-view.php?id={$PARENT}";
			}else{
				$NEXTSTEP = "/information/information-list.php?category={$INFOBJECT->data['category']}&type={$INFOBJECT->data['type']}";
			}
		}
		
		print <<<END
			<pre>{$MESSAGE}</pre>
			Toggled Active State Information ID $ID and any children, click <a href="{$NEXTSTEP}">here</a> to continue.<br>
END;
		$MESSAGE = "Information Active Toggle ID:$ID CATEGORY:{$INFOBJECT->data['category']} TYPE:{$INFOBJECT->data['type']} ACTIVE:{$INFOBJECT->data['active']}";
		$DB->log($MESSAGE);
		if ($_SESSION["DEBUG"])
		{
			\metaclassing\Utility::dumper($INFOBJECT);
		}
		if($_SESSION["DEBUG"] <= 1)
		{
			print <<<END
<script>
setInterval(function(){
	window.location.href = "{$NEXTSTEP}";
},1000);
</script>
END;
		}
	}
}

print $HTML->footer();

?>
