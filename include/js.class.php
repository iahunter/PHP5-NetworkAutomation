<?php

/**
 * include/js.class.php
 *
 * This is a wrapper class for jquery from Ryan with animation updates from John
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
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

/**
 * class JS
 *
 * javascript wrapper for jquery
 *
 * Example:
 *
 *    class Example extends Base
 *    {
 *        ...
 *    }
 *
 * @category  default
 * @package   none
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2011 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

require_once 'base.class.php';

class JS extends Base
{
	protected $version = 1.0;

	//===================================================================================================
	// Description: Display a progress bar
	// Input: string(name), Name of the progressbar
	// Input: string(action), Action to be performed
	// Output: mixed(options), String or array of options
	//===================================================================================================
	public function progressbar($name = 'progressbar', $action = 'div', $options = null)
	{
		$return = "";
//		$pbcolor = "purple"; // grey orange purple lightblue greyblue etc...
		$pbcolor = "green"; // grey orange purple lightblue greyblue etc...
		$bgimg  = ($options['bar.image']) ? $options['bar.image'] : '/images/pb-'.$pbcolor.'.gif';
		$size   = ($options['bar.size']) ? $options['bar.size'] : 11;
		$bcolor = ($options['bar.color']) ? $options['bar.color'] : 'abb2bc';
		$value  = ($options['value']) ? $options['value'] : 0;
		$duration  = ($options['duration']) ? $options['duration'] : 1000;

		if ($action == 'div')
		{
			$return .= "<div id='$name' class='ui-progress-bar ui-container' style='font-size: ${size}px; background: #${bcolor}'>
					<div class='ui-progress' style='width: 0%;'></div>
				    </div>\n";

		}
		else if ($action == 'init')
		{
			$return .= <<<EOT
<style>
.ui-progress-bar {
  /* Usual setup stuff */
  position: relative;
  height: 22px;
  /* Pad right so we don't cover the borders when fully progressed */
  padding-right: 2px;
  /* For browser that don't support gradients, we'll set a blanket background colour */
  background-color: #$bcolor;
}

