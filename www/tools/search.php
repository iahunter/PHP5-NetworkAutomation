<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$STEPSIZE = 50;

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("Search",$HTML->thispage);
print $HTML->header("Search");

print <<<END
		<form name="search" method="get" action="{$_SERVER['PHP_SELF']}">
			Search Devices, Configuration, Hardware, etc: <input type="text" size=30 name="search" value="{$_GET['search']}">
			<input type="submit" value="Search!">
		</form><br>
END;
if (!isset($_GET['search']))
{
	die($HTML->footer());
}else{
	$LOG .= "Search for {$_GET['search']}";
	$DB->log($LOG);
}

$SEARCH = "%" . $_GET['search'] . "%";
$SEARCHES = array(
				array(										// Search for devices by name
					"category"      => "management",
					"type"          => "device_network_%",
					"stringfield0"	=> $SEARCH,
					),
				array(										// Search for devices by running config
					"category"      => "management",
					"type"          => "device_network_%",
					"stringfield5"	=> $SEARCH,
					),
				array(										// Search for devices by version
					"category"      => "management",
					"type"          => "device_network_%",
					"stringfield6"	=> $SEARCH,
					),
				array(										// Search for devices by inventory
					"category"      => "management",
					"type"          => "device_network_%",
					"stringfield7"	=> $SEARCH,
					),
				);
$RESULTS = Information::multisearch($SEARCHES);

//\metaclassing\Utility::dumper($RESULTS);

$RECORDCOUNT = count($RESULTS);
print "Found {$RECORDCOUNT} matching devices (Max {$STEPSIZE} Displayed)";
$OFFSET = $_GET['offset'];
if ($OFFSET+$STEPSIZE < $RECORDCOUNT)
{
	$NEXTSTEP = $OFFSET + $STEPSIZE;
	$SEARCHURL = urlencode($_GET['search']);
	print <<<END
 <a href={$_SERVER['PHP_SELF']}?search={$SEARCHURL}&offset={$NEXTSTEP}>Next {$STEPSIZE} Results</a>
END;
}
print "<br><br>\n";

if ($RECORDCOUNT < 1) { die("No results to display!" . $HTML->footer()); }

$WIDTH = array();	$i = 1;
$WIDTH[$i++]= 100;
$WIDTH[$i++]= 100;
$WIDTH[$i++]= 100;
$WIDTH[$i++]= 200;
$WIDTH[$i++]= 200;
$WIDTH[0]	= array_sum($WIDTH); $i = 0;

print <<<END
	<table table border=0 cellpadding=1 cellspacing=0 width={$WIDTH[$i++]}>
		<tr>
			<th width={$WIDTH[$i++]}>Device ID</th>
			<th width={$WIDTH[$i++]}>Device IP</th>
			<th width={$WIDTH[$i++]}>Mgmt Protocol</th>
			<th width={$WIDTH[$i++]}>Prompt</th>
			<th width={$WIDTH[$i++]}>Model</th>
		</tr>
	</table>
	<div class="accordion ui-widget-content noborder">
END;

$PRINTED = 0;	$SKIPPED = 0;
foreach ($RESULTS as $RESULT)
{
	if ($SKIPPED++ <  $OFFSET)		{ /* print "SKIPPED <= OFFSET";	  /**/ continue; }
	if ($PRINTED++ >= $STEPSIZE)	{ /* print "PRINTED >= STEPSIZE"; /**/ break; }

	$DEVICE		= Information::retrieve($RESULT);
	$ID			= $DEVICE->data["id"		];
	$IP			= $DEVICE->data["ip"		];
	$PROTOCOL	= $DEVICE->data["protocol"	];
	$PROMPT		= $DEVICE->data["name"		];
	$MODEL		= $DEVICE->data["model"		];

	$i = 0;
	print <<<END

		<table border=0 cellpadding=1 cellspacing=0 width={$WIDTH[$i++]}>
			<tr>
				<td width={$WIDTH[$i++]}>{$ID}</td>
				<td width={$WIDTH[$i++]}>{$IP}</td>
				<td width={$WIDTH[$i++]}>{$PROTOCOL}</td>
				<td width={$WIDTH[$i++]}>{$PROMPT}</td>
				<td width={$WIDTH[$i++]}>{$MODEL}</td>
			</tr>
		</table>
		<div class="margin0">
			<div class="tabs" width="{$WIDTH[0]}">
				<ul>
					<li><a href="#tabs-{$DEVICE->data['id']}">{$DEVICE->data["name"]}</a>
END;
	$SHOW_COMMANDS = array( "run","version","inventory","diag","module","interface");
	foreach($SHOW_COMMANDS as $SHOWCMD)
	{
		print <<<END
					<li><a href="/ajax/get_showcommand_by_id.php?id={$ID}&field={$SHOWCMD}&hilight={$_GET['search']}">Show {$SHOWCMD}</a></li>
END;
		$i++;
	}
	print <<<END
				</ul>
				<div id="tabs-{$DEVICE->data['id']}"><a href="/information/information-view.php?id={$DEVICE->data['id']}" class="bluelink">{$DEVICE->data["name"]} ID {$DEVICE->data['id']}</a> was found to have matching show output in the database.</div>
			</div>
		</div>
END;
}

print "</div>\n";
print $HTML->footer();

?>
