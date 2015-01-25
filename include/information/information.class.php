<?php

/**
 * include/information/information.class.php
 *
 * The information object store
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
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

class Information
{
	public $data;
	public $category = "";
	public $type = "Information";
	public $customfunction = "customfunction";
	public $html_width;
	public $html_list_page_items = 0;

	public function __construct($DATA = null)
	{
		if (empty($DATA))
		{
			$this->data = array();
			$this->data['category'] = $this->category;
			$this->data['type'] = $this->type;
			$this->customdata(); // Sometimes used to populate custom datastructures!
		}elseif(is_array($DATA)){
			$this->data = $DATA;
			$this->data['category'] = $this->category; // Bug, sometimes this gets lost on non-nested informatin types
			$this->customdata(); // Sometimes used to populate custom datastructures!
		}else{
			$MESSAGE = "Error: Information Constructor Called With non-empty, non-array data!";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}
	}

	public static function retrieve($ID)
	{
		global $DB; // Our Database Wrapper Object
		if (!$ID)
		{
			$MESSAGE = "ERROR: Information::retrieve called with no ID:\"{$ID}\"!";
			$MESSAGE .= "STACK TRACE: " . Utility::last_stack_call(new Exception);
			$DB->log($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}
		$data = "";
		if ( isset($GLOBALS["CACHE"]) )
		{
			//print "ATTEMPTING TO PULL FROM CACHE!\n";
			global $CACHE;
			$PREFIX = "InfoID:";
			$KEY = "{$PREFIX}{$ID}";
			$BASETIME = Utility::microtime_ticks();
			$data = unserialize($CACHE->get($KEY));
			$DIFFTIME = Utility::microtime_ticks() - $BASETIME;
			$DB->CACHETIME += $DIFFTIME;
		}
		if ( $data == "" ) // IF we didnt get any data out of the cache
		{
			//print "CACHE MISS, FALLING BACK TO SQL!\n";
			$QUERY = "select * from information where id= :ID";
			$DB->query($QUERY);
			try {
				$DB->bind("ID",$ID);
				$DB->execute();
				$RESULTS = $DB->results();
			} catch (Exception $E) {
				$MESSAGE = "Exception: {$E->getMessage()}";
				trigger_error($MESSAGE);
				global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
				die($MESSAGE);
			}
			$RECORDCOUNT = count($RESULTS);
			if ($RECORDCOUNT != 1)
			{
				$MESSAGE = "ERROR: Request for information ID {$ID} returned {$RECORDCOUNT} results!";
				$DB->log($MESSAGE);
				global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
				die($MESSAGE);
			}
			$data = $RESULTS[0];
			if ( isset($GLOBALS["CACHE"]) )
			{
				//print "SQL SUCCESS, PUSHING TO CACHE!\n";
				global $CACHE;
				$PREFIX = "InfoID:";
				$KEY = "{$PREFIX}{$ID}";
				$CACHE->set($KEY,serialize($data));
			}
		}

		// This installs all serialized custom information into the standard data array
		$custom = unserialize($data['custom']);		// Unserialize what we got out of the database
		if (is_array($custom))					// Make sure its a valid associative array
		{
			foreach($custom as $key => $value)		// Install every element one by one
			{
				if (!isset($data[$key])) { $data[$key] = $value; } // DONT overwrite fields pulled directly from the database
			}
		}
		foreach($data as $key => $value)			// Prevent recursive appending to custom serialized value
		{
//			if ( $key == "custom" || is_int($key) || empty($value) )		// BUG possibly using value on integers = 0...
			if ( $key == "custom" || is_int($key) || strval($value) == "" )	// Trying to fix this...
			{
				unset($data[$key]);		// Remove the serialized information from the data array
			}
		}

		if (isset($data['category'])) { $category = $data['category']; }else{ $category = ""; }
		$type = $data['type']; // Type is mandatory

		$path = "information/"; $newclass = "";

		if ($category) { $path .= "$category/"; $newclass .= "{$category}_"; }

		$path .= "$type.class.php"; $newclass .= "$type";

		$path = strtolower($path); // Only use lower case in filesystem paths
		if (stream_resolve_include_path($path))
		{
			require_once($path);
		}else{
			$MESSAGE = "Error: Object type {$category}/{$type} class {$newclass} does not exist, is the file {$path} correct?";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}

		if(class_exists($newclass))
		{
			if($newclass == "Information") { return new $newclass($data);}
			if(is_subclass_of($newclass, 'Information'))
			{
				$OBJECT = new $newclass($data);
			}else{
				print "ERROR, $newclass is not a subclass of Information\n";
			}
		}else{
			print "ERROR, $newclass is not a valid class\n";
		}
		return $OBJECT;
	}

	public function children($ID = 0, $TYPE = "", $CATEGORY = "", $ACTIVE = -1)
	{
		if ($ACTIVE < 0) { $ACTIVE = intval($this->data['active']); }
		if ($ID == 0) { $ID = $this->data['id']; }
		$QUERY = "SELECT id FROM information WHERE parent = :ID AND active = :ACTIVE";
		if ($TYPE != "") { $QUERY .= " and type like :TYPE"; }
		if ($CATEGORY != "") { $QUERY .= " and category like :CATEGORY"; }
//		$QUERY .= " order by category,type,id";	// THIS INCURS A MASSIVE TIME PENALTY!
		$QUERY .= " order by id";

		global $DB;
		$DB->query($QUERY);
		try {
			$DB->bind("ID",$ID);
			$DB->bind("ACTIVE",$ACTIVE);
			if ($TYPE 		!= "") { $DB->bind("TYPE"		,$TYPE);	}
			if ($CATEGORY	!= "") { $DB->bind("CATEGORY"	,$CATEGORY);}
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}

		$CHILDREN = array();
		foreach ($RESULTS as $CHILD)
		{
			array_push($CHILDREN, Information::retrieve($CHILD['id']));
		}
		return $CHILDREN;
	}

	public static function ids_to_objs($INFOIDS)
	{
		$INFOBJS = array();
		foreach ($INFOIDS as $INFOID) { array_push($INFOBJS, Information::retrieve($INFOID) ); }
		return $INFOBJS;
	}

	public function parent()
	{
		$ID = $this->data['parent'];
		return Information::retrieve($ID);
	}

	public static function create($type, $category = "", $parent = 0)
	{
		$path = "information/"; $newclass = "";

		if ($category) { $path .= "$category/"; $newclass .= "${category}_"; }

		$path .= "$type.class.php"; $newclass .= "$type";

		$path = strtolower($path); // Only use lower case in filesystem paths
		if (stream_resolve_include_path($path))
		{
			require_once($path);
		}else{
			$MESSAGE = "Error: Object type {$category}/{$type} class {$newclass} does not exist, is the file {$path} correct?";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}

		$data = array();
		$data['category'] = $category;
		$data['type'] = $type;
		$data['parent'] = $parent;

		if(class_exists($newclass))
		{
			if($newclass == "Information") { return new $newclass($data);}
			if(is_subclass_of($newclass, 'Information'))
			{
				$OBJECT = new $newclass($data);
			}else{
				print "ERROR, $newclass is not a subclass of Information\n";
			}
		}else{
			print "ERROR, $newclass is not a valid class\n";
		}
		return $OBJECT;
	}

	public static function search( $SEARCH = array() , $ORDER = "ID" )
	{
		$RETURN = array();
		$QUERY = "SELECT id FROM information WHERE 1=1";	// By default passing no search criteria will result in all record ID's being returned
		if (!isset($SEARCH['active'])) { $SEARCH['active'] = 1; } // By default, only search ACTIVE information. This can be overridden abnormally
		foreach ($SEARCH as $KEY => $VALUE)
		{
			if ( is_array($VALUE) && count($VALUE) )		// If they pass us an array WITH content
			{
				$QUERY .= " AND ( ";
				foreach($VALUE as $INDEX => $ORVALUE)
				{
					if ($INDEX > 0) { $QUERY .= " OR "; }
					$QUERY .= "{$KEY} LIKE :{$KEY}ITEM{$INDEX}";
				}
				$QUERY .= " )";
			}else{
				$QUERY .= " AND {$KEY} LIKE :{$KEY}";
			}
		}
		$QUERY .= " ORDER BY {$ORDER}";
		//dumper($QUERY);
		global $DB;
		$DB->query($QUERY);
		try {
			foreach ($SEARCH as $KEY => $VALUE)
			{
				if ( is_array($VALUE) && count($VALUE) )        // If they pass us an array WITH content
				{
					foreach($VALUE as $INDEX => $ORVALUE)
					{
						$DB->bind($KEY."ITEM".$INDEX, $ORVALUE);
					}
				}else{
					$DB->bind($KEY, $VALUE);
				}
			}
			$DB->execute();
			$RESULTS = array_values($DB->results());
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		foreach ($RESULTS as $RESULT)
		{
			array_push($RETURN, $RESULT['id']);
		}
		return $RETURN;
	}

	public static function multisearch( $SEARCHES = array() )
	{
		$RETURN = array();
		if (is_array($SEARCHES))
		{
			foreach($SEARCHES as $SEARCH)
			{
				$RETURN = array_merge($RETURN,Information::search($SEARCH));
				$RETURN = array_unique($RETURN); // remove duplicate values
/*print "SEARCHED FOR:\n";
dumper($SEARCH);
print "RETURN NOW CONTAINS " . count($RETURN) . " RECORDS!\n";/**/
			}
		}
		return $RETURN;
	}

	public function customfield($KEY1,$KEY2)
	{
		if (!isset($this->data[$KEY2])) { return 0; }	// If the comparator is not set, dont bother comparing
		if($this->data[$KEY1] != $this->data[$KEY2])	// If the data is different,
		{
			$this->data[$KEY1] = $this->data[$KEY2];	// Fix the difference
			return 1;
		}
		return 0;
	}

	public function customdata()
	{
		$CHANGED = 0;
		// This is called by the constructor for a hook in children to override.
		$CHANGED += $this->customfield("stringfield0","stringfield0");
		$CHANGED += $this->customfield("stringfield1","stringfield1");
		$CHANGED += $this->customfield("stringfield2","stringfield2");
		$CHANGED += $this->customfield("stringfield3","stringfield3");
		$CHANGED += $this->customfield("stringfield4","stringfield4");
		$CHANGED += $this->customfield("stringfield5","stringfield5");
		$CHANGED += $this->customfield("stringfield6","stringfield6");
		$CHANGED += $this->customfield("stringfield7","stringfield7");
		$CHANGED += $this->customfield("stringfield8","stringfield8");
		$CHANGED += $this->customfield("stringfield9","stringfield9");
		// If any of the fields have changed, run the update function.
		if($CHANGED && isset($this->data['id'])) { $this->update(); }
	}

	public function validate($NEWDATA)
	{
		// This function will validate data before $this->data is replaced and an update is issued.
		return 1; // Always return true because data is valid, this can be overridden in children.
	}

	public function update()
	{
		$QUERY = <<<END
			UPDATE information SET
			active			= :ACTIVE,
			parent			= :PARENT,
			type			= :TYPE,
			category		= :CATEGORY,
			modifiedby		= :USERNAME,
			modifiedwhen	= now(),
			custom			= :SERIALDATA,
			stringfield0	= :STRINGFIELD0,
			stringfield1	= :STRINGFIELD1,
			stringfield2	= :STRINGFIELD2,
			stringfield3	= :STRINGFIELD3,
			stringfield4	= :STRINGFIELD4,
			stringfield5	= :STRINGFIELD5,
			stringfield6	= :STRINGFIELD6,
			stringfield7	= :STRINGFIELD7,
			stringfield8	= :STRINGFIELD8,
			stringfield9	= :STRINGFIELD9
			WHERE id = :ID
END;
		global $DB;
		$DB->query($QUERY);
		try {
			$this->update_bind(); // Always call update_bind first allowing the child type to override data in the structure!
			$this->update_override();	// This is a function for final overwriting or appending of data.
			$DB->bind("ACTIVE"			,$this->data['active'			]);
			$DB->bind("PARENT"			,$this->data['parent'			]);
			$DB->bind("TYPE"			,$this->data['type'				]);
			$DB->bind("CATEGORY"		,$this->data['category'			]);
			if (isset($_SESSION["AAA"]["username"]) && $_SESSION["AAA"]["username"] != "" && $_SESSION["AAA"]["username"] != "Anonymous")
			{
				$USERNAME = $_SESSION["AAA"]["username"];
			}else{
				$USERNAME = LDAP_USER;
			}
			$DB->bind("USERNAME"		,$USERNAME						);
			$DB->bind("ID"				,$this->data['id'				]);
			$DB->bind("STRINGFIELD0"	,$this->data['stringfield0'		]);
			$DB->bind("STRINGFIELD1"	,$this->data['stringfield1'		]);
			$DB->bind("STRINGFIELD2"	,$this->data['stringfield2'		]);
			$DB->bind("STRINGFIELD3"	,$this->data['stringfield3'		]);
			$DB->bind("STRINGFIELD4"	,$this->data['stringfield4'		]);
			$DB->bind("STRINGFIELD5"	,$this->data['stringfield5'		]);
			$DB->bind("STRINGFIELD6"	,$this->data['stringfield6'		]);
			$DB->bind("STRINGFIELD7"	,$this->data['stringfield7'		]);
			$DB->bind("STRINGFIELD8"	,$this->data['stringfield8'		]);
			$DB->bind("STRINGFIELD9"	,$this->data['stringfield9'		]);
			$this->update_bind(); // Always call update_bind again allowing the child type to override the default bindings
			$this->update_type(); // Check to see if the datatype was changed by this update and redirect accordingly!
			$DB->bind("SERIALDATA"		,serialize($this->data)			);
			$DB->execute();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}
		if ( isset($GLOBALS["CACHE"]) )
		{
			$ID = $this->data["id"];
			//print "SQL UPDATED ID {$ID}, FLUSHING FROM CACHE!\n";
			global $CACHE;
			$PREFIX = "InfoID:";
			$KEY = "{$PREFIX}{$ID}";
			$CACHE->del($KEY);
		}
	}

	public function otr_update()
	{
		$QUERY = <<<END
			UPDATE information SET
			active			= :ACTIVE,
			parent			= :PARENT,
			type			= :TYPE,
			category		= :CATEGORY,
			custom			= :SERIALDATA,
			stringfield0	= :STRINGFIELD0,
			stringfield1	= :STRINGFIELD1,
			stringfield2	= :STRINGFIELD2,
			stringfield3	= :STRINGFIELD3,
			stringfield4	= :STRINGFIELD4,
			stringfield5	= :STRINGFIELD5,
			stringfield6	= :STRINGFIELD6,
			stringfield7	= :STRINGFIELD7,
			stringfield8	= :STRINGFIELD8,
			stringfield9	= :STRINGFIELD9
			WHERE id = :ID
END;
		global $DB;
		$DB->query($QUERY);
		try {
			$this->update_bind(); // Always call update_bind first allowing the child type to override data in the structure!
			$this->update_override();	// This is a function for final overwriting or appending of data.
			$DB->bind("ACTIVE"			,$this->data['active'			]);
			$DB->bind("PARENT"			,$this->data['parent'			]);
			$DB->bind("TYPE"			,$this->data['type'				]);
			$DB->bind("CATEGORY"		,$this->data['category'			]);
			$DB->bind("ID"				,$this->data['id'				]);
			$DB->bind("STRINGFIELD0"	,$this->data['stringfield0'		]);
			$DB->bind("STRINGFIELD1"	,$this->data['stringfield1'		]);
			$DB->bind("STRINGFIELD2"	,$this->data['stringfield2'		]);
			$DB->bind("STRINGFIELD3"	,$this->data['stringfield3'		]);
			$DB->bind("STRINGFIELD4"	,$this->data['stringfield4'		]);
			$DB->bind("STRINGFIELD5"	,$this->data['stringfield5'		]);
			$DB->bind("STRINGFIELD6"	,$this->data['stringfield6'		]);
			$DB->bind("STRINGFIELD7"	,$this->data['stringfield7'		]);
			$DB->bind("STRINGFIELD8"	,$this->data['stringfield8'		]);
			$DB->bind("STRINGFIELD9"	,$this->data['stringfield9'		]);
			$this->update_bind(); // Always call update_bind again allowing the child type to override the default bindings
			$this->update_type(); // Check to see if the datatype was changed by this update and redirect accordingly!
			$DB->bind("SERIALDATA"		,serialize($this->data)			);
			$DB->execute();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}
		if ( isset($GLOBALS["CACHE"]) )
		{
			$ID = $this->data["id"];
			//print "SQL UPDATED ID {$ID}, FLUSHING FROM CACHE!\n";
			global $CACHE;
			$PREFIX = "InfoID:";
			$KEY = "{$PREFIX}{$ID}";
			$CACHE->del($KEY);
		}
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
/*		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['stringfield0'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['stringfield1'		]);
		$DB->bind("STRINGFIELD2"	,$this->data['stringfield2'		]);
		$DB->bind("STRINGFIELD3"	,$this->data['stringfield3'		]);
		$DB->bind("STRINGFIELD4"	,$this->data['stringfield4'		]);
		$DB->bind("STRINGFIELD5"	,$this->data['stringfield5'		]);
		$DB->bind("STRINGFIELD6"	,$this->data['stringfield6'		]);
		$DB->bind("STRINGFIELD7"	,$this->data['stringfield7'		]);
		$DB->bind("STRINGFIELD8"	,$this->data['stringfield8'		]);
		$DB->bind("STRINGFIELD9"	,$this->data['stringfield9'		]);/**/
	}

	public function update_type()
	{
		global $DB;
		if(isset($this->data["newtype"]) && $this->data["type"] != $this->data["newtype"])
		{
			print "Information Changed Type To {$this->data["newtype"]}<br>
					<b>Please edit this new object and provide additional information!</b><br><br>\n";
			$this->data["type"] = $this->data["newtype"];
			$DB->bind("TYPE",$this->data["type"]);
			unset($this->data["newtype"]);
		}
	}

	public function update_override()
	{
		// set something = something else.
	}

	public function initialize()
	{
		// Hook for children to be initialized after they are inserted into the database!
		return "";
	}

	public function reinitialize()
	{
		// Hook for children to be initialized after they are edited and type changes!
		return "";
	}

	public function insert()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "INSERT INTO information () VALUES()";
		$DB->query($QUERY);
		try {
			$DB->execute();
			$ID = $DB->get_insert_id();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML; if (is_object($HTML)) { $MESSAGE .= $HTML->footer(); }
			die($MESSAGE);
		}
		$this->data['id'] = $ID;
		if($this->data['parent']) // If we have a parent, copy its active status.
		{
			$this->data['active'] = $this->parent()->data['active']; // If this is a child, and the parent is active, make it active.
		}else{
			$this->data['active'] = 1;	// New records with no parent are always marked active
		}
		$this->update();
		return $ID;
	}

	public function created_by()
	{
		// if we have the data already in the object, just return that!
		if ( isset($this->data["createdby"]) && $this->data["createdby"] != "" ) { return $this->data["createdby"]; }
		// Otherwise go get it and update ourselves!
		global $DB;
		$QUERY = "SELECT user,date FROM log WHERE description LIKE 'Information Added ID:{$this->data['id']} %' ORDER BY id";
		$DB->query($QUERY);
		try {
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		$CREATOR = "Unknown";
		if (count($RESULTS))
		{
			$RESULT = reset($RESULTS);
			$CREATOR = $RESULT['user'];
			$CREATED = $RESULT['date'];
		}
		// Jam it into the structure and OTR update the record
		$this->data["createdby"] = $CREATOR;
		$this->data["createdwhen"] = $CREATED;
		if ($CREATOR || $CREATED) { $this->otr_update(); }
		return $CREATOR;
	}

	public function created_when()
	{
		// if we have the data already in the object, just return that!
		if ( isset($this->data["createdwhen"]) && $this->data["createdwhen"] != "" ) { return $this->data["createdwhen"]; }
		// Otherwise go get it and update ourselves!
		global $DB;
		$QUERY = "SELECT user,date FROM log WHERE description LIKE 'Information Added ID:{$this->data['id']} %' ORDER BY id";
		$DB->query($QUERY);
		try {
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		$CREATOR = "Unknown";
		if (count($RESULTS))
		{
			$RESULT = reset($RESULTS);
			$CREATOR = $RESULT['user'];
			$CREATED = $RESULT['date'];
		}
		// Jam it into the structure and OTR update the record
		$this->data["createdby"] = $CREATOR;
		$this->data["createdwhen"] = $CREATED;
		if ($CREATOR || $CREATED) { $this->otr_update(); }
		return $CREATED;
	}

	public function toggle_active()
	{
		$OUTPUT = "";
		if ($this->data['active'])
		{
			$OUTPUT .= $this->set_active(0);
		}else{
			$OUTPUT .= $this->set_active(1);
		}
		return $OUTPUT;
	}

	public function set_active($ACTIVE)	// Recursively sets information object state to active or inactive
	{
		$OUTPUT = "";
		if ($ACTIVE == 1) { $ACTION = "Activate"; }else{ $ACTION = "Deactivate"; }
		$CHILDREN = $this->children();
		foreach ($CHILDREN as $CHILD)
		{
			$OUTPUT .= "\t" . $CHILD->set_active($ACTIVE);
		}
		$this->data['active'] = $ACTIVE;
		$this->update();
		$OUTPUT = "Information $ACTION ID:{$this->data['id']} CATEGORY:{$this->data['category']} TYPE:{$this->data['type']} ACTIVE:{$this->data['active']}\n" . $OUTPUT;
		return $OUTPUT;
	}

	public function html_toggle_active_button()
	{
		$OUTPUT = "";
		if (!isset($this->data['id'])) { return $OUTPUT; }	// If this is a new item not yet added, dont offer an activate link!
		if ($this->data['active'])
		{
			$ACTION = "Deactivate";
			$CSSCLASS = "dellink";
		}else{
			$ACTION = "Activate";
			$CSSCLASS = "addlink";
		}
		$OUTPUT .= <<<END

					<ul class="object-tools">
						<li>
							<a href="/information/information-toggleactive.php?id={$this->data['id']}" class="{$CSSCLASS}">{$ACTION} Information</a>
						</li>
					</ul>
END;
		return $OUTPUT;
	}

	public function list_query()
	{
		global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1";
		$DB->query($QUERY);
		try {
			$DB->bind("TYPE",$this->data['type']);
			$DB->bind("CATEGORY",$this->data['category']);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
		return $RESULTS;
	}

	public function html_width()
	{
		$this->html_width = array();	$i = 1;
		$this->html_width[$i++] = 50;	// ID
		$this->html_width[$i++] = 75;	// Parent
		$this->html_width[$i++] = 75;	// Category
		$this->html_width[$i++] = 100;	// Type
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list( $PAGE = 0 )
	{
		$OUTPUT = "";
		$this->html_width();
		$RESULTS = $this->list_query();

		// Buttons at the top to add items, etc.
		$OUTPUT .= $this->html_list_buttons();

		// TODO: if this object type supports user SEARCH function! Add a search box?

		$OUTPUT .= $this->html_list_pagination( $RESULTS, $PAGE ); // If this object type uses paginated lists generate the header for pages

		// Process the resulting items one at a time to add to the list table & sandwich them between header and footer
		if (count($RESULTS) > 0)
		{
			// Just grab the first record and use its html_list_header function (should be the same for all objects of type X)
			$FIRSTRECORD = reset($RESULTS);
			$FIRSTOBJECT = Information::retrieve($FIRSTRECORD['id']);
			$OUTPUT .= $FIRSTOBJECT->html_list_header();
			$i = 1;
			// Loop through all the remaining items
			foreach ($RESULTS as $INFO)
			{
				$INFOBJECT = Information::retrieve($INFO['id']);
				$OUTPUT .= $INFOBJECT->html_list_row($i++);
				unset ($INFOBJECT);	// THIS FREES UP MEMORY WHEN LISTING VERY LARGE SETS!
			}
			$OUTPUT .= $FIRSTOBJECT->html_list_footer();
		}
		return $OUTPUT;
	}

	public function html_list_gearman( $PAGE = 0 )
	{
		$OUTPUT = "";
		$this->html_width();
		$RESULTS = $this->list_query();

		// Buttons at the top to add items, etc.
		$OUTPUT .= $this->html_list_buttons();

		// TODO: if this object type supports user SEARCH function! Add a search box?

		$OUTPUT .= $this->html_list_pagination( $RESULTS, $PAGE ); // If this object type uses paginated lists generate the header for pages

		// Process the resulting items one at a time to add to the list table & sandwich them between header and footer
		if (count($RESULTS) > 0)
		{
			// Just grab the first record and use its html_list_header function (should be the same for all objects of type X)
			$FIRSTRECORD = reset($RESULTS);
			$FIRSTOBJECT = Information::retrieve($FIRSTRECORD["id"]);
			$OUTPUT .= $FIRSTOBJECT->html_list_header();
			$i = 1;
			// Loop through all the remaining items IN PARALLEL WITH GEARMAN!!!
			$GEARMAN = new Gearman_Client;
			$FUNCTION	= "information-action";
			$QUEUE		= "web";
			$WORK		= "{$FUNCTION}-{$QUEUE}";
			$i = 1;
			foreach ($RESULTS as $RECORD)
			{
				$DATA = array();
				$DATA["id"] = $RECORD["id"];
				$DATA["method"] = "html_list_row_gearman";
				$DATA["rowclass"] = $i++;
				$GEARMAN->addTask($WORK, $DATA);
			}
			$GEARMAN->setTimeout(12000);// Make sure that we have a timeout set (12 seconds)
			if (! $GEARMAN->runTasks())	// Now run all those tasks in parallel!
			{
				global $DB; $DB->log("GEARMAN ERROR: " . $GEARMAN->error() );
				return "<b>ERROR:</b> " . $GEARMAN->error() . " <b>Attempting to load the page without gearman...</b><br>\n" . $this->html_list($PAGE);
			}
			foreach ($GEARMAN->tasks as $HANDLE => $TASKINFO)
			{
				if ( isset($TASKINFO["output"]) )
				{
					$OUTPUT .= $TASKINFO["output"];
				}else{
					$OUTPUT .= "ERROR! " . dumper_to_string($TASKINFO);
				}
			}
			$OUTPUT .= $FIRSTOBJECT->html_list_footer();
		}
		return $OUTPUT;
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$COLUMNS = array("ID","Parent","Category","Type");
		$OUTPUT .= $this->html_list_header_template("Information List",$COLUMNS);
		return $OUTPUT;
	}

	public function html_list_header_template($TITLE,$COLUMNS)
	{
		$OUTPUT = "";
		$this->html_width();

		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
END;
		if ($TITLE != "")
		{
			$OUTPUT .= <<<END

			<caption class="report">{$TITLE}</caption>
END;
		}
		$OUTPUT .= <<<END

			<thead>
				<tr>
END;
		$i = 1;
		foreach($COLUMNS as $COLUMN)
		{
			$OUTPUT .= <<<END

				<th class="report" width="{$this->html_width[$i++]}">{$COLUMN}</th>
END;
		}
		$OUTPUT .= <<<END

				</tr>
			</thead>
			<tbody class="report">
END;

		return $OUTPUT;
	}

	public function html_list_pagination( & $RESULTS, $PAGE = 0 )
	{
		$OUTPUT = "";

		// If this object type uses paginated lists...
		if ($this->html_list_page_items)
		{
			$COUNT = count($RESULTS);
			$HTML_PAGE_LIST = "";	// I put this in a separate variable in case I need to add the page list at the BOTTOM of the list as well as the top!
			$PAGES = array_chunk($RESULTS,$this->html_list_page_items);	// Split the result set into pages of html_list_page_items size
			$PAGECOUNT = count($PAGES);									// Count the number of pages that gives us total
			$HTML_PAGE_LIST .= "Found {$COUNT} items, displaying page " . ($PAGE + 1) . " of {$PAGECOUNT} ({$this->html_list_page_items} max items per page) - ";
			$HTML_PAGE_LINKS = array();
			foreach(range(0,$PAGECOUNT - 1) as $NUMBER)	// Screwy math, count is readable, array index is - 1
			{
				$DISPLAY_PAGE = $NUMBER + 1;
				if ($NUMBER == $PAGE)	// If we are viewing page number N already, dont link to ourselves?
				{
					array_push($HTML_PAGE_LINKS,"<b>{$DISPLAY_PAGE}</b>");
				}else{
					array_push($HTML_PAGE_LINKS,"<a href=\"/information/information-list.php?category={$this->data['category']}&type={$this->data['type']}&page={$NUMBER}\">$DISPLAY_PAGE</a>");
				}
			}
			$HTML_PAGE_LIST .= "<b>Page: </b>" . implode(", ",$HTML_PAGE_LINKS);
			$OUTPUT .= "{$HTML_PAGE_LIST}<br><br>\n";	// Append the page link list to the current output...
			$RESULTS = $PAGES[$PAGE];					// Set $RESULTS = the subset of items for THIS page!
		}

		return $OUTPUT;
	}

	public function html_list_buttons()
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

		<table border="0" cellspacing="0" cellpadding="1">
			<tr>
				<td align="right">
					<ul class="object-tools">
						<li>
							<a href="/information/information-add.php?category={$this->data['category']}&type={$this->data['type']}" class="addlink">Add {$this->data['category']} / {$this->data['type']}</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>
END;
		return $OUTPUT;
	}

	public function html_list_row_gearman($DATA)
	{
		return $this->html_list_row($DATA["rowclass"]);
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$rowclass = "row".(($i % 2)+1);

		$columns = count($this->html_width)-1;	$i = 1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['id']}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['parent']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['category']}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data['type']}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_list_footer()
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

			</tbody>
		</table><br>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";
		$this->html_width();

		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$rowclass = "row1";

		$COLUMNS = array("ID","Parent","Category","Type");
		$OUTPUT .= $this->html_list_header_template("Information Detail",$COLUMNS);

		$columns = count($this->html_width)-1;
		$datadump = dumper_to_string($this->data);
		$OUTPUT .= <<<END

			<tbody class="report">
END;
		$OUTPUT .= $this->html_list_row();
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td colspan="{$columns}">
						{$datadump}
					</td>
				</tr>
			</tbody>
		</table><br>
END;

		// Print out the children object list
		$CHILDREN = $this->children();
		$i = 1; $LASTCHILD = "";
		if (!empty($CHILDREN))
		{
			$CHILD = reset($CHILDREN);
			$LASTCHILD = $CHILD;
			$OUTPUT .= $CHILD->html_list_header();
			foreach ($CHILDREN as $CHILD)
			{
				if ($LASTCHILD->category != $CHILD->category && $LASTCHILD->type != $CHILD->type)
				{
					$OUTPUT .= $LASTCHILD->html_list_footer();
					$OUTPUT .= $CHILD->html_list_header();
				}
				$OUTPUT .= $CHILD->html_list_row($i++);
				$LASTCHILD = $CHILD;
			}
			$OUTPUT .= $CHILD->html_list_footer();
		}

		return $OUTPUT;
	}

	public function html_children_button($CHILDTYPE)
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

			<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td align="right">
						<ul class="object-tools">
							<li>
								<a href="/information/information-add.php?parent={$this->data["id"]}&category={$this->data["category"]}&type={$CHILDTYPE}" class="addlink">Add {$CHILDTYPE}</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
