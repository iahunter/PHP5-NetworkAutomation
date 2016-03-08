<?php

/**
 * include/gearman_client.class.php
 *
 * This class wraps a bunch of gearman functions for parallel distributed execution of work
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
 * @author    John Lavoie
 * @copyright 2009-2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

class Gearman_Client
{
	public $tasks = array();
	public $gearman;

	public function __construct($DATA = null)
	{
		$this->gearman = new \GearmanClient();	// This class wraps the global GearmanClient object
		$this->gearman->addServer(GEARMAN_SERVER,GEARMAN_PORT);
		$this->gearman->setCreatedCallback	( array($this,"callback_created"	) ); // New task created
		$this->gearman->setStatusCallback	( array($this,"callback_status"		) ); // Task provides status update
		$this->gearman->setCompleteCallback	( array($this,"callback_complete"	) ); // Task is completed and has returned a value
		$this->gearman->setFailCallback		( array($this,"callback_fail"		) ); // Task has failed
		$this->gearman->setExceptionCallback( array($this,"callback_exception"	) ); // Task has raised exception with an error message
	}

	// Just wrap the raw GearmanClient objects functions we use...
	public function addTask($FUNCTION, $DATA, &$CONTEXT = null, $UNIQUEID = null)
	{
		return $this->gearman->addTask($FUNCTION, json_encode($DATA), $CONTEXT, $UNIQUEID );
	}
	public function runTasks()			{ return $this->gearman->runTasks();			}
	public function error()				{ return $this->gearman->error();				}
	public function setTimeout($TIMEOUT){ return $this->gearman->setTimeout($TIMEOUT);	}

	// Provide callbacks with working referencial passes to track our stupid tasks and their status/output/etc.
	public function callback_created	($TASK, &$CONTEXT)
	{
		$this->tasks[$TASK->jobHandle()] = array();
	//	$this->tasks[$TASK->jobHandle()]["taskobj"] = $TASK;	// THIS DOES NOT WORK! the returned object can NOT be referenced outside THIS FUNCTION!
		krsort($this->tasks);	// Sort our task list by REVERSE task ID so that output processing order is preserved!
		//print "CREATED:   " . $TASK->jobHandle() . "\n";
	}
	public function callback_status		($TASK, &$CONTEXT)
	{
		$this->tasks[$TASK->jobHandle()]["progress"] = array(
															"numerator"   => $TASK->taskNumerator(),
															"denominator" => $TASK->taskDenominator(),
														  );
		//print "STATUS:    " . $TASK->jobHandle() . " - " . $TASK->taskNumerator() . "/" . $TASK->taskDenominator() . "\n";
	}
	public function callback_complete	($TASK, &$CONTEXT)
	{
		$this->tasks[$TASK->jobHandle()]["status"] = "complete";
		$this->tasks[$TASK->jobHandle()]["output"] = $TASK->data();
		//print "COMPLETE:  " . $TASK->jobHandle() . " - " . $TASK->data() . "\n";
	}
	public function callback_fail		($TASK, &$CONTEXT)
	{
		$this->tasks[$TASK->jobHandle()]["status"] = "failed";
		$this->tasks[$TASK->jobHandle()]["failed"] = 1;
		//print "FAILED:    " . $TASK->jobHandle() . "\n";
	}
	public function callback_exception	($TASK,&$TASKS)
	{
		$this->tasks[$TASK->jobHandle()]["error"] = $TASK->data();
		//print "EXCEPTION: " . $TASK->jobHandle() . " - " . $TASK->data() . "\n";
	}

}
///////////////
// Usage
/*
$FUNCTION	= "information-action";
$QUEUE		= "web";
$WORK		= "{$FUNCTION}-{$QUEUE}";

$LORDBUSINESS = new GearmanClient;
foreach($RESULTS as $ID)
{
	// Containerize our information to pass the worker
	$DATA = array();
	$DATA["id"] = $ID;			// Information object ID we want to instanciate
	$DATA["method"] = "ping";	// Information object function we want to call
	$DATA["string"] = "ASDF " . md5( rand(1,100) );	// Some data to send the function

	$LORDBUSINESS->addTask($WORK, $DATA);
}

// Now run all those tasks in parallel!
if (! $LORDBUSINESS->gearman->runTasks())
{
	print "ERROR " . $LORDBUSINESS->gearman->error() . "\n";
	exit(1);
}else{
	foreach ($LORDBUSINESS->tasks as $HANDLE => $TASKINFO)
	{
		print "Checking task {$HANDLE}... ";
		if ( isset($TASKINFO["output"]) )
		{
			print "Completed, data: {$TASKINFO["output"]}\n";
		}else{
			print "Issues, did not return data!";
			if ( isset($TASKINFO["error"]) ) { print " ERROR: {$TASKINFO["error"]}"; }
			print "\n";
		}
	}
}
/**/
