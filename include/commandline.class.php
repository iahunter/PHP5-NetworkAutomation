<?php

/**
 * include/commandline.class.php
 *
 * Command line argument parsing object
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
 * @author    Patrick Fisher <patrick@pwfisher.com>
 * @copyright 2009 Patrick Fisher <patrick@pwfisher.com>
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

/**
 * Command Line Interface (CLI) utility class.
 *
 * Utility class for parsing and processing commandline arguements.
 *
 * @category  default
 * @package   none
 * @author    Patrick Fisher <patrick@pwfisher.com>
 * @copyright 2009 Patrick Fisher <patrick@pwfisher.com>
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */
class CommandLine {

    public static $args;

    /**
     * PARSE ARGUMENTS
     *
     * This command line option parser supports any combination of three types
     * of options (switches, flags and arguments) and returns a simple array.
     *
     * [pfisher ~]$ php test.php --foo --bar=baz
     *   ["foo"]   => true
     *   ["bar"]   => "baz"
     * 
     * [pfisher ~]$ php test.php -abc
     *   ["a"]     => true
     *   ["b"]     => true
     *   ["c"]     => true
     * 
     * [pfisher ~]$ php test.php arg1 arg2 arg3
     *   [0]       => "arg1"
     *   [1]       => "arg2"
     *   [2]       => "arg3"
     * 
     * [pfisher ~]$ php test.php plain-arg --foo --bar=baz --funny="spam=eggs" --also-funny=spam=eggs \
     * > 'plain arg 2' -abc -k=value "plain arg 3" --s="original" --s='overwrite' --s
     *   [0]       => "plain-arg"
     *   ["foo"]   => true
     *   ["bar"]   => "baz"
     *   ["funny"] => "spam=eggs"
     *   ["also-funny"]=> "spam=eggs"
     *   [1]       => "plain arg 2"
     *   ["a"]     => true
     *   ["b"]     => true
     *   ["c"]     => true
     *   ["k"]     => "value"
     *   [2]       => "plain arg 3"
     *   ["s"]     => "overwrite"
     *
     * @since               August 21, 2009
     * @see                 http://www.php.net/manual/en/features.commandline.php
     *                      #81042 function arguments($argv) by technorati at gmail dot com, 12-Feb-2008
     *                      #78651 function getArgs($args) by B Crawford, 22-Oct-2007
     *
     * @param  unknown $argv Parameter description (if any) ...
     * @return array   Return description (if any) ...
     * @access public 
     */
    public static function parseArgs($argv){

        array_shift($argv);
        $out                            = array();

        foreach ($argv as $arg){

            // --foo --bar=baz
            if (substr($arg,0,2) == '--'){
                $eqPos                  = strpos($arg,'=');

                // --foo
                if ($eqPos === false){
                    $key                = substr($arg,2);
                    $value              = isset($out[$key]) ? $out[$key] : true;
                    $out[$key]          = $value;
                }
                // --bar=baz
                else {
                    $key                = substr($arg,2,$eqPos-2);
                    $value              = substr($arg,$eqPos+1);
                    $out[$key]          = $value;
                }
            }
            // -k=value -abc
            else if (substr($arg,0,1) == '-'){

                // -k=value
                if (substr($arg,2,1) == '='){
                    $key                = substr($arg,1,1);
                    $value              = substr($arg,3);
                    $out[$key]          = $value;
                }
                // -abc
                else {
                    $chars              = str_split(substr($arg,1));
                    foreach ($chars as $char){
                        $key            = $char;
                        $value          = isset($out[$key]) ? $out[$key] : true;
                        $out[$key]      = $value;
                    }
                }
            }
            // plain-arg
            else {
                $value                  = $arg;
                $out[]                  = $value;
            }
        }
        self::$args                     = $out;
        return $out;
    }

    /**
     * GET BOOLEAN
     */
    public static function getBoolean($key, $default = false){
        if (!isset(self::$args[$key])){
            return $default;
        }
        $value                          = self::$args[$key];
        if (is_bool($value)){
            return $value;
        }
        if (is_int($value)){
            return (bool)$value;
        }
        if (is_string($value)){
            $value                      = strtolower($value);
            $map = array(
                'y'                     => true,
                'n'                     => false,
                'yes'                   => true,
                'no'                    => false,
                'true'                  => true,
                'false'                 => false,
                '1'                     => true,
                '0'                     => false,
                'on'                    => true,
                'off'                   => false,
            );
            if (isset($map[$value])){
                return $map[$value];
            }
        }
        return $default;
    }
}
?>
