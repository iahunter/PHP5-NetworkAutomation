<?php

/**
 * include/html.class.php
 *
 * HTML utility class to parse and return HTML code based on templates.
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
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

/**
 * Description for require_once
 */
require_once 'base.class.php';

/**
 * class HTML
 *
 * This is a utility class to dump header and footer as well as some html variable substitution.
 *
 * @category  default
 * @package   none
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2011 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class HTML extends Base
{
    /**
     * Description for protected
     * @var    array
     * @access protected
     */
    protected $version = 1.1;

    /**
     * Description for private
     * @var    array
     * @access private
     */
    private $data;

    /**
     * Description for private
     * @var    number
     * @access private
     */
    private $basetime;


	public $thispage;
	public $lastpage;

	function __construct()
	{
		$this->timer_start();
		$this->data['HEAD_EXTRA'] = "";
		$this->data['BODY_EXTRA'] = "";
		$this->data["breadcrumbs"] = array();
		$this->thispage = $_SERVER['PHP_SELF'];
		if (isset($_SERVER['HTTP_REFERER'])) { $this->lastpage = $_SERVER['HTTP_REFERER']; }else{ $this->lastpage = ""; }
	}


    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  unknown $key   Parameter description (if any) ...
     * @param  unknown $value Parameter description (if any) ...
     * @return void   
     * @access public 
     */
    public function set($key,$value) {
       if (isset($key)) { $this->data[$key] = $value; }
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  unknown $key Parameter description (if any) ...
     * @return array   Return description (if any) ...
     * @access public 
     */
    public function get($key) {
       if (isset($key)) { return $this->data[$key]; }
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @param  unknown $key Parameter description (if any) ...
     * @return void   
     * @access public 
     */
    public function clear($key) {
       if (isset($key)) { unset($this->data[$key]); }
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  array  $match Parameter description (if any) ...
     * @return mixed  Return description (if any) ...
     * @access public
     */
    public function replace($match) {
       return $this->get($match[1]);
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  array  $html Parameter description (if any) ...
     * @return mixed  Return description (if any) ...
     * @access public
     */
	public function parse($html)
	{
		if (!is_array($html))
		{
			if (file_exists($html))
			{
				$html = file($html);
			}else{
				return "Error opening template file $html";
			}
		}
		$return = "";
		foreach ($html as $line)
		{
			$return .= preg_replace_callback('/\<dynamic\s+name\s*=\s*[\'"](\S+?)[\'"]\>/i',array(&$this,'replace'),$line);
		}
		return $return;
	}

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @return void  
     * @access public
     */
    public function timer_start()
    {
        $PAGELOADTIME = microtime();
        $PAGELOADTIME = explode(" ", $PAGELOADTIME);
        $PAGELOADTIME = $PAGELOADTIME[1] + $PAGELOADTIME[0];
        $this->basetime = $PAGELOADTIME;
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @return unknown Return description (if any) ...
     * @access public 
     */
    public function timer_diff()
    {
        $PAGELOADTIME = microtime();
        $PAGELOADTIME = explode(" ", $PAGELOADTIME);
        $PAGELOADTIME = $PAGELOADTIME[1] + $PAGELOADTIME[0];
        $now = $PAGELOADTIME;
        $TIMEDIFF = ($now - $this->basetime);
	return $TIMEDIFF;
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  unknown $PAGE_TITLE Parameter description (if any) ...
     * @return string  Return description (if any) ...
     * @access public
     */
    public function header($PAGE_TITLE)
    {
        $this->set("PAGE_TITLE",$PAGE_TITLE);
		$this->set("BREADCRUMBS",$this->breadcrumbs());
        return $this->parse(INCLUDEDIR."/header.template.html");
    }

    public function hr()
    {
	return "<hr style=\"border: 0; color: #ccc; background-color: #aaa; height: 1px;\">";
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  string $FOOT_TEXT Parameter description (if any) ...
     * @param  string $FOOT_LINK Parameter description (if any) ...
     * @return string Return description (if any) ...
     * @access public
     */
	public function footer($FOOT_TEXT = "Home", $FOOT_LINK = "/")
	{
		$size = memory_get_usage(true);
		$unit=array('b','kb','mb','gb','tb','pb');
		$MEMORYUSED = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		$this->set("MEMORYUSED", $MEMORYUSED);
		$this->set("LOADTIME" ,number_format($this->timer_diff(),5));
		$this->set("FOOT_TEXT",$FOOT_TEXT);
		$this->set("FOOT_LINK",$FOOT_LINK);
		global $QUERYCOUNT;
		global $DB;
		$this->set("QUERYCOUNT",$QUERYCOUNT + intval(count($DB->QUERIES)));
		$this->set("RECORDCOUNT",intval($DB->RECORDCOUNT));
		if ( isset($_SESSION["DEBUG"]) && $_SESSION["DEBUG"] > 5 )
		{
			$this->set("FOOTERDEBUG",dumper($DB));
		}
		return $this->parse(INCLUDEDIR."/footer.template.html");
    }

    public function breadcrumb($bread, $crumb = "")
    {
        if (!is_array($this->data["breadcrumbs"]))
	{
		$this->data["breadcrumbs"] = array();
	}
	$this->data["breadcrumbs"][$bread] = $crumb;
    }

    public function breadcrumbs()
    {
        if (!is_array($this->data["breadcrumbs"]))
        {
            return;
        }
        $html = "";
        $html = "<div class=\"breadcrumbs\"><ul class=\"collapsed\">\n";
        foreach ($this->data["breadcrumbs"] as $key => $value)
        {
		if($value!="")
		{
			$html .= "<li><a href=\"$value\">$key</a></li>\n";
		}else{
			$html .= "<li>$key</li>\n";
		}
        }
        $html .= "</ul></div>\n";
        return $html;
    }

    /**
     * Short description for function
     * 
     * Long description (if any) ...
     * 
     * @param  string $FOOT_TEXT Parameter description (if any) ...
     * @param  string $FOOT_LINK Parameter description (if any) ...
     * @return string Return description (if any) ...
     * @access public
     */
    public function makeselect($name, $values, $select = array(), $size = 1, $multi = 0, $script = '')
    {
        if (!empty($select))
        {
            if (is_array($select))
            {
                foreach ($select as $item)
                {
                    $selected[$item] = 1;
                }
            }else{
                $selected[$select] = 1;
            }
        }

        if (!$this->is_assoc($values))
        {
            foreach ($values as $item)
            {
                $options[$item] = $item;
            }
        }else{
            $options = $values;
        }

        $html = sprintf("<select name='%s%s' size='%s'%s%s>\n",$name,($multi) ? "[]" : "",$size,($multi) ? " multiple" : ""," $script");
        foreach ($options as $key => $value)
        {
            $html .= sprintf("<option value='%s'%s>%s</option>\n",$key,(isset($selected[$key])) ? " selected" : "",$value);
        }
        $html .= "</select>\n";
        return $html;
    }

	public function featureblock($title, $link, $image, $list)
	{
	if (!is_array($list))
	{
		return;
	}
	$html = "";
	$html .= "<div class=\"featured-block\" OnClick=\"document.location='$link'\" style=\"position:relative; display:block;\">\n";
	$html .= "
			<div class=\"block-title\">$title</div>

			<div style=\"display: table;\">
				<div style=\"display: table-row; position:relative;\">
					<div class=\"block-image\">
						<img src=\"$image\">
					</div>
			                <div class=\"block-list\">
			                        <ul>\n";
	foreach ($list as $li)
	{
		$html .= "<li>$li</li>\n";
	}
	$html .= "
		                        	</ul>
			                </div>
			        </div>
		        </div>
		</div>\n";
	return $html;
    }

    public function quicktable($title, $varval)
    {
        if (!is_array($varval))
        {
            return;
        }
        $html = "";
	$html .= "<table border=1 cellpadding=1 cellspacing=0><tr><th colspan=2>" . $title . "</th></tr>\n";
	foreach ($varval as $key => $value)
	{
		$html .= "<tr><td>$key</td><td>$value</td></tr>\n";
	}
	$html .= "</table>\n";
	return $html;
    }

    public function quicktable_report($title, $cols, $varval)
    {
        if (!is_array($cols))
        {
            return "Quicktable Error (table cols is not an array)\n";
        }
        if (!is_array($varval))
        {
            return "Quicktable Error (table data is not an array)\n";
        }
        $html = "";
		$html .= "<table class=\"report\">
			<caption class=\"report\">$title</caption>
			<thead>
				<tr>";
				foreach ($cols as $col)
				{
					$html .= "<th class=\"report\">$col</th>";
				}
				$html .= "</tr>
				</thead>
				<tbody class=\"report\">\n";
		$i=1;
		foreach ($varval as $key => $value)
		{
			$i++; $rowclass = "row".(($i % 2)+1);
			$html .= "<tr class='".$rowclass."'>
				<td class=\"report\">$key</td>
				<td class=\"report\">$value</td>
				</tr>\n";
		}
		$html .= "</tbody></table>\n";
		return $html;
    }

    public function quicktable_assoc_report($title, $cols, $varval)
    {
        if (!is_array($cols))
        {
            return "Quicktable Error (table cols is not an array)\n";
        }
        if (!is_array($varval))
        {
            return "Quicktable Error (table data is not an array)\n";
        }
        $html = "";
		$html .= "<table class=\"report\">
			<caption class=\"report\">$title</caption>
			<thead>
				<tr>";
				foreach ($cols as $col)
				{
					$html .= "<th class=\"report\">$col</th>";
				}
				$html .= "</tr>
				</thead>
				<tbody class=\"report\">\n";
		$i=1;
		foreach ($varval as $element)
		{
			$i++; $rowclass = "row".(($i % 2)+1);
			$html .= "<tr class='".$rowclass."'>\n";
			foreach ($cols as $col)
			{
				$html .= "<td class=\"report\">{$element[$col]}</td>";
			}
			$html .= "</tr>\n";
		}
		$html .= "</tbody></table>\n";
		return $html;
    }

}

?>