END;
		return $OUTPUT;
	}

	public function html_children($CHILDTYPE)
	{
		$OUTPUT = "";
		$CHILDREN = $this->children($this->id,$CHILDTYPE . "%",$this->category);
		if (!empty($CHILDREN))
		{
			$CHILD = reset($CHILDREN);
			$OUTPUT .= $CHILD->html_list_header();
			$i = 1;
			foreach ($CHILDREN as $CHILD)
			{
				$OUTPUT .= $CHILD->html_list_row($i++);
			}
			$OUTPUT .= $CHILD->html_list_footer();
		}
		return $OUTPUT;
	}

	public function html_detail_buttons($EXTRA = "")
	{
		$OUTPUT = "";
		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= <<<END
		<table width="{$this->html_width[0]}" border="0" cellspacing="0" cellpadding="1">
			<tr>
				<td>
END;
		if ($this->customfunction)
		{
			$OUTPUT .= <<<END

					<ul class="object-tools" style="float: left; align: left;">
						<li>
							<a href="/information/information-action.php?id={$this->data['id']}&action={$this->customfunction}">{$this->customfunction}</a>
						</li>
					</ul>
END;
		}
		if ($EXTRA) { $OUTPUT .= $EXTRA; }
		$OUTPUT .= <<<END
				</td>
				<td align="right">
					<ul class="object-tools">
						<li>
							<a href="/information/information-edit.php?id={$this->data['id']}" class="viewsitelink">Edit Information</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>
END;

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";

		$OUTPUT .= $this->html_form_header();

		$OUTPUT .= $this->html_form_field_text("stringfield0","String Field 0");

		$OUTPUT .= $this->html_form_field_textarea("stringfield1","String Field 1");

		$SELECT = array(
			"option1" => "Option 1",
			"option2" => "Option 2",
			"option3" => "Option 3"
		);
		$OUTPUT .= $this->html_form_field_select("stringfield2","String Field 2",$SELECT);

		$OUTPUT .= $this->html_form_footer();

		return $OUTPUT;
	}

	public function html_form_extended()	// This is a function that some children override in large inheritance hierarchies
	{
		$OUTPUT = "";
		return $OUTPUT;
	}

	public function html_form_section($TEXT)
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END
				<tr class="row1">
					<th>$TEXT</th>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_form_comment($TEXT)
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong>
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_text($FIELD,$TEXT)
	{
		$OUTPUT = "";
		if (!isset($this->data[$FIELD])) { $this->data[$FIELD] = ""; }
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong>
					<input type="text" name="{$FIELD}" size="50" value="{$this->data[$FIELD]}">
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_hidden($FIELD,$VALUE = "")
	{
		if (!isset($this->data[$FIELD])) { $this->data[$FIELD] = ""; }
		if (!$VALUE) { $VALUE = $this->data[$FIELD]; }
		$OUTPUT = "";
		$OUTPUT .= <<<END

					<input type="hidden" name="{$FIELD}" value="{$VALUE}">
END;
		return $OUTPUT;
	}

	public function html_form_field_textarea($FIELD,$TEXT,$ROWS=6,$COLS=50)
	{
		$OUTPUT = "";
		if (!isset($this->data[$FIELD])) { $this->data[$FIELD] = ""; }
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong><br>
					<textarea name="{$FIELD}" rows="{$ROWS}" cols="{$COLS}">{$this->data[$FIELD]}</textarea>
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_comment($FIELD,$TEXT,$ROWS=6,$COLS=50)
	{
		$OUTPUT = "";
		if (!isset($this->data[$FIELD])) { $this->data[$FIELD] = ""; }
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong> {$this->data[$FIELD]}
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_select($FIELD,$TEXT,$KEYVALUE)
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong>
					<select name="{$FIELD}" size="1">
END;
		if (isset($this->data[$FIELD]))
		{
			$OUTPUT .= <<<END

					<option value="{$this->data[$FIELD]}" selected>{$this->data[$FIELD]}</option>
END;
		}
		foreach($KEYVALUE as $KEY => $VALUE)
		{
			$OUTPUT .= <<<END

					<option value="{$KEY}">{$VALUE}</option>
END;
		}
		$OUTPUT .= <<<END
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_multiselect($FIELD,$TEXT,$KEYVALUE)
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong>
					<select multiple name="{$FIELD}[]" size="8">
END;
		foreach($KEYVALUE as $KEY => $VALUE)
		{
			if ( in_array($KEY,$this->data[$FIELD]) ) { $SELECTED = " selected"; }else{ $SELECTED = ""; }
			$OUTPUT .= <<<END

					<option value="{$KEY}"{$SELECTED}>{$VALUE}</option>
END;
		}
		$OUTPUT .= <<<END
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_field_upload($FIELD,$TEXT)
	{
		$OUTPUT = "";
		if (preg_match("/image/",$this->data[$FIELD . "type"],$REG))
		{
			$IMGSRC = "<br>" . $this->html_display_image($FIELD,0,200);
/*			$IMGSRC = <<<END
<br><img src="/information/information-raw.php?id={$this->data["id"]}&method=display_image&field={$FIELD}" height="100" width="100">
END;/**/
		}else{ $IMGSRC = "None"; }
		$OUTPUT .= <<<END

				<tr><td>
					<strong>{$TEXT}:</strong>
					<input type="file" name="{$FIELD}">
					<br>Current $FIELD: {$IMGSRC}
				</td></tr>
END;
		return $OUTPUT;
	}

	public function html_form_header()
	{
		$OUTPUT = "";
		$OUTPUT .= <<<END

			<div id="nosx_form">
			<form method="post" action="{$_SERVER['PHP_SELF']}" enctype="multipart/form-data">
			<table width="700" border="0" cellspacing="2" cellpadding="1">
END;

		return $OUTPUT;
	}

	public function html_form_footer()
	{
		$OUTPUT = "";
		if($this->data['id'])
		{
			$OUTPUT .= <<<END

				<tr><td>
					<input type="hidden" name="id"		value="{$this->data['id']}">
					<input type="submit"				value="Edit Information">
				</td></tr>
END;
		}else{
			$OUTPUT .= <<<END

				<tr><td>
					<input type="hidden" name="category"	value="{$this->data['category']}">
					<input type="hidden" name="type"		value="{$this->data['type']}">
					<input type="hidden" name="parent"		value="{$this->data['parent']}">
					<input type="submit"					value="Add Information">
				</td></tr>
END;
		}

		$OUTPUT .= <<<END

			</table>
			</form>
		</div>
END;
		return $OUTPUT;
	}

	public function upload($FILES)
	{
		$OUTPUT = "";
		$UPLOAD_ERROR = array(
			0 => "UPLOAD_ERR_OK",
			1 => "UPLOAD_ERR_INI_SIZE",
			2 => "UPLOAD_ERR_FORM_SIZE",
			3 => "UPLOAD_ERR_PARTIAL",
			4 => "",
			6 => "UPLOAD_ERR_NO_TMP_DIR",
			7 => "UPLOAD_ERR_CANT_WRITE",
			8 => "UPLOAD_ERR_EXTENSION"
		);
		foreach($FILES as $KEY => $FILE)
		{
			if (isset($FILE['error']) && $FILE['error'] === UPLOAD_ERR_OK)
			{
				$this->data[$KEY] = file_get_contents($FILE['tmp_name']);
				$this->data[$KEY."type"] = $FILE['type'];
				$OUTPUT .= "Uploaded file to {$KEY}";
//				$OUTPUT .= dumper_to_string($FILE);
			}else{
				$OUTPUT .= "{$UPLOAD_ERROR[$FILE['error']]}";
				$OUTPUT .= dumper_to_string($FILE);
			}
		}
		return $OUTPUT;
	}

	public function action($FUNCTION, $ARRAY = "")
	{
		$OUTPUT = "";
		if ($FUNCTION == "") { $FUNCTION = "customfunction"; }
		if(method_exists($this, $FUNCTION))
		{
			$OUTPUT = $this->$FUNCTION($ARRAY);
		}else{
			$OUTPUT = "$FUNCTION is not a method of " . gettype($this) . " {$this->category}/{$this->type}\n";
		}
		return $OUTPUT;
	}

	public function customfunction($ARRAY = "")
	{
		$OUTPUT = "";
		$OUTPUT .= "This is a custom function in the parent object and should be overridden by the child!<br>\n";
		$OUTPUT .= "Called from {$this->category}/{$this->type} object<br>\n";
		return $OUTPUT;
	}

	public function display_image($DATA)
	{
		$FIELD = $DATA['field'];
		$FIELDTYPE = $FIELD . "type";
		header("Content-type: {$this->data[$FIELDTYPE]}");
		print $this->data[$FIELD];
	}

	public function html_display_image($FIELD, $HEIGHT = "", $WIDTH = "")
	{
		$OUTPUT = "";
		if (isset($this->data[$FIELD]))
		{
			if (preg_match("/image/",$this->data[$FIELD . "type"],$REG))
			{
				$OUTPUT = <<<END
<img src="/information/information-raw.php?id={$this->data["id"]}&method=display_image&field={$FIELD}"
END;
				if ($HEIGHT){ $OUTPUT .= " height=\"{$HEIGHT}\""; }
				if ($WIDTH)	{ $OUTPUT .= " width=\"{$WIDTH}\""; }
				$OUTPUT .= ">";
			}else{
				$OUTPUT = "$FIELD is not an image.";
			}
		}
		return $OUTPUT;
	}

	public function image_url($FIELD)
	{
		$OUTPUT = "";
		if (isset($this->data[$FIELD]))
		{
			if (preg_match("/image/",$this->data[$FIELD . "type"],$REG))
			{
				$OUTPUT = BASEURL . "information/information-raw.php?id={$this->data["id"]}&method=display_image&field={$FIELD}";
			}else{
				$OUTPUT = "$FIELD is not an image.";
			}
		}
		return $OUTPUT;
	}

	public function png_graph_simple($POINTS,$LABEL)
	{
		// Graphing inclusions
		include("pChart/pData.class");
		include("pChart/pChart.class");

		// Dataset definition
		$DataSet = new pData;

		$AXIS = "AXIS1";	
		$DataSet->AddPoint($POINTS,$AXIS);
		$DataSet->AddSerie($AXIS);
		$DataSet->SetSerieName($LABEL,$AXIS);

		// Initialise the graph
		$Test = new pChart(900,500);

		$FONT = BASEDIR . "/font/tahoma.ttf";
		$Test->setFontProperties($FONT,13); // Font size for the X and Y axis count/date numbers
		$Test->setGraphArea(50,10,860,440);
		$Test->drawGraphArea(255,255,255,TRUE);
		//$Test->setLineStyle(1,0);
		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,45,2); // 45 is text rotation angle for date!
		$Test->drawGrid(5,TRUE,230,230,230,50); // 5 is dotted line width on background 230 is grey and 50 is alternating gradient transparency

		// Draw the line graph
		$Test->setLineStyle(2,0);
		$Test->setColorPalette(0,   0   ,   0   ,   255 );  // Make our first line blue
		$Test->setColorPalette(1,   255 ,   120 ,   0   );  // Make our second line orange
		$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
		$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),5,2,255,255,255); // 5 is dot size on the line, 3 is dot background

		// Finish the graph
		$Test->setFontProperties($FONT,14); // Font size of the axis labels
		$Test->drawLegend(60,20,$DataSet->GetDataDescription(),255,255,255);
		$Test->stroke();

	}

	public function assoc_select_name($OBJECTIDARRAY)
	{
		$ASSOC_OBJECTS = array();
		foreach($OBJECTIDARRAY as $OBJECTID)
		{
			$OBJECT = Information::retrieve($OBJECTID);
			if (isset($OBJECT->data["name"]))
			{
				$ASSOC_OBJECTS[$OBJECTID] = $OBJECT->data["name"];
			}
		}
		return $ASSOC_OBJECTS;
	}

	public function parse_nested_list_to_array($LIST, $INDENTATION = " ")
	{
		$RESULT = array();
		$PATH = array();

		$LINES = explode("\n",$LIST);

		foreach ($LINES as $LINE)
		{
			if ($LINE == "") { continue; print "Skipped blank line\n"; } // Skip blank lines, they dont need to be in our structure
			$DEPTH	= strlen($LINE) - strlen(ltrim($LINE));
			$LINE	= trim($LINE);
			// truncate path if needed
			while ($DEPTH < sizeof($PATH))
			{
				array_pop($PATH);
			}
			// keep label (at depth)
			$PATH[$DEPTH] = $LINE;
			// traverse path and add label to result
			$PARENT =& $RESULT;
			foreach ($PATH as $DEPTH => $KEY)
			{
				if (!isset($PARENT[$KEY]))
				{
					$PARENT[$LINE] = array();
					break;
				}
				$PARENT =& $PARENT[$KEY];
			}
		}
		$RESULT = recursive_remove_empty_array($RESULT);
		//ksort($RESULT);	// Sort our keys in the array for comparison ease // Do we really need this?
		return $RESULT;
	}

}

?>
