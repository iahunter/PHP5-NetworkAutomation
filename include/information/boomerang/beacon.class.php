<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
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

require_once "information/information.class.php";

class Boomerang_Beacon	extends Information
{
	public $category = "Boomerang";
	public $type = "Boomerang_Beacon";
	public $customfunction = "";
	public $html_list_page_items = 100;

	public function customdata()	// This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("key"		,"stringfield0");
		$CHANGED += $this->customfield("status"		,"stringfield1");
		$CHANGED += $this->customfield("loadtime"	,"stringfield2");
		$CHANGED += $this->customfield("ip"			,"stringfield3");
		$CHANGED += $this->customfield("username"	,"stringfield4");
		$CHANGED += $this->customfield("url"		,"stringfield5");
		$CHANGED += $this->customfield("referer"	,"stringfield6");
//		$CHANGED += $this->customfield("browser"	,"stringfield7");
//		$CHANGED += $this->customfield("platform"	,"stringfield8");
//		$CHANGED += $this->customfield(""			,"stringfield9");
		if($CHANGED && isset($this->data['id'])) { $this->update(); global $DB; $DB->log("Database changes to object {$this->data['id']} detected, running update"); }	// If any of the fields have changed, run the update function.
	}

	public function update_bind()	// Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['key'		]);
		$DB->bind("STRINGFIELD1"	,$this->data['status'	]);
		$DB->bind("STRINGFIELD2"	,$this->data["loadtime"]);
		$DB->bind("STRINGFIELD3"	,$this->data['ip'		]);
		$DB->bind("STRINGFIELD4"	,$this->data['username'	]);
		$DB->bind("STRINGFIELD5"	,$this->data['url'		]);
		$DB->bind("STRINGFIELD6"	,$this->data['referer'	]);
	}

    public function list_query()
    {
        global $DB; // Our Database Wrapper Object
		$QUERY = "select id from information where type like :TYPE and category like :CATEGORY and active = 1 ORDER BY modifiedwhen DESC";
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
		$this->html_width[$i++] = 35;	// ID
		$this->html_width[$i++] = 50;	// Status
		$this->html_width[$i++] = 100;	// Load Time
		$this->html_width[$i++] = 50;	// IP
		$this->html_width[$i++] = 100;	// Username
		$this->html_width[$i++] = 900;	// URL
		$this->html_width[0]	= array_sum($this->html_width);
	}

	public function html_list_buttons()
	{
		$OUTPUT = "";
		return $OUTPUT;
	}

	public function html_list_header()
	{
		$OUTPUT = "";
		$this->html_width();

		// Information table itself
		$rowclass = "row1";	$i = 1;
		$OUTPUT .= <<<END

		<table class="report" width="{$this->html_width[0]}">
			<caption class="report">Probe List</caption>
			<thead>
				<tr>
					<th class="report" width="{$this->html_width[$i++]}">ID</th>
					<th class="report" width="{$this->html_width[$i++]}">Status</th>
					<th class="report" width="{$this->html_width[$i++]}">Loadtime(ms)</th>
					<th class="report" width="{$this->html_width[$i++]}">IP</th>
					<th class="report" width="{$this->html_width[$i++]}">Username</th>
					<th class="report" width="{$this->html_width[$i++]}">URL</th>
				</tr>
			</thead>
			<tbody class="report">
END;
		return $OUTPUT;
	}

	public function html_list_row($i = 1)
	{
		$OUTPUT = "";

		$this->html_width();
		$rowclass = "row".(($i % 2)+1);
		$columns = count($this->html_width)-1;	$i = 1;
		$OUTPUT .= <<<END

				<tr class="{$rowclass}">
					<td class="report" width="{$this->html_width[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data["id"]}</a></td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["status"]}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["loadtime"]}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["ip"]}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["username"]}</td>
					<td class="report" width="{$this->html_width[$i++]}">{$this->data["url"]}</td>
				</tr>
END;
		return $OUTPUT;
	}

	public function html_detail()
	{
		$OUTPUT = "";

		$this->html_width();

		// Pre-information table links to edit or perform some action
		$OUTPUT .= $this->html_detail_buttons();

		// Information table itself
		$columns = count($this->html_width)-1;
		$i = 1;
		$OUTPUT .= $this->html_list_header();
		$OUTPUT .= $this->html_list_row($i++);

		$rowclass = "row".(($i++ % 2)+1);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">Modified by {$this->data['modifiedby']} on {$this->data['modifiedwhen']}</td></tr>
END;
		$rowclass = "row".(($i++ % 2)+1);
		$DATADUMP = \metaclassing\Utility::dumperToString($this->data);
		$OUTPUT .= <<<END
				<tr class="{$rowclass}"><td colspan="{$columns}">{$DATADUMP}</td></tr>
END;

		$OUTPUT .= $this->html_list_footer();

		return $OUTPUT;
	}

	public function html_form()
	{
		$OUTPUT = "";
		return $OUTPUT;
	}

	// Throw a new boomerang
	public function initialize()
	{
		$this->data["key"]		= md5(uniqid());			// Generate a 3-time session key:
																// 1) Pass to client on initialize,
																// 2) collect from client on return (page load)
																// 3) and finally catch (page unload)
		$this->data["status"]	= 1;
		$this->data["ip"]		= $_SERVER["REMOTE_ADDR"];		// The users IP address
		$this->data["username"]	= $_SESSION["AAA"]["username"];	// Active directory username
		$this->data["realname"]	= $_SESSION["AAA"]["realname"]; // Real first.last name

		$BEACON_URL = "/information/information-raw.php";	// Use the information-action raw file to handle events!
		$BW_TEST_IMAGES = "/images/boomerang/";				// Directory for boomerang bw-test image files

		$OUTPUT = <<<END
	BOOMR.init({
		beacon_url: "{$BEACON_URL}",
		user_ip: "{$_SERVER["REMOTE_ADDR"]}",
		BW: {
				base_url: "{$BW_TEST_IMAGES}"
		}
	});
	BOOMR.addVar("id"		, "{$this->data["id"]}");
	BOOMR.addVar("key"		, "{$this->data["key"]}");
	BOOMR.addVar("method"	, "event");
END;

		return $OUTPUT;
	}

	public function event($DATA)	// EVENT, we saw the boomerang return or caught it!
	{
		// Do some checking to make sure the event call is legitimate!!!
		if ($this->data["key"]			!= $DATA["key"] 				||
			$this->data["username"]		!= $_SESSION["AAA"]["username"]	||
			$this->data["status"]		>= 3							)
			{ $MESSAGE = "Boomerang key/user/status mismatch!"; global $DB; $DB->log($MESSAGE,1); return; }

		// This event call looks authentic, lets continue processing it...
		$this->data["status"]++;		// We are now on status 2 (page load complete) or 3 (page unload event)

		if ($this->data["status"] == 2)	// In this case we are on step 2 (page load complete)
		{
			$this->data["url"]		= $DATA["u"];			// The URL being loaded
			$this->data["referer"]	= $DATA["r"];			// The refering URL (if any)
			$this->data["browser"]	= get_browser(null, true);// Get our client & browser info...
//			$this->data["browser"]	= $BROWSER["browser"];	// And save the browser
//			$this->data["platform"]	= $BROWSER["platform"];	// Also save the client OS

			$this->data["time"] = array();
			$this->data["time"]["server"]	= $DATA["t_resp"];						// Time for JUST the server to start responding
			$this->data["time"]["network"]	= $DATA["t_page"] - $DATA["t_resp"];	// Time for JUST the content to be delivered
			$this->data["time"]["browser"]	= $DATA["t_done"] - $DATA["t_page"];	// Time for JUST the browser to render all the content
			$this->data["time"]["total"]	= $DATA["t_done"];	// Total load time from request to rendered
			$this->data["loadtime"]			= $DATA["t_done"];	// Stupid limitation of information framework for single depth vars in stringfields
			if ( isset($DATA["lat"]) && $DATA["lat"] > 0 )
			{
				$this->data["latency"]	= array();
				$this->data["latency"]["avg"]	= $DATA["lat"];
				$this->data["latency"]["err"]	= $DATA["lat_err"];
				$this->data["latency"]["min"]	= $DATA["lat"] - $DATA["lat_err"];
				$this->data["latency"]["max"]	= $DATA["lat"] + $DATA["lat_err"];
			}
			if ( isset($DATA["bw"]) && $DATA["bw"] > 0 )
			{
				$this->data["bandwidth"] = array();
				$this->data["bandwidth"]["avg"]	= ( $DATA["bw"]						) * 8 / 1024 / 1024;	// Bandwidth is bytes/sec so multiply by 8 for bits/sec
				$this->data["bandwidth"]["err"]	= ( $DATA["bw_err"]					) * 8 / 1024 / 1024;	// then divide by 1024 for kbits/sec and finally mbits/sec
				$this->data["bandwidth"]["min"]	= ( $DATA["bw"] - $DATA["bw_err"]	) * 8 / 1024 / 1024;	// So now we know the min/max/avg/err of our bandwidth
				$this->data["bandwidth"]["max"]	= ( $DATA["bw"] + $DATA["bw_err"]	) * 8 / 1024 / 1024;
			}
		}
		if ($this->data["status"] == 3)	// Otherwise we are at step 3 (page unloaded by browser)
		{
			$this->data["time"]["visited"]	= $DATA["t_done"];
		}
		$this->update();	// SAVE our updated data in this beacon!
		header("Content-type: image/png");
		return;
	}

}
