<?php

/**
 * include/utility.class.php
 *
 * Utility object for more functions collected over the years
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

class Utility
{

	public static function last_stack_call($E)
	{
		$OUTPUT = "";
		if (isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 0)
		{
			$TRACE = $E->getTrace();
			$TRACE = reset($TRACE);
			$TRACE_FILE = basename($TRACE['file']);
			$OUTPUT .= "! IN {$TRACE_FILE} (line {$TRACE['line']}) function {$TRACE['function']}()\n";
		}
		return $OUTPUT;
	}

/*	public static function stack_trace($stacktrace)
	{
        $OUTPUT = "";

		$i = 1;
		foreach($stacktrace as $node)
		{
			$OUTPUT .= "$i. ".basename($node['file']) .":" .$node['function'] ."(" .$node['line'].")\n";
			$i++;
		}

		return $OUTPUT;
	}/**/

/*	public static function stack_trace2()
	{
		$e = new Exception;
//		$OUTPUT = dumper_to_string($e->getTraceAsString(), true);
		$OUTPUT = $e->getTraceAsString();
		return $OUTPUT;
	}/**/

	public static function tcp_probe($host,$port,$timeout = 1)
    {
        if ( false == ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) { return false; }
        if ( false == (socket_set_nonblock($socket))) { return 0; }
        $time = time();
        while (!@socket_connect($socket, $host, $port))
        {
            $err = socket_last_error($socket);
            if ($err == 115 || $err == 114)
            {
                if ((time() - $time) >= $timeout)
                {
                    socket_close($socket);
                    return false;
                }
                usleep(50000);	// Sleep for 50 ms! we run this loop 20 times before default timeout.
                continue;
            }
            return false;
        }
        socket_close($socket);
        return true;
	}

	public static function assoc_range($START,$END,$STEP = 1)
	{
		$RETURN = array();
		$RANGE = range($START,$END,$STEP);
		foreach ($RANGE as $KEY)
		{ $RETURN["$KEY"] = "$KEY"; }
		return $RETURN;
	}

	public static function recursive_strip_tags($INPUT, $ALLOWED_TAGS = "")
	{
//		print "Running recursive_strip_tags on "; dumper($INPUT); print "<br>\n"; john_flush();

		if (is_assoc($INPUT))		// If this is an associative array, parse it as key => value.
		{
			foreach($INPUT as $KEY => $VALUE)
			{
				$INPUT[$KEY] = Utility::recursive_strip_tags($VALUE, $ALLOWED_TAGS);
			}
		}else if(is_array($INPUT))	// If this is a normal array, parse it as $value.
		{
			foreach($INPUT as &$VALUE)
			{
				$VALUE = Utility::recursive_strip_tags($VALUE, $ALLOWED_TAGS);
			}
		}else if(is_string($INPUT))	// If this is a string, run the global strip_tags function.
		{
			$INPUT = @strip_tags($INPUT, $ALLOWED_TAGS);
		}							// If we dont know wtf we are given, dont muck it up.
		return $INPUT;
	}

	public static function draw_small_status($TEXT = "", $STATUSCOLOR = "black", $FONTCOLOR = "black" )
	{
		$FONT = BASEDIR . "/font/arial.ttf";								// Set Path to Font File
		$box = @imageTTFBbox(10,0,$FONT,$TEXT);									// Cheap trick to figure out how big to make our image
		$textwidth = abs($box[4] - $box[0]);
		$textheight = abs($box[5] - $box[1]);
		$WIDTH = 11 + $textwidth;												// Now our images have a dynamic width based on text length!
		$HEIGHT = 2 + $textheight;												// and a dynamic height
		if ($HEIGHT < 10) { $HEIGHT = 13; }

		$IMAGE = imagecreatetruecolor($WIDTH,$HEIGHT);							// Create our GD image object

		$COLORS = array();														// Create a pallet of colors
		$COLORS['transparent']	= imagecolorallocate($IMAGE,	254	,	254	,	254	);
		$COLORS['white']		= imagecolorallocate($IMAGE,	255	,	255	,	255	);
		$COLORS['black']		= imagecolorallocate($IMAGE,	0	,	0	,	0	);
		$COLORS['gray']			= imagecolorallocate($IMAGE,	127	,	127	,	127	);
		$COLORS['red']			= imagecolorallocate($IMAGE,	255	,	0	,	0	);
		$COLORS['green']		= imagecolorallocate($IMAGE,	0	,	224	,	0	);
		$COLORS['blue']			= imagecolorallocate($IMAGE,	0	,	0	,	255	);
		$COLORS['yellow']		= imagecolorallocate($IMAGE,	255	,	255	,	0	);
		$COLORS['orange']		= imagecolorallocate($IMAGE,	255	,	165	,	0	);

		imagefill($IMAGE,0,0,$COLORS['transparent']);							// Fill the image with our transparent color
		imagefilledellipse($IMAGE, 6, 6, 10, 10, $COLORS[$STATUSCOLOR]);		// Print a filled ellipse
		imagefilledellipse($IMAGE, 6, 6, 7,  7,  $COLORS["transparent"]);		// Print a filled ellipse
		imagefilledellipse($IMAGE, 6, 6, 3,  3,  $COLORS[$STATUSCOLOR]);		// Print a filled ellipse
		imagettftext($IMAGE, 10, 0, 13, 11, $COLORS[$FONTCOLOR], $FONT, $TEXT);	// Print Text On Image
		imagecolortransparent($IMAGE, $COLORS['transparent']);					// Create a transparent background

		ob_start();																// start a new output buffer
			imagepng($IMAGE);													// Send Image to the buffer
			$RETURN = ob_get_contents();										// Capture image contents from buffer
		ob_end_clean();															// stop this output buffer
		imagedestroy($IMAGE);													// Clean up the image
		return $RETURN;
	}

	public static function get_devices_by_search($SEARCH)
	{
		if ($SEARCH == "") { return array(); } // Prevent me from accidentally calling this function with a blank parameter again...
/*		$SEARCH = "%".$SEARCH."%";
		$QUERY = <<<END
			SELECT device.* FROM device
				WHERE prompt LIKE :SEARCH
			UNION
			SELECT device.*
				FROM showcmd
				RIGHT JOIN device
				USING (id)
				WHERE showcmd.run LIKE :SEARCH
			UNION
			SELECT device.*
				FROM showcmd
				RIGHT JOIN device
				USING (id)
				WHERE showcmd.version LIKE :SEARCH
			UNION
			SELECT device.*
				FROM showcmd
				RIGHT JOIN device
				USING (id)
				WHERE showcmd.inventory LIKE :SEARCH
			UNION
			SELECT device.*
				FROM showcmd
				RIGHT JOIN device
				USING (id)
				WHERE showcmd.diag LIKE :SEARCH
			UNION
			SELECT device.*
				FROM showcmd
				RIGHT JOIN device
				USING (id)
				WHERE showcmd.module LIKE :SEARCH
			ORDER BY id
END;
		global $DB;
		$DB->query($QUERY);
		try {
			$DB->bind("SEARCH"	,$SEARCH);
			$DB->execute();
			$RESULTS = $DB->results();
		} catch (Exception $E) {
			$MESSAGE = "Exception: {$E->getMessage()}";
			trigger_error($MESSAGE);
			global $HTML;
			die($MESSAGE . $HTML->footer());
		}
/**/
		return $RESULTS;
	}

}