/* Progress part of the progress bar */
.ui-progress {
  /* Usual setup stuff */
  position: relative;
  display: block;
  overflow: hidden;
  /* Height should be 2px less than .ui-progress-bar so as to not cover borders and give it a look of being inset */
  height: 20px;
  /* For browser that don't support gradients, we'll set a blanket background colour */
  background-color: #74d04c;
  /* Give it a higher contrast outline */
  border: 1px solid #4c8932;
  background-image: url($bgimg);
}
</style>
EOT;
		}
		else if ($action == 'value')
		{
			$return .= "<script>\n".
				   "   \$('#$name .ui-progress').css('width', '$value%');\n".
				   "</script>\n";
		}
		else if ($action == 'animateprogress')
		{
			$return .= "<script>\n".
				   "   \$('#$name .ui-progress').animate({width: \"$value%\"}, {duration: $duration});".
				   "</script>\n";
		}
		else if ($action == 'show') {
			$return .= $this->show($name);
		}
		else if ($action == 'hide') {
			$return .= $this->hide($name);
		}

		return $return;
	}

   public function dialog($name = 'dialog', $action = 'init', $options = null)
   {
      $return = "";

      $left     = ($options['left']) ? $options['left'] : 100;
      $top      = ($options['top']) ? $options['top'] : 100;
      $height   = ($options['height']) ? $options['height'] : 100;
      $width    = ($options['width']) ? $options['width'] : 300;
      $border   = ($options['border']) ? $options['border'] : 1;
      $padding  = ($options['padding']) ? $options['padding'] : 15;
      $bcolor   = ($options['border.color']) ? $options['border.color'] : "000000";
      $bgcolor  = ($options['bg.color']) ? $options['bg.color'] : "ffffff";
      $txtcolor = ($options['text.color']) ? $options['text.color'] : "000000";
      $tcolor   = ($options['title.color']) ? $options['title.color'] : "ffffff";
      $tbgcolor = ($options['title.bg.color']) ? $options['title.bg.color'] : "996633";
      $ocolor   = ($options['overlay.color']) ? $options['overlay.color'] : "000000";
      $noclose  = ($options['no.close']) ? $options['no.close'] : 0;
      $escclose = ($noclose) ? 'false' : 'true';
      $title    = ($options['title']) ? $options['title'] : "";

      // Initialize the dialog.  Only set the title colors in init and allow the dialog object
      // to set the title here, this is used to prevent artifacts and improving rendering.
      // Do not try to call the title action with a title before the dialog box is sent to the client.
      //==============================================================================================
      if ($action == 'init') {
         $return .= $this->dialog($name,'frame',$options).
                    $this->dialog($name,'overlay',$options).
                    $this->dialog($name,'title',array('title.color' => $tcolor,
                                                      'title.bg.color' => $tbgcolor)).
                    "<script>\n".
                    "   $('#$name').dialog({title: '$title', position: [$left,$top], draggable: false, ".
                                           "height: $height, width: $width, resizable: false, ".
                                           "modal: true, closeOnEscape: $escclose});\n".
                    "</script>\n";
      }
      else if ($action == 'title') {
         $return .= "<style>\n".
                    "  .ui-dialog-titlebar { background-color: #${tbgcolor}; color: #${tcolor}; ".
                                            "border: ${border}px #${bcolor} solid; ".
                                            "border-color: #${bcolor}; }\n".
                    "</style>\n";
 
         if ($title) {
            $return .= "<script>\n".
                       "   $('#$name').dialog({title: '$title'});\n".
                       "</script>\n";
         }
      }
      else if ($action == 'overlay') {
         $return .= "<style>\n".
                    "  .ui-widget-overlay { background: #${ocolor}; display: block; }\n".
                    (($noclose) ? "  .ui-dialog-titlebar-close { display: none; }\n" : "").
                    "</style>\n";
      }
      else if ($action == 'frame') {
         $return .= "<style>\n".
                    "  .ui-dialog { background-color: #${bgcolor}; ".
                                   "border: ${border}px #${bcolor} solid; ".
                                   "border-color: #${bcolor}; padding: ${padding}px; }\n".
                    "</style>\n";
      }
      else if ($action == 'show') {
         $return .= $this->show($name);
      }
      else if ($action == 'hide') {
         $return .= $this->hide($name);
      }
 
      return $return;
 
   }
 
   public function messagebox($name = 'messagebox', $action = 'init', $options = null)
   {
      $return = "";
 
      $position = ($options['position']) ? $options['position'] : 'absolute';
      $left     = ($options['left']) ? $options['left'] : 100;
      $top      = ($options['top']) ? $options['top'] : 200;
      $width    = ($options['width']) ? $options['width'] : 650;
      $border   = ($options['border']) ? $options['border'] : 1;
      $bcolor   = ($options['border.color']) ? $options['border.color'] : "000000";
      $bgcolor  = ($options['bg.color']) ? $options['bg.color'] : "ffffff";
 
      if ($action == 'init') {
         $return .= "<style>\n".
                    "  .$name { background: #${bgcolor}; width: ${width}px; ".
                               "border: ${border}px #${bcolor} solid; ".
                               "padding: 15px; position: $position; left: ${left}px; top: ${top}px; ".
                               "-moz-border-radius: 3px; -webkit-border-radius: 3px; ".
                               "border-radius: 3px; }\n".
                    "</style>\n";
      }
      else if ($action == 'show') {
         $return .= $this->show($name);
      }
      else if ($action == 'hide') {
         $return .= $this->hide($name);
      }
 
      return $return;
   }
 
   public function screen($name = 'screen', $action = 'div', $options = null)
   {
      $return = "";
 
      $opacity = ($options['opacity']) ? $options['opacity'] : "0.7";
      $bgcolor = ($options['bg.color']) ? $options['bg.color'] : "000000";
 
      if ($action == 'div') {
         $return .= "<div id='$name' class='$name'></div>\n";
      }
      else if ($action == 'init') {
         $return .= "<style>\n".
                    "  #$name { position: absolute; left: 0; top: 0; background: #${bgcolor}; }\n".
                    "</style>\n";
      }
      else if ($action == 'fade') {
         $return .= "<script>\n".
                    "   \$('#$name').css({ opacity: $opacity, width: \$(document).width(), ".
                                          "height: \$(document).height(), background: '#${bgcolor}' });".
                    "</script>\n";
      }
      else if ($action == 'show') {
         $return .= $this->show($name);
      }
      else if ($action == 'hide') {
         $return .= $this->hide($name);
      }
 
      return $return;
   }
 
   public function textbox($name, $action = 'div', $options = null)
   {
      $return = "";
 
      $opacity = ($options['opacity']) ? $options['opacity'] : "0.7";
      $bgcolor = ($options['bg.color']) ? $options['bg.color'] : "000000";
 
      if ($action == 'div') {
         $return .= "<div id='$name' class='$name'></div>\n";
      }
      else if ($action == 'init') {
         $return .= "<style>\n".
                    "  #$name { position: absolute; left: 0; top: 0; background: #${bgcolor}; }\n".
                    "</style>\n";
      }
      else if ($action == 'fade') {
         $return .= "<script>\n".
                    "   \$('#$name').css({ opacity: $opacity, width: \$(document).width(), ".
                                          "height: \$(document).height() });".
                    "</script>\n";
      }
      else if ($action == 'show') {
         $return .= $this->show($name);
      }
      else if ($action == 'hide') {
         $return .= $this->hide($name);
      }
 
      return $return;
   }
 
   public function style($element, $options = null)
   {
      if (!is_array($options)) { return; }
 
      $return = "<script>\n";
 
      foreach ($options as $name => $value) {
         $return .= "  $('#$element').css('$name','$value');\n";
      }
      $return .= "</script>\n";
 
      return $return;
   }
 
   public function html($element, $status = '')
   {
      $status = preg_replace('/\'/','&quot;',$status)."<br>";
 
      $return = "<script>\n".
                "  $('#$element').html('$status');\n".
                "</script>\n";
 
      return $return;
  }
 
   public function show($element)
   {
      $return = "<script>\n".
                "   \$('#$element').show();\n".
                "</script>\n";

	return $return;
   }

   public function hide($element)
   {
      $return = "<script>\n".
                "   \$('#$element').hide();\n".
                "</script>\n";

      return $return;
   }
}

?>
