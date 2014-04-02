<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$MAX_LINES = 100;
//trigger_error("TESTING asdf");
//error_reporting(E_ALL);
////////////////////////////////////////////////////////////////

$WIDTH = array();
$WIDTH[1] = 220;
$WIDTH[2] = 140;
$WIDTH[3] = 1200;
$WIDTH[0] = array_sum($WIDTH);

$MAXREAD = 1024000;
$LOGFILE = "/var/log/apache2/error.log";
$FILESIZE = filesize($LOGFILE);
$FILESIZEOFFSET = $FILESIZE - $MAXREAD; if ($FILESIZEOFFSET < 0) { $FILESIZEOFFSET = 0; }
if ($FILESIZE < $MAXREAD) { $FILESIZEREAD = $FILESIZE; }else{ $FILESIZEREAD = $MAXREAD; }
$LOG = file_get_contents($LOGFILE,FALSE,NULL,$FILESIZEOFFSET,$FILESIZEREAD);
$LOGLINES = explode("\n", $LOG);
/*
$OFFSET = count($LOGLINES) - $MAX_LINES - 1;
$LOGLINES = array_slice($LOGLINES,$OFFSET,$MAX_LINES);
/**/
$LOGLINES = array_reverse($LOGLINES);
$LOGCOUNT = count($LOGLINES);

$i = 0;
print <<<END
<table class="report" width="{$WIDTH[$i++]}">
    <caption>Read {$LOGCOUNT} Logs {$FILESIZEREAD} bytes from {$LOGFILE} {$FILESIZE} bytes</caption>
    <thead>
        <tr>
            <th width="{$WIDTH[$i++]}">Date</th>
            <th width="{$WIDTH[$i++]}">Client</th>
            <th width="{$WIDTH[$i++]}">Message</th>
        </tr>
    </thead>
    <tbody class="report">
END;

$i = 0;
foreach ($LOGLINES as $LOGLINE)
{
    $PATTERN = "/\[(.*?)\..*\] \[.*:(.*?)\] \[(.*?)\] \[client (.*?)\] (.*)/";
    if (preg_match($PATTERN, $LOGLINE, $REG))
    {
		$DATE		= $REG[1];
        $LEVEL      = $REG[2];
        $CLIENT     = $REG[4];
        $MESSAGE    = $REG[5];
        $ROWCLASS = "row".(($i++ % 2)+1);
        print <<<END
        <tr class="{$ROWCLASS}">
            <td class="report">{$DATE}</td>
            <td class="report">{$CLIENT}</td>
            <td class="report">{$MESSAGE}</td>
        </tr>
END;
    }else{
		if (strlen($LOGLINE) > 0)
		{
			print <<<END
        <tr class="{$ROWCLASS}">
            <td class="report">No Match</td>
            <td class="report"></td>
            <td class="report">{$LOGLINE}</td>
        </tr>
END;
		}
	}
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

