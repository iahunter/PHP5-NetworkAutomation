<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");
$HTML->breadcrumb("Edit");
print $HTML->header("Edit Information");

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
		print $INFOBJECT->html_form();
	}else{
		print "Error: You lack permission $PERMISSION to perform this action!\n";
	}
}



if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (isset($_POST['id']))	{ $ID = $_POST['id']; }else{ die("Error: No information ID passed!\n" . $HTML->footer() ); }

	$INFOBJECT = Information::retrieve($ID);
	$TYPE = $INFOBJECT->data['type'];
\metaclassing\Utility::dumper($TYPE);

	if ($INFOBJECT->validate($_POST))
	{
		$INFOBJECT->data = array_merge($INFOBJECT->data,$_POST);
		if (isset($_FILES)) { $UPLOAD = $INFOBJECT->upload($_FILES); } // handle uploaded files into mysql BLOBs
		$INFOBJECT->update();
		$INFOBJECT = Information::retrieve($ID);
		print <<<END
			Edited information ID $ID, click <a href="/information/information-view.php?id=$ID">here</a> to return to the information.<br>
			{$UPLOAD}
END;
		$MESSAGE = "Information Edited ID:$ID CATEGORY:{$INFOBJECT->data['category']} TYPE:{$INFOBJECT->data['type']}";
		$DB->log($MESSAGE);
		$NEXT_STEP = "view";
		if ($TYPE != $INFOBJECT->data['type']) { $NEXT_STEP = "edit"; $INFOBJECT->reinitialize();  }
		print <<<END
			click <a href="/information/information-{$NEXT_STEP}.php?id=$ID">here</a> to {$NEXT_STEP} to the information.<br><br>
END;
		if ($_SESSION["DEBUG"])
		{
			\metaclassing\Utility::dumper($INFOBJECT);
		}
		if($_SESSION["DEBUG"] <= 1)
		{
			print <<<END
<script>
setInterval(function(){
	window.location.href = "/information/information-{$NEXT_STEP}.php?id={$ID}";
},1000);
</script>
END;
		}
	}else{
		print <<<END
			Unable to validate information: <b>{$INFOBJECT->data['error']}</b><br><br>
			Please <a href="javascript:history.back()">go back</a> and correct the information provided.
END;
	}
}

print $HTML->footer();

?>
