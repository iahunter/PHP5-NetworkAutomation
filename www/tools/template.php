<?php

/**
 * tools/template.php
 *
 * Simple text substitution template tool
 *
 * PHP version 5
 *
 * This application is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2008-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
//$HTML->breadcrumb("Tools","/tools");
$HTML->breadcrumb("Template Tool",$HTML->thispage);
print $HTML->header("Template Tool");
$BASE_DIRECTORY = BASEDIR."/TEMPLATES";
$debug = $_SESSION["DEBUG"];
$PAGE_SELF = $_SERVER['PHP_SELF'];

// Get a list of templates to consider...
$HANDLE = opendir($BASE_DIRECTORY); $FILES = array(); while ($FILES[] = readdir($HANDLE)); closedir($HANDLE); sort($FILES);
$TEMPLATES = array(); foreach ($FILES as $FILE) { if ($FILE[0] != "." && $FILE != "") { array_push($TEMPLATES,$FILE); } }

/******************************************************************************************/
/******************************************************************************************/
/******************************************************************************************/
/*  First page,  step 1   */
/******************************************************************************************/
/******************************************************************************************/
/******************************************************************************************/

   if ($_SERVER['REQUEST_METHOD'] != 'POST'){

	//Since they are coming to the first page, clear out all the nosx variables!
	foreach ($_SESSION as $var_name => $var_value) { if (preg_match('/nosx/',$var_name)) { $_SESSION[$var_name]="";} }
?>

<div id="nosx_form">
   <form name="nosx_templatetool" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="nosx_step" value="1">
	  <table border="0" cellspacing="0" cellpadding="1">
		 <tr>
			<td>
Template:<br>
<select name="nosx_template" size="1">
<?php
foreach ($TEMPLATES as $FILE) { print "<option value=\"$FILE\">$FILE</option>\n"; }
?>
</select>
</td></tr><tr><td>
Action:<br>
<select name="nosx_action" size="1">
<option value="run">Fill in a template</option>
<option value="view">View a template</option>
</select>
</td></tr></table>
<br><input type="submit" name="Next" value="Go!">

<?php
   }

/******************************************************************************************/
/******************************************************************************************/
/******************************************************************************************/
/*  Begin processing step 2   */
/******************************************************************************************/
/******************************************************************************************/
/******************************************************************************************/

