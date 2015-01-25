<?php

/**
 * include/cache.class.php
 *
 * This class is a wrapper for underlying memory cache tools (REDIS)
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
 * @copyright 2009-2015 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

class Cache
{
	public $REDIS;

	public function __construct( $PARAMS, $OPTIONS )
	{
		require_once("predis/Autoloader.php");
		Predis\Autoloader::register();
		$this->REDIS = new Predis\Client($PARAMS,$OPTIONS);
	}

	public function auth($AUTH)
	{
		return $this->REDIS->auth($AUTH);
	}

	public function get($KEY)
	{
		return $this->REDIS->get($KEY);
	}

	public function set($KEY,$VALUE)
	{
		return $this->REDIS->set($KEY,$VALUE);
	}

	public function del($KEY)
	{
		return $this->REDIS->del($KEY);
	}
}

?>
