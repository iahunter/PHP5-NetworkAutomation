<?php

/**************************
* PHP5 Network Automation *
* Configuration File      *
**************************/

/******************
* Base Variables  *
******************/
define("BASEDIR"	,"/opt/networkautomation"	);	// Absolute base directory (no trailing slash)
define("BASEURL"	,"https://networktool/"		);	// Base URL to the website

define("EMAIL_TO"	,"admin@company.com"		);	// Email to send TO
define("EMAIL_FROM"	,"netman@company.com"		);	// Email to send FROM

/*******************
* SNMP Credentials *
*******************/
define("SNMP_RW"	,"NetworkRW"				);	// SNMP Read-write community string
define("TFTPIP"		,"10.123.123.123"			);	// Local TFTP server IP for config grabbing

/***********************
* Database Credentials *
***********************/
define("DB_HOSTNAME","10.123.123.33"			);	// SQL server IP or hostname
define("DB_DATABASE","production"				);	// SQL database name
define("DB_USERNAME","production_rw"			);	// SQL username
define("DB_PASSWORD","supersecret"				);	// SQL password

/*******************
* LDAP Credentials *
*******************/
define("LDAP_USER"	,"active.dir.usr"			);	// Valid active directory username
define("LDAP_PASS"	,"whatwhatinthe"			);	// Password for active directory user
define("LDAP_BASE"	,"DC=com"					);	// Top level domain (.com)
define("LDAP_DOMAIN","company"					);	// AD first level domain (no top level .com)
define("LDAP_HOST"	,"ldap.company.com"			);	// Active directory domain controller
define("LDAP_PORT"	,"3268"						);	// LDAP service port number on DC

/*********************
* TACACS Credentials *
*********************/
define("TACACS_USER","tacacs.user"				);	// TACACS username
define("TACACS_PASS","ciscorules"				);	// TACACS password
define("TACACS_ENABLE","topsecret"				);	// TACACS enable password

/****************
* PHP Variables *
****************/
ini_set('memory_limit', '512M');    // Limit process memory to 512meg
ini_set('display_errors', '0');     // Dont display errors
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT); // But do log them

?>
