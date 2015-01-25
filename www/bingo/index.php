<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
print $HTML->header("Buzzword Bingo");

$TABLEROWS	= 5;	// 5 rows
$TABLECOLS		= 5;	// 5 columns

$WORDS = "Free Space!
(Re)Define
Administration
Adopt(ion)
Architecture
Artifacts
Assess(ment)
Awareness
Best in class / of breed
Brand loyalty
Business
Capacity
Challenges
Classify
Cloud
Compliance
Controls
Customers
Deploy
Disruptive technology
Efficient
End to end
Environment
Field
Fine-tune
Flesh-out
Formalize
Foundation
Gather requirements
Governance
Growth
Guidance
Guiding principals
Impact
Initiate
Integration
Jobsite
Leverage
Mapping
Marketing
Measure
Measurement
Mentality
Metadata
Metric(s)Products
Mobility
Model
Next Gen(eration)
Opportunity
Organization
Over arching
Performance Collaborate
Plan
Playbook
Process
Project
Promote
Quality
Reduce Cost
Relationship
Roadmap
Seven red perpendicular lines
Solution(s)
Standard
Stewardship
Summit
Super-user
Team
The Business
The Field
Transition
UI/UX
Up front
Value
Vision
_____ as a service";

$WORD_ARRAY = preg_split( '/\r\n|\r|\n/', $WORDS );
$WORDRANGE = range(1,count($WORD_ARRAY) - 1);
shuffle($WORDRANGE);
$MIDDLECELL = ( ( $TABLEROWS * $TABLECOLS ) - 1 ) / 2;
$WORDRANGE[$MIDDLECELL] = 0;	// 0 is the first word, always "free cell".


$CELLSIZE = 150;
$TABLEWIDTH		= $TABLECOLS * $CELLSIZE;
$TABLEHEIGHT	= $TABLEROWS * $CELLSIZE;

if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 1 )
{
	//dumper($WORD_ARRAY);
	$WORDCOUNT = count($WORD_ARRAY);
	print "Count of array: $WORDCOUNT<br>\n";
	dumper($WORDRANGE);
	print "Word array: \n";
	print "Shuffled range: \n";
}

$i = 0;

print <<<END
	<div style="table; width: {$TABLEWIDTH}px; border: 1px solid;">
END;

foreach(range(0,$TABLEROWS - 1) as $R)
{
	print <<<END
		<div style="display: table-row;">
END;
	foreach(range(0,$TABLECOLS - 1) as $C)
	{
		print <<<END
			<div style="display: table-cell; height: {$CELLSIZE}px; width: {$CELLSIZE}px; padding: 5px; border: 1px solid; text-align: center; vertical-align: middle; font-weight: bold;">{$WORD_ARRAY[ $WORDRANGE[$i++] ]}</div>
END;
	}
print <<<END
		</div>
END;
}
print <<<END
	</div>
END;


print $HTML->footer();

?>
