<?php
header("Content-Type: text/plain");
define("NO_AUTHENTICATION",1);    // Do not authenticate requests against this tool
require_once "/etc/networkautomation/networkautomation.inc.php";

$SEARCH = array(
				"category"	=> "Company",
				"type"		=> "Jobsite_Planned",
				);
$RESULTS = Information::search($SEARCH);
$RECORDCOUNT = count($RESULTS);

print "ID	Sitecode	PeakUsers	Length	Value	Estimate	Manweeks	CostPerManweek	CostPercentOfJobValue	PersonalHardware	Software	Communications	Printers	Infrastructure	Labor\n";

foreach ($RESULTS as $ID) {
	$ESTIMATE = Information::retrieve($ID);
	$JUNK = $ESTIMATE->report(); unset($JUNK);
	if ( isset($ESTIMATE->data["itestimatetotal"]) )
	{
		print $ESTIMATE->data["id"]					. "\t";
		print $ESTIMATE->data["sitecode"]			. "\t";
		print $ESTIMATE->data["peakusers"]			. "\t";
		print $ESTIMATE->data["joblength"]			. "\t";
		print $ESTIMATE->data["value"]				. "\t";
		print $ESTIMATE->data["itestimatetotal"]	. "\t";
		print $ESTIMATE->data["totalmanweeks"]		. "\t";
		print $ESTIMATE->data["costpermanweek"]		. "\t";
		print $ESTIMATE->data["costpercentofvalue"]	. "\t";
		foreach($ESTIMATE->estimate_calculate_accounting_totals() as $EST_COST_CODE)
		{
			print $EST_COST_CODE . "\t";
		}
		print "\n";
//		dumper($ESTIMATE->estimate_calculate_accounting_totals() );
		john_flush();
	}else{
		print "ERROR: ID {$ID}\n";
	}
	unset($ESTIMATE);
} // End of foreach, do not remove!
