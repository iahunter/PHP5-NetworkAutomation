<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("IPv6 Address Plan",$HTML->thispage);
print $HTML->header("IPv6 Subnet Tool");


// Check to see if ipv6gen is installed.
$PATH_IPV6GEN = exec("which " . BASEDIR . "/bin/ipv6gen/ipv6gen.pl");
if (preg_match("/not found/",$PATH_IPV6GEN))
{
        print "Error: $PATH_PATH_IPV6GEN<br>\n";
        print "Perhaps you need to install the ipv6gen tool?<br>\n";
	exit($HTML->footer());
}

// Get our variables
if (isset($_GET['prefix'])) { $PREFIX = $_GET['prefix']; }else{ $PREFIX = ""; }
if (isset($_GET['split1'])) { $SPLIT1 = $_GET['split1']; }else{ $SPLIT1 = ""; }
if (isset($_GET['split2'])) { $SPLIT2 = $_GET['split2']; }else{ $SPLIT2 = ""; }


// Print out the form
print "<form name=\"ipv6gen\" method=\"get\" action=\"" . $_SERVER['PHP_SELF'] . "\">\n";
print "Base IPv6 Prefix: \n";
print "<input type=\"text\" size=20 name=\"prefix\" value=\"$PREFIX\">\n";
print "ex. 2620:153::/36<br>\n";

print "First bit length split: <select name=\"split1\">\n";
for ($i = 36; $i <= 64; $i++) {
	if ($i == $SPLIT1)
	{
		print "<option value=\"$i\" selected=\"yes\">$i</option>\n";
	}else{
		print "<option value=\"$i\">$i</option>\n";
	}
}
print "</select><br>\n";

print "Second bit length split: <select name=\"split2\">\n";
for ($i = 36; $i <= 64; $i++) {
	if ($i == $SPLIT2)
	{
		print "<option value=\"$i\" selected=\"yes\">$i</option>\n";
	}else{
		print "<option value=\"$i\">$i</option>\n";
	}
}
print "</select><br>\n";

print "<input type=\"submit\" value=\"Generate Subnets\">
	</form><br><hr style=\"border: 0; color: #ccc; background-color: #aaa; height: 1px;\"><br>\n";


if( !filter_var($PREFIX, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) )
{
	print "Error: {$PREFIX} is not a valid IPv6 address.";
	$PREFIX = "";	// Important, dont run exec on invalid IPs
}

// Check to see if we were passed input.
if ($PREFIX)
{
	if ($SPLIT1 <= 35)	{ print "Error: split1 is too short<br>\n";	}
	if ($SPLIT2 <= $SPLIT1)	{ print "Error: split2 is too short<br>\n";	}
	if ($SPLIT2 >= 64)	{ print "Error: split2 is too long<br>\n";	}

	$COMMAND = $PATH_IPV6GEN." ".$PREFIX." ".$SPLIT1;
	$LEVEL1 = array();
	exec($COMMAND,$LEVEL1);
	$WIDTH = array();
	$WIDTH[1] = 25;  // Level1
	$WIDTH[2] = 25;  // Level2
	$WIDTH[3] = 25;  // /64 subnets
	$WIDTH[3] = 600; // overflow

	$WIDTH[0] = array_sum($WIDTH);

	print "<table class=\"report\" width=".$WIDTH[0].">
	        <caption class=\"report\">Subnet $PREFIX --> $SPLIT1 --> $SPLIT2</caption>
	        <thead>
	                <tr>
	                        <th class=\"report\" width=".$WIDTH[1]." colspan=\"4\">Prefix/Length</th>
	                </tr>
	        </thead>
	        <tbody class=\"report\">\n";
	$i=0;
	$rowclass = "row".(($i % 2)+1);

	$CHILDREN = count($LEVEL1);
	print "<tr class='".$rowclass."'>
		<td width=\"".$WIDTH[1]."\" class=\"report ".$COLOR[1]."\" colspan=\"4\">$PREFIX - split into $CHILDREN /$SPLIT1 subnets</td>
		</tr>\n";

	foreach($LEVEL1 as $L1PREFIX)
	{
		$i++;
		$rowclass = "row".(($i % 2)+1);

		$COMMAND = $PATH_IPV6GEN." ".$L1PREFIX." ".$SPLIT2;
		$LEVEL2 = array();
		exec($COMMAND,$LEVEL2);
		$CHILDREN = count($LEVEL2);

		print "<tr class='".$rowclass."'>
			<td width=\"".$WIDTH[1]."\" class=\"report ".$COLOR[1]."\"></td>
			<td width=\"".$WIDTH[2]."\" class=\"report ".$COLOR[1]."\" colspan=\"3\">$L1PREFIX - split into $CHILDREN /$SPLIT2 subnets</td>
		</tr>\n";

		foreach($LEVEL2 as $L2PREFIX)
		{
			$i++;
			$rowclass = "row".(($i % 2)+1);

			$CHILDREN = pow(2,(64-$SPLIT2));

			print "<tr class='".$rowclass."'>
				<td width=\"".$WIDTH[1]."\" class=\"report ".$COLOR[1]."\"></td>
				<td width=\"".$WIDTH[2]."\" class=\"report ".$COLOR[1]."\"></td>
				<td width=\"".$WIDTH[3]."\" class=\"report ".$COLOR[1]."\" colspan=\"2\">$L2PREFIX - split into $CHILDREN /64 subnets</td>
			</tr>\n";
		}
	}
	print "</table>";
	print "$i lines printed<br>\n";
}
print $HTML->footer();
?>
