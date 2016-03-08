<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
print $HTML->header("Network Engineering Tools");

$BLOCKS = array();

if (PERMISSION_CHECK("tool.apache")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Apache",
		"/server-status",
		"/images/apache.png",
		array(
			"Apache server status - worker threads"
		)
	));
}

if (PERMISSION_CHECK("tool.supervisor")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Supervisor",
		"http://netman:9001/",
		"/images/supervisord.png",
		array(
			"Gearman worker thread supervisor server"
		)
	));
}

if (PERMISSION_CHECK("tool.gearman")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Gearman",
		"/gearman",
		"/images/gearman.png",
		array(
			"Gearman job queue, workers, and servers"
		)
	));
}

if (PERMISSION_CHECK("tool.log"))
{
	array_push($BLOCKS,$HTML->featureblock(
		"Tool Activity Log",
		"/monitoring/log.php",
		"/images/videocamera.png",
		array(
			"Audit trail & access log"
		)
	));
}

if (PERMISSION_CHECK("debug")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Toggle Debug",
		"/debug.php",
		"/images/debug.png",
		array(
			"Toggle debug session variable",
			"Display additional output from other tools"
		)
	));
}

if (PERMISSION_CHECK("tool.ldap")) {
	array_push($BLOCKS,$HTML->featureblock(
		"LDAP Tool",
		"/tools/ldap.php",
		"/images/ldap.png",
		array(
			"Dump LDAP information from active directory, user groups and attributes"
		)
	));
}

if (PERMISSION_CHECK("tool.template")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Template Tool",
		"/tools/template.php",
		"/images/tools2.png",
		array(
		"Template Tool",
		"Nothing Else Currently"
	)
	));
}

if (PERMISSION_CHECK("tool.switch.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Switch Viewer",
		"/tools/switch-viewer.php",
		"/images/gnome-session-switch.png",
		array(
			"Realtime Access Switch",
			"Port Information & Changes"
		)
	));
}

if (PERMISSION_CHECK("tool.racktables")) {
	array_push($BLOCKS,$HTML->featureblock(
		"RackTables",
		"/racktables",
		"/images/greentech.png",
		array(
			"Datacenter Infrastructure Manager",
			"Datacenter Rows, Racks, Equipment"
		)
	));
}

if (PERMISSION_CHECK("tool.search")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Network Search",
		"/tools/search.php",
		"/images/gtk_find_and_replace.png",
		array(
			"Search the network device database",
			"Device information by name, config,",
			"hardware, or interface criteria"
		)
	));
}

if (PERMISSION_CHECK("report.iosversion")) {
	array_push($BLOCKS,$HTML->featureblock(
		"IOS Version Report",
		"/reports/ios-version-report.php",
		"/images/version-icon.png",
		array(
			"Version information for",
			"network devices globally"
		)
	));
}

if (PERMISSION_CHECK("tool.diff")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Config Comparison",
		"/tools/diff.php",
		"/images/cherrys.png",
		array(
			"Differential comparison of",
			"network device configuration"
		)
	));
}

if (PERMISSION_CHECK("report.siteservice")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Site Service Report",
		"/reports/site-service-report.php",
		"/images/site-services.png",
		array(
			"Services listed by sitecode",
			"WAN, VPN, Internet, etc."
		)
	));
}

if (PERMISSION_CHECK("information.management.device")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Inaccessible Device Report",
		"/reports/inaccessible-devices.php",
		"/images/agt_stop_256.png",
		array(
			"Devices with management problems"
		)
	));
}

if (PERMISSION_CHECK("information.mpls.vpn.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"MPLS VPN Information",
		"/information/information-list.php?category=MPLS&type=VPN",
		"/images/cluster.png",
		array(
				"MPLS VPN Database",
				"Route Targets & VPN ID's"
		)
	));
}

if (PERMISSION_CHECK("information.bgp.asn.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"BGP Autonomous Systems",
		"/information/information-list.php?category=BGP&type=ASN",
		"/images/folder_burned_system.png",
		array(
				"BGP ASN Database",
				"ASN to Device map report"
		)
	));
}

if (PERMISSION_CHECK("monitoring.bgp")) {
	array_push($BLOCKS,$HTML->featureblock(
		"BGP Update Monitor",
		"/monitoring/bgp.php",
		"/images/globearrows.png",
		array(
				"Realtime BGP update monitoring",
				"Historical routing change search"
		)
	));
}

if (PERMISSION_CHECK("information.ipplan.block.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"IPv4 Planning Information",
		"/information/information-view.php?id=45",
		"/images/ipv4plan.png",
		array(
				" Address space hierarchy planning",
				"Tracking blocks, networks, addresses",
		)
	));
}