if ($_SERVER['REQUEST_METHOD'] == "POST" )
{
	//### ALL STEPS VARIABLES ###
	foreach ($_POST as $var_name => $var_value)
	{
		if ( preg_match('/nosx/',$var_name) )
		{
			$_SESSION[$var_name]=$var_value;
		}
	}
	// Increase our step counter every page post
	$_SESSION['nosx_step']++;

	// sanity check that the template we are using exists...
	if ( !in_array($_SESSION["nosx_template"],$TEMPLATES) ) { die("Error: Template not in list...\n" . $HTML->footer() ); }

	//### RE print and store all our previous screens variables! ###
	print "<form name=\"nosx_templatetool\" method=\"post\" action=\"$PAGE_SELF\">\n";
	if ($debug > 3)
	{
		print "<table>\n";
		foreach ($_SESSION as $var_name => $var_value) {
			print "<tr><td>$var_name</td><td>=</td><td>$var_value</tr>\n";
		}
		print "</table>\n";
	}

  ///### Step 2 VIEW action ###

if ($_SESSION['nosx_step'] == 2 & $_SESSION['nosx_action'] == "view" && $_SESSION['nosx_template']!="")
{
	$TEMPLATE = $_SESSION['nosx_template'];
	$TEMPLATE_FILENAME = $BASE_DIRECTORY."/".$TEMPLATE;
		print "<div>\n";
		print "You are viewing $TEMPLATE<br>\n";
		$TEMPLATE_FILEHANDLE = fopen($TEMPLATE_FILENAME, 'r') or die("can't open $TEMPLATE_FILENAME");
		$TEMPLATE_CONTENT = fread($TEMPLATE_FILEHANDLE, filesize($TEMPLATE_FILENAME));
		fclose($TEMPLATE_FILEHANDLE);
	print "<pre>\n";
	print "$TEMPLATE_CONTENT";
	print "</pre>\n";

}

//### Step 2+ RUN action (fill in variables if they arent filled in, and print output) ###

elseif ($_SESSION['nosx_step'] >= 2 && $_SESSION['nosx_action'] == "run" && $_SESSION['nosx_template']!="")
{
	$PATTERN = array();
	$REPLACE = array();
	$SEARCH_REPLACE = array();
	$n = 0;
	$REPLACE_PRINT = "";
	$TEMPLATE = $_SESSION['nosx_template'];
	$TEMPLATE_FILENAME = $BASE_DIRECTORY."/".$TEMPLATE;
	$TEMPLATE_FILEHANDLE = fopen($TEMPLATE_FILENAME, 'r') or die("can't open $TEMPLATE_FILENAME");
	$TEMPLATE_CONTENT = fread($TEMPLATE_FILEHANDLE, filesize($TEMPLATE_FILENAME));
	fclose($TEMPLATE_FILEHANDLE);

	$CHECK_INCLUDE_AGAIN = 1;
	$CHECK_ALREADY_INCLUDED = array();
	$SKIP_IF_BLOCK = 0;
	while ($CHECK_INCLUDE_AGAIN>0)
	{
		$ALL_LINES = explode("\n",$TEMPLATE_CONTENT);
		$TEMPLATE_CONTENT = "";
		$CHECK_INCLUDE_AGAIN = 0;
		foreach ($ALL_LINES as $line) {
			if(preg_match('/^#INCLUDE\s+"(.*)".*/',$line,$reg))
			{
				$INCLUDE_FILENAME = $reg[1];
				if ( !isset($CHECK_ALREADY_INCLUDED[$INCLUDE_FILENAME]) ) { $CHECK_ALREADY_INCLUDED[$INCLUDE_FILENAME] = 0; }
				if ($CHECK_ALREADY_INCLUDED[$INCLUDE_FILENAME] < 100)
				{
					$CHECK_ALREADY_INCLUDED[$INCLUDE_FILENAME]++;
					$INCLUDE_FILENAME = $BASE_DIRECTORY.".INCLUDE/".$INCLUDE_FILENAME;
					$INCLUDE_FILEHANDLE = fopen($INCLUDE_FILENAME, 'r') or die("can't open $INCLUDE_FILENAME");
					$INCLUDE_CONTENT = fread($INCLUDE_FILEHANDLE, filesize($INCLUDE_FILENAME));
					fclose($INCLUDE_FILEHANDLE);
					$TEMPLATE_CONTENT = $TEMPLATE_CONTENT.$INCLUDE_CONTENT;
					$CHECK_INCLUDE_AGAIN++;
				}else{
					print "<pre>Warning: Duplicate recursion of $INCLUDE_FILENAME detected. Skipping...</pre>\n";
				}
//				next;
			}else{
				$TEMPLATE_CONTENT = $TEMPLATE_CONTENT.$line."\n";
			}
		}
	}
	print "<div\n";
	print "The template $TEMPLATE has the following fields to substitute:<br><br><TABLE bgcolor=BBBBB>\n";
	$TEMPLATE_LINES = explode("\n",$TEMPLATE_CONTENT);
	$SKIP_IF_BLOCK = 0;
	$PRINT_OUTPUT = 1;
	$PROMPTED_VARS = array();
	$PRINTED_VARS = array();
	foreach ($TEMPLATE_LINES as $line) {
		if ($SKIP_IF_BLOCK > 0) {
			if(preg_match('/^#ENDIF.*/',$line,$reg)) {$SKIP_IF_BLOCK--;}
			if(preg_match('/^#ELSE.*/',$line,$reg) && $SKIP_IF_BLOCK == 1) {$SKIP_IF_BLOCK--;}
			if(preg_match('/^#IF\s+.*/',$line,$reg)) {$SKIP_IF_BLOCK++;}
//			next;
		}elseif(preg_match('/^#ELSE.*/',$line,$reg) && SKIP_IF_BLOCK == 0)
		{
			$SKIP_IF_BLOCK++;
		}elseif(preg_match('/^#IF\s+(\$[a-zA-Z0-9_-]+)\s+"(.*)".*/',$line,$reg))
		{
			$compare_var = $reg[1]; $compare_val = $reg[2];
			$varsessionkey = "nosx_var_".$compare_var;
			$varvalue = $_SESSION[$varsessionkey];
			if ($varvalue != $compare_val) { $SKIP_IF_BLOCK++;}
//			next;
		}elseif(preg_match('/^#IF\s+(\$[a-zA-Z0-9_-]+)\s+([!<=>]+)\s+"(.*)".*/',$line,$reg))
		{
			$compare_var = $reg[1]; $compare_operator = $reg[2]; $compare_val = $reg[3];
			$varsessionkey = "nosx_var_".$compare_var;
			$varvalue = $_SESSION[$varsessionkey];

			#IF the variable we are comparing to is undefined, the IF must always fail.
			if ($varvalue == "") { $SKIP_IF_BLOCK++; /*next;/**/ }

			if ($compare_operator == "=" ) { if ($varvalue == $compare_val)         {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == "==") { if ($varvalue == $compare_val)         {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == "!=") { if ($varvalue != $compare_val)         {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == ">=") { if ($varvalue >= intval($compare_val)) {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == "<=") { if ($varvalue <= intval($compare_val)) {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == ">" ) { if ($varvalue >  intval($compare_val)) {} else { $SKIP_IF_BLOCK++;} }
			if ($compare_operator == "<" ) { if ($varvalue <  intval($compare_val)) {} else { $SKIP_IF_BLOCK++;} }

			//###INCLUSIVE AND EQUAL logic
			if ($compare_operator == ">=<=" && preg_match('/(.+)\s+(.+)/',$compare_val,$reg2))
			{
				$compare_gt = $reg2[1]; $compare_lt = $reg2[2];
				if ($varvalue >= intval($compare_gt) && $varvalue <= intval($compare_lt)) {} else { $SKIP_IF_BLOCK++;}
			}
//			next;
		}elseif(preg_match('/^#(\$[a-zA-Z0-9_-]+)\s+"(.*)"\s+"(.*)"\s+(\w+)\s+"(.*)"\s*/',$line,$reg))
		{
			if ($debug > 5)	{print "<pre>\n";print "$reg[1]\n";print "$reg[2]\n";print "$reg[3]\n";print "$reg[4]\n";print "$reg[5]";print "</pre>\n";}
			$variable = $reg[1]; $varname = $reg[2]; $vardesc = $reg[3]; $vartype = $reg[4]; $varbounds = $reg[5];
			$varsessionkey = "nosx_var_".$variable;
		//###If the variable has no value, we need to ask the user for it! but dont ask them twice.
			if ($_SESSION[$varsessionkey]=="" && $PROMPTED_VARS[$varsessionkey]!=1)
			{
				$PROMPTED_VARS[$varsessionkey]=1;
				if ($varname!="") { print "<tr><td><b>$varname:</td><td>"; }
				if($vartype=="text") 
				{
					$PROMPTED_VARS[$varsessionkey]=1;
					print "<input type=\"text\" name=\"nosx_var_$variable\"> </td>";
					$PRINT_OUTPUT = 0;
				}elseif($vartype=="select"){
					$PROMPTED_VARS[$varsessionkey]=1;
					$varboundlist = explode(" ", $varbounds);
					print "<select name=\"nosx_var_$variable\">";
					    // Lets put a Default Null for option #1
                                                print "<option value=></option>";
					foreach ($varboundlist as $varbound)
					{
						print "<option value=\"$varbound\">$varbound</option>";
					}
					print "</select></td>";
					$PRINT_OUTPUT = 0;
				}elseif($vartype=="hidden")
				{
					print "<input type=\"hidden\" name=\"nosx_var_$variable\" value=\"$varbounds\"></td></tr>";
					$PRINT_OUTPUT = 0;
				}elseif($vartype=="static")
				{
					$nosxized_varname = "nosx_var_".$variable;
					$_SESSION[$nosxized_varname]=$varbounds;
					$varvalue = $_SESSION[$varsessionkey];
					$varregex = "/\\".$variable."/";
					$SEARCH_REPLACE[$varregex] = $varvalue;
					print "</td>";
				}else{
					print "$variable has type \"$vartype\" which is unknown!<br>\n</td>";
				}
				if ($vardesc!="") { print "</b><td> ($vardesc)<br>\n</td></tr>"; }
			}
			//###If the variable has a value, we set it in the structure!
			if ($_SESSION[$varsessionkey]!="" && $PRINTED_VARS[$varsessionkey]!=1)
			{
				$PRINTED_VARS[$varsessionkey] = 1;
				$varvalue = $_SESSION[$varsessionkey];
				$varregex = "/\\".$variable."/";
				$SEARCH_REPLACE[$varregex] = $varvalue;
				if ($varname != "") {
					print "<tr><td><b>$varname:</b></td><td>$varvalue</td></tr>\n";
				}
			}
		}elseif ($line[0]=='#') {
//			next;
		}else{
			if ($PRINT_OUTPUT==0) { /*next;/**/ }

			ksort($SEARCH_REPLACE);
			$SEARCH_REPLACE = array_reverse($SEARCH_REPLACE);
			foreach($SEARCH_REPLACE as $PATTERN => $REPLACE)
			{
				$line = preg_replace($PATTERN,$REPLACE,$line);
			}
			$REPLACE_PRINT = $REPLACE_PRINT.$line."\n";
		}
	}
	if ($PRINT_OUTPUT==1)
	{
		print "</table><br><pre>$REPLACE_PRINT</pre>\n";
		$MESSAGE = "TEMPLATE GENERATED ".$_SESSION['nosx_template'];
		ob_start();
		var_dump($_SESSION);
		$DETAILS = ob_get_clean();
		$DB->log($MESSAGE,0,$DETAILS);
	}else{
		print "</TABLE><br><input type=\"submit\" name=\"Submit\" value=\"Next\"></center><br>\n";
	}
	if ($debug > 3){print "<pre>\n";print "$TEMPLATE_CONTENT";print "</pre>\n";}
}else{
	print "<b>Error: session data is inconsistent.</b><br><br>You may have hit the back button and reloaded the page or used the same window to submit another form on this site.<br><br>Please restart this session.\n";
}

}
print "</form></div>\n";

if ($_SESSION['nosx_step'] >= 1)
{
	print $HTML->footer("Restart",$HTML->thispage);
}else{
	print $HTML->footer();
}
?>
