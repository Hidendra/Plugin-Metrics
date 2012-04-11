<?php

/// Expire the set cached value at the next graph generation
const CACHE_UNTIL_NEXT_GRAPH = -25;

/**
 * Handles caching, by default with memcached
 */
class Cache
{

    /**
     * The handle to the caching object
     * @var Memcache
     */
    private $handle;

    public function __construct()
    {
        $this->handle = new Memcache();
        $this->connect();
    }

    /**
     * @return TRUE if caching is enabled, otherwise FALSE
     */
    public function isEnabled()
    {
        global $config;
        return $config['cache']['enabled'];
    }

    /**
     * Connect to the caching engine
     */
    public function connect()
    {
        if (!$this->isEnabled())
        {
            return;
        }

        $this->handle->connect('127.0.0.1', 11211);
    }

    /**
     * Get an object from the cache
     * @param $key string
     * @return object The result
     */
    public function get($key)
    {
        if (!$this->isEnabled())
        {
            return null;
        }

        return $this->handle->get($key);
    }

    /**
     * Store a key/value pair in the cache
     * @param $key string The key to store as
     * @param $value object The value to store
     * @param $expire int The number of seconds to expire in, 0 for forever
     * @return TRUE on success and FALSE on failure
     */
    public function set($key, $value, $expire = 0)
    {
        if (!$this->isEnabled())
        {
            return FALSE;
        }

        // Check for flags
        if ($expire == CACHE_UNTIL_NEXT_GRAPH)
        {
            $expire = timeUntilNextGraph() - time();
        }

        return $this->handle->set($key, $value, false, $expire);
    }

}