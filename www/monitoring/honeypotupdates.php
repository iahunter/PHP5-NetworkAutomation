<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(	// Search existing honeypot sensors
		"category"      => "Blackhole",
		"type"          => "Sensor_Hon3yPot",
	);
$SENSORIDS = Information::search($SEARCH);

$SUSPECTS = array();
foreach ($SENSORIDS as $SENSORID)
{
	$SENSOR = Information::retrieve($SENSORID);
	$SUSPECTS = array_merge( $SUSPECTS , $SENSOR->get_hostile_details(500) );
}

$COUNT = count($SUSPECTS);

$WIDTH = array();	$i = 1;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 150;
$WIDTH[$i++]	= 100;
$WIDTH[0]		= array_sum($WIDTH);

$i = 0;
print <<<END
	<table class="report" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<COLGROUP span="1" width="{$WIDTH[$i++]}">
	<thead>
		<caption>Honeypot Attackers: ({$COUNT} displayed)</caption>
		<tr>
			<th class="report">Date</th>
			<th class="report">Suspect</th>
			<th class="report">Country</th>
			<th class="report">Region</th>
			<th class="report">City</th>
			<th class="report">Target</th>
			<th class="report">Protocol</th>
		</tr>
	</thead>
END;

$i = 0;
foreach($SUSPECTS as $SUSPECT)
{
	$DATE	= date("m/d/y H:i:s", strtotime($SUSPECT["date"]));
	$GEOIP = geoip_record_by_name( $SUSPECT["source"] );
	$ROWCLASS = "row" . ( ($i++ % 2) + 1 );
		print <<<END
			<tr class="{$ROWCLASS}">
				<td class="report">{$DATE}					</td>
				<td class="report">{$SUSPECT["source"]}		</td>
				<td class="report">{$GEOIP["country_name"]}	</td>
				<td class="report">{$GEOIP["region"]}		</td>
				<td class="report">{$GEOIP["city"]}			</td>
				<td class="report">{$SUSPECT["target"]}		</td>
				<td class="report">{$SUSPECT["protocol"]}	</td>
			</tr>
END;
}
print "</table>\n";
$size = memory_get_usage(true);
$unit=array('b','kb','mb','gb','tb','pb');
$MEMORYUSED = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
print "<br>Loaded in " . $HTML->timer_diff() . " seconds, " . count($DB->QUERIES) . " SQL queries, " . $MEMORYUSED . " of memory";

?>
