
<?php

/**
 * include/libtravis.inc.php
 *
 * Common library of use(less/ful) functions,  common theme is voice centric integration with callmangler
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
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
 * @author    John Lavoie, Travis Riesenberg
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

// SSH into a callmanager admin CLI and execute a command to collect phone information (CSV with leading and trailing horsecrap)
function ssh_get_phones($CALLMANAGER)
{
	$COMMAND = "show risdb query phone\n";

	$IP = $CALLMANAGER;	$PORT = 22;
	require_once "command/phpseclib/Net/SSH2.php";
	$SSH = new Net_SSH2($IP,$PORT);
	if ( !$SSH->login(CALLMGR_USER,CALLMGR_PASS) ) { die("SSH Login Failed!"); }

	$OUTPUT = "";

//	print "Waiting for CLI, discarding banner motd...\n";
	$OUTPUT .= $SSH->read("admin:");	// Wait for the prompt!
	$SSH->write($COMMAND);				// Get the output of our command

	$OUTPUT = "";
//	print "Waiting for OUTPUT...\n";
	$OUTPUT .= $SSH->read("admin:");	// Wait for the prompt!

//	print "Returning!\n";
	return $OUTPUT;
}

// SSH into a callmanager admin CLI and execute a command to collect extension information (CSV with leading and trailing horsecrap)
function ssh_get_extensions($CALLMANAGER)
{
	$COMMAND = "show risdb query phoneextn\n";

	$IP = $CALLMANAGER;	$PORT = 22;
	require_once "command/phpseclib/Net/SSH2.php";
	$SSH = new Net_SSH2($IP,$PORT);
	if ( !$SSH->login(CALLMGR_USER,CALLMGR_PASS) ) { die("SSH Login Failed!"); }

	$OUTPUT = "";

//	print "Waiting for CLI, discarding banner motd...\n";
	$OUTPUT .= $SSH->read("admin:");	// Wait for the prompt!
	$SSH->write($COMMAND);				// Get the output of our command

	$OUTPUT = "";
//	print "Waiting for OUTPUT...\n";
	$OUTPUT .= $SSH->read("admin:");	// Wait for the prompt!

//	print "Returning!\n";
	return $OUTPUT;
}

// Special parsing for the phone command CSV run above with trimmings
function callmanager_parse_phones($PHONELIST)
{
	$RETURN = array();
	$LINES = explode("\r\n",$PHONELIST);
	$LINES = array_slice($LINES,5);	// Lop off the first 5 lines.
	array_pop($LINES); array_pop($LINES); array_pop($LINES); array_pop($LINES); // Lop off the last 4 lines
	foreach ($LINES as $LINE)
	{
		$LINE = trim($LINE);	//print "$LINE\n";
		if ( preg_match('/^DeviceName.*/',$LINE,$REG))	// This line is the field headers
		{
			$HEADER = str_getcsv($LINE);
			foreach ($HEADER as $KEY => $VALUE)
			{
				$HEADER[$KEY] = trim(preg_replace("/\#/","num"	,$HEADER[$KEY]) );	// IF the header contains #, replace it with "num"...
				$HEADER[$KEY] = trim(preg_replace("/\ /",""		,$HEADER[$KEY]) );	// IF the header contains SPACE, REMOVE it!
//				print "HEADER: {$HEADER[$KEY]}\n";
			}
		}elseif ( preg_match('/^.+,.+,.+/',$LINE,$REG))	// See if its a phone data line
		{
			$PHONERECORD = str_getcsv($LINE);
			$i = 0;	$PHONE = array();
			foreach ($PHONERECORD as $JUNK) { $PHONE[$HEADER[$i]] = trim($PHONERECORD[$i]); $i++; }
			if ( count($HEADER) == count($PHONE) )	// Only count reocrds with the RIGHT NUMBER OF FIELDS!
			{
				array_push($RETURN,$PHONE);
			}else{
				print "RECORD FAILED TO PARSE!\n{$LINE}\n";
			}
		}else { continue; }
	}
	return $RETURN;
}

