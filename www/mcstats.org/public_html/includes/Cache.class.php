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

    public function __construct($handle = null)
    {
        if ($handle === NULL)
        {
            $this->handle = new Memcached('memcached_pool');
            $this->connect();
        } else
        {
            $this->handle = $handle;
        }
    }

    /**
     * Get the caching daemon handle
     */
    public function handle()
    {
        return $this->handle;
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

        $list = $this->handle->getServerList();
        if (empty($list))
        {
            $this->handle->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
            $this->handle->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
            $this->handle->setOption(Memcached::OPT_TCP_NODELAY, true);
            $this->handle->addServer('127.0.0.1', 11211); // TODO configuration
        }
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

        return $this->handle->get(json_decode($key));
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

        return $this->handle->setByKey($key, json_encode($value), false, $expire);
    }

}