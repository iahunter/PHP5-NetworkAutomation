<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Reports","/reports");
$HTML->breadcrumb("Site Service Report",$THISPAGE);
print $HTML->header("Site Service Report");

// Build our list of sites
    $QUERY = <<<END
		SELECT DISTINCT SUBSTRING(stringfield0,1,8) AS sitecode FROM information
		WHERE category LIKE 'Management'
		AND type LIKE 'Device_Network_Cisco%'
		AND active = 1
		HAVING sitecode RLIKE '[a-z]{5}[a-z0-9]{3}'
		ORDER BY sitecode ASC
END;
	// LEGACY QUERY: select distinct substring(prompt,1,8) as sitecode from device having sitecode rlike '[a-z]{5}[a-z0-9]{3}' order by sitecode asc
	$DB->query($QUERY);
	try {
		$DB->execute();
		$RESULTS = $DB->results();
	} catch (Exception $E) {
		$MESSAGE = "Exception: {$E->getMessage()}";
		trigger_error($MESSAGE);
		die($MESSAGE . $HTML->footer());
	}
$SITES = array(); foreach($RESULTS as $KEY => $RESULT) { array_push($SITES,$RESULT["sitecode"]); }

$SERVICES = array('verizon','centurylink','telus','internet','ezvpn','flexvpn');

//dumper($SITES);

print <<<END
	<table class="report">
	<caption class="report">Site Services (Scanned $SITECOUNT Sites)</caption>
	<thead>
		<tr>
			<th class="report">Site</th>
END;
foreach($SERVICES as $SERVICE)
{
	print <<<END
			<th class="report">$SERVICE</th>
END;
}
print <<<END
		</tr>
	</thead>
	<tbody class="report">
END;

$i = 1;
foreach($SITES as $SITE)
{
	$ROWCLASS = "row".(($i++ % 2)+1);
	print <<<END
		<tr class="{$ROWCLASS}">
			<td class="report">$SITE</td>
END;
	foreach ($SERVICES as $SERVICE)
	{
		print <<<END
			<td class="report" width="49" background="/ajax/sitequery.png.php?site=$SITE&service=$SERVICE" style="background-repeat: no-repeat; background-position: left;"></td>
END;
	}
	print <<<END
		</tr>
END;

}
print <<<END
		</tbody>
	</table>
END;
print $HTML->footer();
?>


