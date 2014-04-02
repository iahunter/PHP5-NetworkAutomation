<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Information");
$HTML->breadcrumb("Add");
print $HTML->header("Add Information");

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	if (isset($_GET['parent']))		{ $PARENT	= $_GET['parent'];	}else{ $PARENT = 0;				}
	if (isset($_GET['category']))	{ $CATEGORY	= $_GET['category'];}else{ $CATEGORY = "";			}
	if (isset($_GET['type']))		{ $TYPE		= $_GET['type'];	}else{ $TYPE = "Information";	}

	$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);
	$PERMISSION = "information";
	if (!empty($INFOBJECT->data['category'])) { $PERMISSION .= ".{$INFOBJECT->data['category']}"; }
	$PERMISSION .= ".{$INFOBJECT->data['type']}";
	$PERMISSION .= ".add";
	if(permission_check($PERMISSION))
	{
		print $INFOBJECT->html_form();
	}else{
		print "Error: You lack permission $PERMISSION to perform this action!\n";
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if (isset($_POST['parent']))	{ $PARENT	= $_POST['parent'];	}else{ $PARENT = 0;		}
	if (isset($_POST['category']))	{ $CATEGORY	= $_POST['category'];	}else{ $CATEGORY = "";		}
	if (isset($_POST['type']))	{ $TYPE		= $_POST['type'];	}else{ $TYPE = "Information";	}
	$INFOBJECT = Information::create($TYPE,$CATEGORY,$PARENT);

	if ($INFOBJECT->validate($_POST))
	{
		$INFOBJECT->data = array_merge($INFOBJECT->data,$_POST);
		$ID = $INFOBJECT->insert();
		print <<<END
			Created new information record with ID $ID,<br>
END;
		$INFOBJECT = Information::retrieve($ID);	// In case of devices that change their type
		print $INFOBJECT->initialize();				// Initialize the information if applicable
		$INFOBJECT->update();						// We need to run the update function to allow update_bind() and update_override()

		$NEXT_STEP = "view";
		if ($TYPE != $INFOBJECT->data['type']) { $NEXT_STEP = "edit"; }
		print <<<END
			click <a href="/information/information-{$NEXT_STEP}.php?id=$ID">here</a> to {$NEXT_STEP} to the information.<br><br>
END;
		if($_SESSION["DEBUG"] <= 1)	// Only redirect automagically when debug is OFF.
		{
			print <<<END
<script>
setInterval(function(){
	window.location.href = "/information/information-{$NEXT_STEP}.php?id={$ID}";
},1000);
</script>
END;
		}
		$MESSAGE = "Information Added ID:$ID PARENT:$PARENT CATEGORY:$CATEGORY TYPE:$TYPE";
		$DB->log($MESSAGE);
		if ($_SESSION["DEBUG"])
		{
			dumper($INFOBJECT);
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
