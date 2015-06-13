#!/usr/bin/php
<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

/****************************************************************/
/* This application attempts to backup ALL DATABASES!			*/
/****************************************************************/
/*

CREATE USER 'backup'@'%' IDENTIFIED BY  'changeme';

GRANT SELECT ,
RELOAD ,
FILE ,
SUPER ,
LOCK TABLES ,
SHOW VIEW ON * . * TO  'backup'@'%' IDENTIFIED BY  'changeme' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;

FLUSH PRIVILEGES;

/**/
define("DB_BACKUP_USERNAME"	,"backup"		);	// SQL username
define("DB_BACKUP_PASSWORD"	,"changeme"	);	// SQL password

$TEMPFOLDER = "/tmp/";		// Location to put the LOCAL backup before we move it to network storage.
$BACKUPFOLDER = "/backup/";	// NFS mount location to store our ultimate SQL dump.

$COMPRESS = " | gzip ";		// Comment this line out to save .sql files, or leave it in to gzip them.

/****************************************************************/

$FILENAME = "";				//	print "$FILENAME\n";
$FILENAME .= DB_DATABASE;	//	print "$FILENAME\n";
$FILENAME .= ".";			//	print "$FILENAME\n";
$FILENAME .= date("Y-m-d");	//	print "$FILENAME\n";
$FILENAME .= ".";			//	print "$FILENAME\n";
$FILENAME .= time();		//	print "$FILENAME\n";
$FILENAME .= ".sql";		//	print "$FILENAME\n";

if ($COMPRESS) { $FILENAME .= ".gz"; }

$BACKUP_COMMAND = "mysqldump --all-databases -h " . DB_HOSTNAME . " -u " . DB_BACKUP_USERNAME . " -p" . DB_BACKUP_PASSWORD . " {$COMPRESS} >> ${TEMPFOLDER}{$FILENAME}" ;
//print "BACKUP COMMAND: {$BACKUP_COMMAND}\n";

/****************************************************************/

//error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_USER_NOTICE);
error_reporting(E_ALL);

if (php_sapi_name() != "cli")
{
    die("This is a CLI tool only!");
}

/****************************************************************/

//die($BACKUP_COMMAND . "\n\n");	// Test this command before running with the script.

$LOG = "DATABASE BACKUP";												//print "{$LOG}\n";

$OUTPUT = shell_exec($BACKUP_COMMAND);
//print "{$OUTPUT}\n";

if ( !file_exists($TEMPFOLDER . $FILENAME) )	// If our backup command failed to create the file
{
	$LOG .= " Failed, temp file not created! Output: {$OUTPUT}";		//print "{$LOG}\n";
	$DB->log($LOG);								// Log some error output
	die($LOG);									// and exit
}

$FILESIZE = filesize($TEMPFOLDER . $FILENAME);
if ( $FILESIZE < 100000 )	// If our backup command failed to backup enough data
{
	$LOG .= " Failed, backup too small! Backup file {$FILESIZE} Bytes. Output: {$OUTPUT}";		//print "{$LOG}\n";
	$DB->log($LOG);								// Log some error output
	die($LOG);									// and exit
}

$LOG .= " TEMP FILE {$FILESIZE} BYTES";												//print "{$LOG}\n";

$TEMPHASH = md5_file($TEMPFOLDER . $FILENAME);

$TRIES = 3;
$TRY = 1;

while ($TRY <= $TRIES)
{
	copy($TEMPFOLDER . $FILENAME , $BACKUPFOLDER . $FILENAME);
	if ( file_exists($BACKUPFOLDER . $FILENAME) )
	{
		$BACKUPHASH = md5_file($BACKUPFOLDER . $FILENAME);
		if ($BACKUPHASH == $TEMPHASH)
		{
			$LOG .= " TRY{$TRY} SUCCESS! {$BACKUPHASH} == {$TEMPHASH}";	//print "{$LOG}\n";
			unlink($TEMPFOLDER . $FILENAME);
			if ( file_exists($TEMPFOLDER . $FILENAME) )
			{
				$LOG .= " CLEANUP FAILED!";								//print "{$LOG}\n";
			}else{
				$LOG .= " CLEANUP COMPLETE!";							//print "{$LOG}\n";
			}
			$LOG .= " Backup Successful in {$TRY} of {$TRIES}!";		//print "{$LOG}\n";
			break;
		}else{
			$LOG .= " TRY{$TRY} FAILED, {$BACKUPHASH} != {$TEMPHASH}";	//print "{$LOG}\n";
		}
	}else{
		$LOG .= " TRY{$TRY} FAILED, FILE NOT FOUND!";					//print "{$LOG}\n";
	}
	$TRY++;
}
if ($TRY > $TRIES) { $LOG .= " BACKUP FAILED AFTER $TRIES TRIES!"; }

print "$LOG\n";
$DB->log($LOG);

  ///////
 //EOF//
///////
