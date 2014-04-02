<?php

/**
 * include/base.class.php
 *
 * This is a generic base class to derive others from in order to use common storage containers.
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
 * class Base
 *
 * Base class from which other classes are derived.
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
class Base
{

    /**
     * Description for protected
     * @var    float    
     * @access protected
     */
   protected $version = 1.0;

    /**
     * Description for protected
     * @var    array    
     * @access protected
     */
   protected $self    = array();

    /**
     * Returns the version of this class
     *
     * Input: null()
     * Output: int(version), Version number
     *
     * @return float  Return description (if any) ...
     * @access public
     */
   public function version() { return $this->version; }

    /**
     * Constructor
     *
     * Creates the class object
     * Input: object(debug), Debug object created from debug.class
     * Input: array(options), List of options to set in the class
     * Output: null()
     *
     * @param  unknown $debug   Parameter description (if any) ...
     * @param  array   $options Parameter description (if any) ...
     * @return void   
     * @access public 
     */
    public function __construct($debug = null, $options = null)
    {
        $this->self['debug'] = $debug;

        if (isset($options))
        {
            foreach ($options as $option => $optionval)
            {
                $this->self["option.$option"] = $optionval;
            }
        }
    }

    /**
     * Gets option from class if any were set when instanciated
     *
     * Input: string(opt), Name of option to retrieve
     * Output: string(value), Value of option
     *
     * @param  unknown $opt Parameter description (if any) ...
     * @return mixed   Return description (if any) ...
     * @access public 
     */
    public function option($opt) { return $this->self["option.$opt"]; }

    /**
     * Debug relay function; available if class was constructed with Debug object.
     *
     * Input: int(level), Level of debug assertion (0-7)
     * Input: string(mesg), Message to write to debug
     * Output: null()
     *
     * @param  unknown $level Parameter description (if any) ...
     * @param  unknown $mesg  Parameter description (if any) ...
     * @return unknown Return description (if any) ...
     * @access public 
     */
    public function debug($level,$mesg)
    {
        if (!isset($this->self['debug'])) { return; }

        $this->self['debug']->trace($level,$mesg,2);
    }

    /**
     * Checks whether an array is associative
     *
     * Input: array(array), Name of array to check
     * Output: boolean(value), True if array was associative
     *
     * @param  unknown $array Parameter description (if any) ...
     * @return integer Return description (if any) ...
     * @access public 
     */
    public function is_assoc($array) {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
    }

}

?>
