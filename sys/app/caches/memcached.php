<?
/**
 * Memcached cache
 * 
 * @copyright     Copyright 2009-2012 Jon Gilkison and Trunk Archive Inc
 * @package       application
 *
 * Copyright (c) 2009, Jon Gilkison and Trunk Archive Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * This is a modified BSD license (the third clause has been removed).
 * The BSD license may be found here:
 * 
 * http://www.opensource.org/licenses/bsd-license.php
 */
	
/**
 * Memcached cache
 * 
 * @link          http://wiki.getheavy.info/index.php/Cache
 * @package		application
 * @subpackage	cache
 */
class MemcachedCache extends Cache
{
	/**
	 * Is the cache enabled?
	 * @var bool
	 */
	private $enabled=true;
	
	/**
	 * Memcache instance
	 * @var Memcache
	 */
	private $memcache=null;
	
	
	/**
	 * Constructor 
	 * 
	 * @param Config $config Configuration data, if any.
	 */
	public function __construct($config=null)
	{
		if ($config)
			$this->enabled=$config->enabled;
			
		if ($this->enabled)
			$this->enabled=function_exists('memcache_pconnect');
		
		// create an instance if needed
		$this->memcache = new Memcache();
		
		// add all of the servers
		foreach ($config->servers->items as $server)
			$this->memcache->addServer($server->server, $server->port);
	}
	
	/**
	 * Sets an item in the cache
	 * 
	 * @param string $key The key to set
	 * @param mixed $data The data to store
	 * @param int $ttl Time to live, amount of time to store in cache.  Zero = eternity.
	 */
	public function set($key,$data,$ttl=0)
	{
		if ($this->enabled)
			return $this->memcache->set($key,$data,false,$ttl);
					
		return false;
	}

	/**
	 * Gets an item from the cache
	 * 
	 * @param string $key The key to fetch
	 * @return mixed The data, if no data then null
	 */
	public function get($key)
	{
		if (!$this->enabled)
			return false;
			
		return $this->memcache->get($key);
	}

	/**
	 * Deletes an object from the cache
	 */
	function delete($key)
	{
		if (!$this->enabled)
			return false;
			
		$this->memcache->delete($key);
		
		return true;
	}

	/**
	 * Determines if the cache is enabled.
	 * 
	 * @return bool
	 */
	public function enabled()
	{
		return $this->enabled;
	}
}