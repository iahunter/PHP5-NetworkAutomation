<?php
// This generates a nosiy error and log if the permission check fails.
function PERMISSION_REQUIRE($PERMISSION)
{
    if (!PERMISSION_CHECK($PERMISSION))
    {
        $MESSAGE = "UNAUTHORIZED ACCESS BY {$_SESSION["AAA"]["username"]} PERMISSION {$PERMISSION}";
        global $DB;
        $DB->log($MESSAGE);
        trigger_error($MESSAGE);
        print "Error: Your user session lacks the permission '{$PERMISSION}' required to perform this action.<br>\n";
        global $HTML;
        die($HTML->footer());
    }
}

// This returns true of ["AAA"]["permission"]["$PERMISSION"] --OR-- ["AAA"]["permission"]["WILD*CARD"] matches permission!
function PERMISSION_CHECK($PERMISSION)
{
    if (isset($_SESSION["AAA"]["permission"][$PERMISSION])) { return $_SESSION["AAA"]["permission"][$PERMISSION]; }
    // If a simple match fails, try the wildcard match against every permission element!
    foreach ($_SESSION["AAA"]["permission"] as $KEY => $VALUE)
    {
        $MATCH = "/$KEY/i";
        if (preg_match($MATCH,$PERMISSION,$REG)) { return $VALUE; }else{
            if ($_SESSION["DEBUG"] > 8)
            {
                print "<pre>$MATCH does not match $PERMISSION</pre>\n";
            }
        }
    }
    return false;
}

