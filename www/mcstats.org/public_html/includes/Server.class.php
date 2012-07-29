<?php
if (!defined('ROOT')) exit('For science.');

class Server
{

    /**
     * Internal id for the stat
     * @var integer
     */
    private $id;

    /**
     * Foreign key to the plugin owner
     * @var integer
     */
    private $plugin;

    /**
     * The server's GUID
     * @var string
     */
    private $guid;

    /**
     * The server's 2-char country code
     * $var string
     */
    private $country = 'ZZ';

    /**
     * Last known amount of players on the server
     * @var integer
     */
    private $players;

    /**
     * The server's software version
     * @var string
     */
    private $serverVersion;

    /**
     * The server's last known version
     * @var string
     */
    private $currentVersion;

    /**
     * Amount of hits (start-ups) from the server
     * @var integer
     */
    private $hits;

    /**
     * Epoch of creation time
     * @var long
     */
    private $created;

    /**
     * Epoch of time last updated
     * @var long
     */
    private $updated;

    /**
     * If the server has been modified (so we don't have to save it if possible.)
     *
     * @var bool
     */
    private $modified = false;

    /**
     * True if the version changed at all
     *
     * @var bool
     */
    public $versionChanged = false;

    /**
     * Get the key is prefixed to entries stored in the cache
     * @return string
     */
    private function cacheKey()
    {
        return 'server-' . $this->id;
    }

    /**
     * Set a key and value unique to this server into the caching daemon
     *
     * @param $key
     * @param $value
     * @param $expire Seconds to expire the cached value in. Defaults to the next caching interval
     * @return TRUE on success and FALSE on failure
     */
    public function cacheSet($key, $value, $expire = CACHE_UNTIL_NEXT_GRAPH)
    {
        global $cache;
        return $cache->set($this->cacheKey() . $key, $value, $expire);
    }

    /**
     * Get a key from the caching daemon
     *
     * @param $key
     * @return the object returned from the cache
     */
    public function cacheGet($key)
    {
        global $cache;
        return $cache->get($this->cacheKey() . $key);
    }

    /**
     * Check if the server is blacklisted
     * @return bool
     */
    public function isBlacklisted()
    {
        global $master_db_handle;

        $statement = get_slave_db_handle()->prepare('SELECT Server FROM ServerBlacklist WHERE Server = ?');
        $statement->execute(array($this->id));

        return $statement->fetch() != FALSE;
    }

    public function addVersionHistory($version)
    {
        global $master_db_handle;

        $statement = get_slave_db_handle()->prepare('SELECT ID FROM Versions WHERE Plugin = :Plugin AND Version = :Version');
        $statement->execute(array(':Plugin' => $this->plugin, ':Version' => $version));

        if ($row = $statement->fetch()) {
            $versionID = $row['ID'];
        } else
        {
            $statement = $master_db_handle->prepare('INSERT INTO Versions (Plugin, Version, Created) VALUES (:Plugin, :Version, :Created)');
            $statement->execute(array(':Plugin' => $this->plugin, ':Version' => $version, ':Created' => time()));
            $versionID = $master_db_handle->lastInsertId();
        }

        $statement = $master_db_handle->prepare('INSERT INTO VersionHistory (Plugin, Server, Version, Created) VALUES (:Plugin, :Server, :Version, :Created)');
        $statement->bindValue(':Plugin', intval($this->plugin), PDO::PARAM_INT);
        $statement->bindValue(':Server', intval($this->id), PDO::PARAM_INT);
        $statement->bindValue(':Version', intval($versionID), PDO::PARAM_INT);
        $statement->bindValue(':Created', time(), PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Save the server to the database
     */
    public function save()
    {
        global $master_db_handle;

        // set the last updated time to now
        $this->setUpdated(time());

        // Prepare it
        $statement = $master_db_handle->prepare('UPDATE Server SET Players = :Players, Country = :Country, ServerVersion = :ServerVersion, Hits = :Hits, Created = :Created WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Players' => $this->players, ':Country' => $this->country,
            ':ServerVersion' => $this->serverVersion, ':Hits' => $this->hits, ':Created' => $this->created));

        // update the plugin part of it
        $this->updatePlugin();
        $this->modified = false;
    }

    /**
     * Update the plugin
     */
    public function updatePlugin()
    {
        global $master_db_handle;

        // inserts or updates into the ServerPlugin table
        $statement = $master_db_handle->prepare('UPDATE ServerPlugin SET Version = :Version , Updated = :Updated WHERE Server = :Server AND Plugin = :Plugin');
        $statement->bindValue(':Server', intval($this->getID()), PDO::PARAM_INT);
        $statement->bindValue(':Plugin', intval($this->getPlugin()), PDO::PARAM_INT);
        $statement->bindValue(':Updated', intval($this->getUpdated()), PDO::PARAM_INT);
        $statement->bindValue(':Version', $this->getCurrentVersion(), PDO::PARAM_STR);

        // Execute
        $statement->execute();
    }

    /**
     * Verify the server has the given plugin
     * $param $plugin int
     */
    public function verifyPlugin($plugin)
    {
        global $master_db_handle;

    }

    /**
     * Get or create a custom column and return the id
     *
     * @param $columnName string
     * @return int
     * @deprecated
     */
    public function getCustomColumnID($columnName, $attemptedToCreate = false) {
        global $master_db_handle;

        // Execute  the query
        $statement = get_slave_db_handle()->prepare('SELECT ID FROM CustomColumn WHERE Name = ?');
        $statement->execute(array($columnName));

        // Did we get it?
        if ($row = $statement->fetch())
        {
            return $row['ID'];
        }

        if ($attemptedToCreate)
        {
            error_fquit("Failed to create custom column: $columnName");
        }

        // Nope...
        $statement = $master_db_handle->prepare('INSERT INTO CustomColumn (Plugin, Name) VALUES (:Plugin, :Name)');
        $statement->execute(array(':Plugin' => $this->plugin, ':Name' => $columnName));

        return $this->getCustomColumnID($columnName, true);
    }

    /**
     * Increment the hits for the server
     */
    public function incrementHits()
    {
        $this->hits += 1;
        $this->modified = true;
    }

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
        $this->modified = true;
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
        $this->modified = true;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        $this->modified = true;
    }

    public function getGUID()
    {
        return $this->guid;
    }

    public function setGUID($guid)
    {
        $this->guid = $guid;
        $this->modified = true;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function setPlayers($players)
    {
        $this->players = $players;
        $this->modified = true;
    }

    public function getServerVersion()
    {
        return $this->serverVersion;
    }

    public function setServerVersion($serverVersion)
    {
        $this->serverVersion = $serverVersion;
        $this->modified = true;
    }

    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
        $this->modified = true;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;
        $this->modified = true;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
        $this->modified = true;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    public function isModified()
    {
        return $this->modified;
    }

}