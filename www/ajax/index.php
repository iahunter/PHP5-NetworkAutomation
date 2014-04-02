<?php
require_once "/etc/networkautomation/networkautomation.inc.php";
$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("AJAX","/ajax");

print $HTML->header("jQuery AJAX Test");
?>

<?php
	$js = new JS;

		print "<div id='dialog' style='display: none'>\n";
		print "<div id='message' style='font-size: 14px;'></div><br>\n";
		print $js->progressbar('progressbar');
		print "<div id='confirm' style='display: none' align=right>";
		print "<input type=button id=confirm value='Ok' ".
				"onClick='javascript:$(&quot;#dialog&quot;).dialog(&quot;close&quot;);'></div>\n";
		print "</div>\n";
		print $js->dialog('dialog','init',array('title' => 'Processing...', 'no.close' => 1,
						        'height' => 240,'width' => 650));
		print $js->progressbar('progressbar','init');

		$i = 0;

		print $js->html('message','Doing work...');
		print $js->progressbar('progressbar','animateprogress',array('value' => 25,'duration' => 2000));
		john_flush();
		sleep(3);  //do work

		print $js->html('message','Doing HARD work...');
		print $js->progressbar('progressbar','animateprogress',array('value' => 50,'duration' => 4000));
		john_flush();
		sleep(4);  //do hard work

		print $js->html('message','Doing easy work...');
		print $js->progressbar('progressbar','animateprogress',array('value' => 75,'duration' => 500));
		john_flush();
		sleep(2); //do easy work

		// Successful deployment of changes
		//=================================
		print $js->html('message','Work Complete');
		print $js->progressbar('progressbar','animateprogress',array('value' => 100,'duration' => 1000));
		john_flush();
		sleep(1);

		print $js->dialog('dialog','overlay',array('overlay.color' => '009900'));
		print $js->dialog('dialog','title',array('title' => 'Successful changes',
							 'title.color' => 'ffffff',
							 'title.bg.color' => '009900'));

		print $js->progressbar('progressbar','hide');
		print $js->show('confirm');
?>
This was a jQuery test with animation and progress bars.
<?php
print $HTML->footer();
?>