if (PERMISSION_CHECK("information.ipplan.block6.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"IPv6 Planning Information",
		"/information/information-view.php?id=891682",
		"/images/ipv6plan.png",
		array(
				"Address space hierarchy planning",
				"Tracking blocks, networks, addresses",
		)
	));
}

if (PERMISSION_CHECK("tool.ipv6plan")) {
	array_push($BLOCKS,$HTML->featureblock(
		"IPv6 Subnet Planning Tool",
		"/tools/ipv6-plan.php",
		"/images/ipv6badge.png",
		array(
				"IPv6 two-tier subnet calculator",
				"Address space hierarchy planning"
		)
	));
}

if (PERMISSION_CHECK("websvn.configrepo")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Configuration Repository",
		"/websvn/",
		"/images/cydiapackage.png",
		array(
				"Network config revision tracking",
				"THIS WILL TAKE TIME TO LOAD!",
				"Please be patient...",
		)
	));
}

if (PERMISSION_CHECK("information.datacenter.site.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Datacenter VLAN Usage",
		"/information/information-list.php?category=Datacenter&type=Site",
		"/images/datacenter-icon.png",
		array(
				"VLAN utilization and distribution block tracking across datacenters",
		)
	));
}

if (PERMISSION_CHECK("information.provisioning.site.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Network Provisioning",
		"/information/information-list.php?category=Provisioning&type=Site",
		"/images/provisioning-icon.png",
		array(
			"Network Device Provisioning",
			"Track Devices and Generate Config"
		)
	));
}

if (PERMISSION_CHECK("information.security..view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Security Provisioning",
		"/information/information-list.php?category=Security&type=Index",
		"/images/firewall1.png",
		array(
			"Firewall Provisioning",
			"Track Applications & Hosts"
		)
	));
}

if (PERMISSION_CHECK("information.equipment.terminalserver.view")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Serial Terminal Servers",
		"/information/information-list.php?category=Equipment&type=TerminalServer",
		"/images/remote_desktop_icon.png",
		array(
			"Out of band serial terminal servers",
			"List of 3g and wired equipment"
		)
	));
}

if (PERMISSION_CHECK("monitoring.honeypot")) {
	array_push($BLOCKS,$HTML->featureblock(
		"Honeypot Monitor",
		"/monitoring/honeypot.php",
		"/images/honeypot.png",
		array(
			"Live updated list attackers, targets, and protocols",
		)
	));
}

// Let anybody run the speedtest
if (true) {
	array_push($BLOCKS,$HTML->featureblock(
		"Speed Test",
		"/speedtest/",
		"/images/speedtest.png",
		array(
			"Measure Upload and Download Speed",
			"",
			"This test may not accurately reflect available bandwidth due to browser and computer settings"
		)
	));
}

//	<b>All is well. Nothing is broke.</b><br><br>
print <<<END
	<div style="table; width: 900px;">
		<div style="display: table-row;">
END;

print "<h3>Certificate based authentication is now active for this website!</h3>\n";
if ($_SESSION["AAA"]["authenticated"] == "PKI")
{
	print "You were authenticated automagically by public key cryptography!<br><br>\n";
}else{
	print "Your browser did not present a user certificate, enroll <a href=\"https://knecasiwp001/certsrv/\">here</a> to enjoy automated password-free authentication!<br><br>\n";
}
if (count($BLOCKS) == 1)
{
	print "<h3>You are not currently a member of any active directory groups that grant permissions to this system</h3>\n";
}
$i = 0;
foreach($BLOCKS as $BLOCK)
{
	if (!($i++ % 3))
	{
		print <<<END

		</div>
	</div>
	<div style="table; width: 900px;">
		<div style="display: table-row;">
END;
	}
	print <<<END

			<div style="display: table-cell; padding: 5px;">$BLOCK</div>
END;
}
print <<<END

		</div>
		<div style="display: table-row">
			<div style="display: table-cell; padding: 5px;">
				<div>
					<a href="mailto:the.admin@company.com?subject=Tool Bug Report"><img src="/images/bug.gif"></a>
				</div>
			</div>
		</div>
	</div>
END;
if ($_SESSION["DEBUG"] > 3)
{
	$DEBUGOUTPUT .= $HTML->hr();
	$DEBUGOUTPUT .= "You have been granted the following permissions:<br>";
	$DEBUGOUTPUT .= \metaclassing\Utility::dumperToString($_SESSION["AAA"]["permission"] );
//	$DEBUGOUTPUT .= $SESSION->garbage(3600);
	print $DEBUGOUTPUT;
}
print $HTML->footer();
?>