// Special parsing for the extension command CSV run above with trimmings
function callmanager_parse_extensions($EXTLIST)
{
	$RETURN = array();
	$LINES = explode("\r\n",$EXTLIST);
	$LINES = array_slice($LINES,5);	// Lop off the first 5 lines.
	array_pop($LINES); array_pop($LINES); array_pop($LINES); array_pop($LINES); // Lop off the last 4 lines
	foreach ($LINES as $LINE)
	{
		$LINE = trim($LINE);	//print "$LINE\n";
		if ( preg_match('/^ExtnTblSeq.*/',$LINE,$REG))	// This line is the field headers
		{
			$HEADER = str_getcsv($LINE);
			foreach ($HEADER as $KEY => $VALUE)
			{
				$HEADER[$KEY] = trim(preg_replace("/\#/","num"	,$HEADER[$KEY]) );	// IF the header contains #, replace it with "num"...
				$HEADER[$KEY] = trim(preg_replace("/\ /",""		,$HEADER[$KEY]) );	// IF the header contains SPACE, REMOVE it!
				if ($HEADER[$KEY] == "Name") { $HEADER[$KEY] = "DeviceName"; }	// Make this match the phone header that links the record types!
//				print "HEADER: {$HEADER[$KEY]}\n";
			}
		}elseif ( preg_match('/^.+,.+,.+/',$LINE,$REG))	// See if its a extension data line
		{
			$PHONERECORD = str_getcsv($LINE);
			$i = 0;	$PHONE = array();
			foreach ($PHONERECORD as $JUNK) { $PHONE[$HEADER[$i]] = trim($PHONERECORD[$i]); $i++; }
			array_push($RETURN,$PHONE);
		}else { continue; }
	}
	return $RETURN;
}

// This is a reusable function to get all the phone records out of the database
function sql_get_phones()
{
	// This reads all the records from a database table
	global $DB;							// Our Database Wrapper Object
	$QUERY = "SELECT * FROM phones";	// The text query we want to run

	$DB->query($QUERY);					// Prepare the query
	try {								// Try/catch block for error handling (exceptions thrown by the database)
		$DB->execute();					// execute the query
		$RESULTS = $DB->results();		// Collect the results in $RESULTS
	} catch (Exception $E) {			// IF we had an exception lets catch it and provide a meaningful error message
		$MESSAGE = "Exception: {$E->getMessage()}";
		$MESSAGE .= "QUERY:\n{$QUERY}\n\n";
		trigger_error($MESSAGE);		// This prints out the error message to the PHP error handler (command line, log file, etc)
		global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }	// This provides an HTML formatted error message in case the script was run from a browser
		die($MESSAGE);					// This terminates the program in case we hit an unrecoverable SQL error, prevents stuff from going crazy
	}
	return $RESULTS;					// Send the results of this function back to the caller
}

// This is a reusable function to get a specific phone record based on DeviceName field
function sql_get_phone_by_name($PHONE)	// Pass it the exact name of a phone you want to find
{
	global $DB;							// Our Database Wrapper Object
	$QUERY = "SELECT * FROM phones WHERE DeviceName = :DEVICENAME";	// The text query we want to run

	$DB->query($QUERY);					// Prepare the query
	try {								// Try/catch block for error handling (exceptions thrown by the database)
		$DB->bind("DEVICENAME",$PHONE);	// Bind is used for security to replace :KEY in query with VALUE in variable, prevents sql injection
		$DB->execute();					// execute the query
		$RESULTS = $DB->results();		// Collect the results in $RESULTS
	} catch (Exception $E) {			// IF we had an exception lets catch it and provide a meaningful error message
		$MESSAGE = "Exception: {$E->getMessage()}";
		$MESSAGE .= "QUERY:\n{$QUERY}\n\n";
		trigger_error($MESSAGE);		// This prints out the error message to the PHP error handler (command line, log file, etc)
		global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }	// This provides an HTML formatted error message in case the script was run from a browser
		die($MESSAGE);					// This terminates the program in case we hit an unrecoverable SQL error, prevents stuff from going crazy
	}
	return $RESULTS;					// Send the results of this function back to the caller
}

// This is a reusable function to update the phone record after it has been changed
// BIG ASSUMPTION! THIS ASSUMES A COMPLETE PHONE DATA RECORD IS BEING PASSED! NO EXTRA FIELDS ETC!
function sql_update_phone($PHONE)		// Pass it the entire phone record, ID is the key field! THIS RECORD MUST BE COMPLETE AND ACCURATE!
{
	global $DB;							// Our Database Wrapper Object

	if ( !isset($PHONE["id"]) ) { print "ERR: NO ID FOUND!\n"; return 0; }	// Error, cant update phone records without a KEY!

	$QUERY = "UPDATE phones SET\n";
	$FIELDS = array();
	foreach($PHONE as $KEY => $VALUE)
	{
		if ($KEY == "id") { continue; }	// Skip updating the ID field...
		array_push($FIELDS,"{$KEY} = :{$KEY}");
	}
	$QUERY .= implode(",\n",$FIELDS) . "\n";
	$QUERY .= "WHERE ID = :id LIMIT 1";
//print "QUERY:\n{$QUERY}\n\n";

	$DB->query($QUERY);					// Prepare the query
	try {								// Try/catch block for error handling (exceptions thrown by the database)
		foreach($PHONE as $KEY => $VALUE)
		{
			$DB->bind("{$KEY}"	,$VALUE);
		}
		$DB->execute();					// execute the query
	} catch (Exception $E) {			// IF we had an exception lets catch it and provide a meaningful error message
		$MESSAGE = "Exception: {$E->getMessage()}";
		$MESSAGE .= "QUERY:\n{$QUERY}\n\n";
		trigger_error($MESSAGE);		// This prints out the error message to the PHP error handler (command line, log file, etc)
		global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }    // This provides an HTML formatted error message in case the script was run from a browser
		die($MESSAGE);					// This terminates the program in case we hit an unrecoverable SQL error, prevents stuff from going crazy
	}
	return $PHONE["id"];	// Return the ID of the record on successful update!
}

