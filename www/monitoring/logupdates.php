<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

PERMISSION_REQUIRE("tool.log");

$USER = "%"; if (isset($_GET['user'])) { $USER = "{$_GET['user']}"; }
$TOOL = "%"; if (isset($_GET['tool'])) { $TOOL = "{$_GET['tool']}"; }

$QUERY = <<<END
	SELECT * FROM log
	WHERE level <= :DEBUG
	AND user like :USER
	AND tool like :TOOL
	ORDER BY id DESC
	LIMIT 200
END;

global $DB;
$DB->query($QUERY);
try {
	$DB->bind("DEBUG",$_SESSION["DEBUG"]);
	$DB->bind("USER",$USER);
	$DB->bind("TOOL",$TOOL);
	$DB->execute();
	$RESULTS = $DB->results();
} catch (Exception $E) {
	$MESSAGE = "Exception: {$E->getMessage()}";
	trigger_error($MESSAGE);
	die($MESSAGE);
}

$RECORDCOUNT = count($RESULTS);

$WIDTH = array();
$WIDTH[1] = 30;
$WIDTH[2] = 120;
$WIDTH[3] = 200;
$WIDTH[4] = 200;
$WIDTH[5] = 40;
$WIDTH[6] = 700;
$WIDTH[0] = array_sum($WIDTH);

$i = 0;

print <<<END
<table class="report" width="{$WIDTH[$i++]}">
	<caption>Tool Activity (max 200 Displayed)</caption>
	<thead>
		<tr>
			<th width="{$WIDTH[$i++]}">ID</th>
			<th width="{$WIDTH[$i++]}">Date</th>
			<th width="{$WIDTH[$i++]}">User</th>
			<th width="{$WIDTH[$i++]}">Tool</th>
			<th width="{$WIDTH[$i++]}">Level</th>
			<th width="{$WIDTH[$i++]}">Description</th>
		</tr>
	</thead>
	<tbody class="report">
END;

$USER_HITS = array();
$TOOL_HITS = array();

$i = 0;
foreach($RESULTS as $RECORD)
{
	$DATE		= date("m/d/y H:i:s", strtotime($RECORD['date']));
	$REALNAME	= $LDAP->user_to_realname($RECORD['user']);
	$ROWCLASS = "row".(($i++ % 2)+1);
	print <<<END
	<tr class="{$ROWCLASS}">
		<td width="{$WIDTH[$i++]}" class="report">{$RECORD['id']}				</td>
		<td width="{$WIDTH[$i++]}" class="report">{$DATE}</td>
		<td width="{$WIDTH[$i++]}" class="report"><a href="/monitoring/log.php?user={$RECORD['user']}">{$REALNAME}</a></td>
		<td width="{$WIDTH[$i++]}" class="report"><a href="/monitoring/log.php?tool={$RECORD['tool']}">{$RECORD['tool']}</a></td>
		<td width="{$WIDTH[$i++]}" class="report">{$RECORD['level']}</td>
		<td width="{$WIDTH[$i++]}" class="report">{$RECORD['description']}</td>
	</tr>
END;
}

print <<<END

	</tbody>
</table><br>
END;

$size = memory_get_usage(true);
$unit=array('b','kb','mb','gb','tb','pb');
$MEMORYUSED = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
print "<br>Loaded in " . $HTML->timer_diff() . " seconds, " . count($DB->QUERIES) . " SQL queries, " . $MEMORYUSED . " of memory";
?>
