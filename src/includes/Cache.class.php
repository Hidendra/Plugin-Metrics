<?php

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
     * Connect to the caching engine
     */
    public function connect()
    {
        $this->handle->connect('127.0.0.1', 11211);
    }

    /**
     * Get an object from the cache
     * @param $key string
     * @return object The result
     */
    public function get($key)
    {
        return $this->handle->get($key);
    }

    /**
     * Store a key/value pair in the cache
     * @param $key string The key to store as
     * @param $value object The value to store
     * @param $expire int The number of seconds to expire in
     * @return TRUE on success and FALSE on failure
     */
    public function set($key, $value, $expire)
    {
        return $this->handle->set($key, $value, false, $expire);
    }

}