// This is a reusable function to insert the phone record after it has been changed
// BIG ASSUMPTION! THIS ASSUMES A PHONE DATA RECORD IS BEING PASSED! NO EXTRA FIELDS ETC!
function sql_insert_phone($PHONE)		// Pass it the complete or partial phone record
{
	global $DB;							// Our Database Wrapper Object

	if ( isset(	$PHONE["id"			]) ) { print "ERR: ID DETECTED!\n"; 	return 0; }	// Error, dont insert phones with an existing ID number stupid!
	if ( !isset($PHONE["DeviceName"	]) ) { print "ERR: NO DEVICENAME!\n";	return 0; }	// Error, dont insert phones without a device name!
	if ( !isset($PHONE["CallManager"]) ) { print "ERR: NO CALLMANAGER!\n";	return 0; }	// Error, dont insert phones without a callmanager!

	$QUERY = "INSERT INTO phones (\n";
	$QUERY .= implode( "," , array_keys($PHONE) );
	$QUERY .= ") VALUES (";
	$KEYVALS = array();
	foreach($PHONE as $KEY => $VALUE) { array_push($KEYVALS,":{$KEY}"); }
	$QUERY .= implode( "," , ($KEYVALS) );
	$QUERY .= ")";
//print "QUERY: {$QUERY}\n";

	$DB->query($QUERY);					// Prepare the query
	try {								// Try/catch block for error handling (exceptions thrown by the database)
		foreach($PHONE as $KEY => $VALUE)
		{
			$DB->bind("{$KEY}"	,$VALUE);
		}
		$DB->execute();					// execute the query
		$ID = $DB->get_insert_id();		// Get our ID number back for the newly created record!
	} catch (Exception $E) {			// IF we had an exception lets catch it and provide a meaningful error message
		$MESSAGE = "Exception: {$E->getMessage()}";
		$MESSAGE .= "QUERY:\n{$QUERY}\n\n";
		$MESSAGE .= dumper_to_string($PHONE);
		trigger_error($MESSAGE);		// This prints out the error message to the PHP error handler (command line, log file, etc)
		global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }    // This provides an HTML formatted error message in case the script was run from a browser
		die($MESSAGE);					// This terminates the program in case we hit an unrecoverable SQL error, prevents stuff from going crazy
	}
	return $ID;	// Return the ID of the record on successful insert!
}

// Create or update an existing phone record, KEYS OFF PHONE DEVICENAME, NOT ID!
function sql_save_phone($PHONE)			// pass it a full or PARTIAL phone record and it will insert or update it
{
	if ( !isset($PHONE["DeviceName"	])) { return 0; }	// Fail if we dont have a device name set!
	if ( !isset($PHONE["CallManager"])) { return 0; }	// Fail if we dont have a callmanager we got the record from!

	$PHONESEARCH = sql_get_phone_by_name($PHONE["DeviceName"]);	// Returns MULTIPLE phone records if on multiple callmanagers!

	// Loop through the phones that came back from the search and see if one is worth updating
	foreach ($PHONESEARCH as $PHONERECORD)
	{
		if ($PHONERECORD["CallManager"] == $PHONE["CallManager"])	// If the phone record is from the same CM as our previous one
		{
			$DBPHONE = $PHONERECORD;								// WE HAVE A DB PHONE RECORD TO UPDATE!
			foreach ($DBPHONE as $KEY => $VALUE)
			{
				if ( isset($PHONE[$KEY]) )							// IF the phone has a better value for a key
				{
					$DBPHONE[$KEY] = $PHONE[$KEY];					// THEN replace the key in the DBPHONE record
				}
			}
			return sql_update_phone($DBPHONE);	// Finally update the DBPHONE record in the database and return success = the ID number of the record updated
		}
	}
	// Since we DIDNT find an existing phone record, lets insert a new one!
	return sql_insert_phone($PHONE);
}

?>
