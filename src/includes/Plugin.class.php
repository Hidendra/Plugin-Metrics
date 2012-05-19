<?php
if (!defined('ROOT')) exit('For science.');

// Minimum countries required to display 'Others'
define('MINIMUM_FOR_OTHERS', 15);

class Plugin
{
    /**
     * Internal id
     * @var integer
     */
    private $id;

    /**
     * The plugin's name
     * @var string
     */
    private $name;

    /**
     * The plugin's author
     * @var string
     */
    private $authors;

    /**
     * If the plugin is hidden from the main page
     * @var boolean
     */
    private $hidden;

    /**
     * The total amount of hits this plugin has received
     * @var integer
     */
    private $globalHits;

    /**
     * Get the key is prefixed to entries stored in the cache
     * @return string
     */
    private function cacheKey()
    {
        return 'plugin-' . $this->id;
    }

    /**
     * Loads a graph from the database and if it does not exist, initialize an empty graph in the
     * database and return it
     *
     * @param $name
     * @return Graph
     */
    public function getOrCreateGraph($name, $attemptedToCreate = false, $active = 0)
    {
        global $master_db_handle;

        // Try to get it from the database
        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Name, DisplayName, Scale FROM Graph WHERE Plugin = ? AND Name = ?');
        $statement->execute(array($this->id, $name));

        if ($row = $statement->fetch())
        {
            return new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Scale']);
        }

        if ($attemptedToCreate)
        {
            error_fquit('Failed to create graph for "' . $name . '"');
        }

        $statement = $master_db_handle->prepare('INSERT INTO Graph (Plugin, Type, Name, DisplayName, Active) VALUES(:Plugin, :Type, :Name, :DisplayName, :Active)');
        $statement->execute(array(':Plugin' => $this->id, ':Type' => GraphType::Line, ':Name' => $name, ':DisplayName' => $name, ':Active' => $active));

        // reselect it
        return $this->getOrCreateGraph($name, TRUE);
    }

    /**
     * Load a graph using its ID
     * @param $id integer
     * @return Graph if found, otherwise NULL
     */
    public function getGraph($id)
    {
        global $master_db_handle;

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Name, DisplayName, Scale FROM Graph WHERE ID = ?');
        $statement->execute(array($id));

        if ($row = $statement->fetch())
        {
            return new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Scale']);
        }

        return NULL;
    }

    /**
     * Gets all of the active graphs for the plugin
     * @return Graph[]
     */
    public function getActiveGraphs()
    {
        global $master_db_handle;

        // The graphs to return
        $graphs = array();

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Name, DisplayName, Scale FROM Graph WHERE Plugin = ? AND Active = 1 ORDER BY ID asc');
        $statement->execute(array($this->id));

        while ($row = $statement->fetch())
        {
            $graphs[] = new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Scale']);
        }

        return $graphs;
    }

    /**
     * Gets all of the graphs for the plugin
     * @return Graph[]
     */
    public function getAllGraphs()
    {
        global $master_db_handle;

        // The graphs to return
        $graphs = array();

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Name, DisplayName, Scale FROM Graph WHERE Plugin = ? ORDER BY Active DESC');
        $statement->execute(array($this->id));

        while ($row = $statement->fetch())
        {
            $graphs[] = new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Scale']);
        }

        return $graphs;
    }

    /**
     * Set a key and value unique to this plugin into the caching daemon
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
     * Get a server by its GUID. If not found, this will create it.
     * @param $guid
     * @param $attemptedToCreate
     */
    public function getOrCreateServer($guid, $attemptedToCreate = false)
    {
        global $master_db_handle;

        // Try to select it first
        $statement = get_slave_db_handle()->prepare('SELECT Server.ID, GUID, ServerVersion, Country, Hits, Created, Players, Plugin, ServerPlugin.Version, ServerPlugin.Updated
                                                FROM Server
                                                LEFT OUTER JOIN ServerPlugin ON ServerPlugin.Server = Server.ID
                                                WHERE GUID = :GUID');
        $statement->execute(array(':GUID' => $guid));

        // The server object
        $server = NULL;

        while ($row = $statement->fetch())
        {
            if ($server === NULL)
            {
                $server = new Server();
                $server->setID($row['ID']);
                $server->setPlugin($this->id);
                $server->setGUID($row['GUID']);
                $server->setCountry($row['Country']);
                $server->setPlayers($row['Players']);
                $server->setServerVersion($row['ServerVersion']);
                $server->setHits($row['Hits']);
                $server->setCreated($row['Created']);
                $server->setModified(false);
            }

            if ($row['Plugin'] == $this->id)
            {
                $server->setCurrentVersion($row['Version']);
                $server->setUpdated($row['Updated']);
                $server->setModified(false);
                return $server;
            }
        }

        // Do we need to add the plugin?
        if ($server !== NULL)
        {
            $statement = $master_db_handle->prepare('INSERT INTO ServerPlugin (Server, Plugin, Version, Updated) VALUES (:Server, :Plugin, :Version, :Updated)');
            $statement->execute(array(':Server' => $server->getID(), ':Plugin' => $this->id, ':Version' => '', ':Updated' => time()));

            $server->setUpdated(time());
            $server->setModified(false);

            // Return the server object
            return $server;
        }

        // Did we already try to create it?
        if ($attemptedToCreate)
        {
            error_fquit($this->name . ': Failed to create server for "' . $guid . '"');
        }

        // It doesn't exist so we are going to create it ^^
        $statement = $master_db_handle->prepare('INSERT INTO Server (GUID, Players, Country, ServerVersion, Hits, Created) VALUES(:GUID, :Players, :Country, :ServerVersion, :Hits, :Created)');
        $statement->execute(array(':GUID' => $guid, ':Players' => 0, ':Country' => 'ZZ', ':ServerVersion' => '', ':Hits' => 0, ':Created' => time()));

        // reselect it
        return $this->getOrCreateServer($guid, TRUE);
    }

    /**
     * Get an array of possible versions
     * @return array
     */
    public function getVersions()
    {
        $db_handle = get_slave_db_handle();

        $versions = array();
        $statement = $db_handle->prepare('SELECT ID, Version FROM Versions WHERE Plugin = ? ORDER BY Created DESC');
        $statement->execute(array($this->id));

        while (($row = $statement->fetch()) != null)
        {
            $versions[$row['ID']] = $row['Version'];
        }

        return $versions;
    }

    /**
     * Get all of the custom columns available for grapinh for this plugin
     * @return array, [id] => name
     * @deprecated
     */
    public function getCustomColumns()
    {
        $db_handle = get_slave_db_handle();

        $columns = array();
        $statement = $db_handle->prepare('SELECT ID, Name FROM CustomColumn WHERE Plugin = ?');
        $statement->execute(array($this->id));

        while (($row = $statement->fetch()) != null)
        {
            $id = $row['ID'];
            $name = $row['Name'];
            $columns[$id] = $name;
        }

        return $columns;
    }

    /**
     * Sum all of the data points for a custom column
     * @param $columnID int
     * @param $min int
     * @return int
     * @deprecated
     */
    public function sumCustomData($columnID, $min, $max = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT SUM(DataPoint) FROM CustomData WHERE ColumnID = ? AND Plugin = ? AND Updated >= ? AND Updated <= ?');
        $statement->execute(array($columnID, $this->id, $min, $max));

        $row = $statement->fetch();
        return is_numeric($row[0]) ? $row[0] : 0;
    }

    /**
     * Get the amount of servers online and using LWC between two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCustom($columnID, $minEpoch, $maxEpoch = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();
        $statement = $db_handle->prepare('SELECT DataPoint, Epoch FROM CustomDataTimeline WHERE ColumnID = ? AND Plugin = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch ASC');
        $statement->execute(array($columnID, $this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['DataPoint'];
        }

        return $ret;
    }

    /**
     * Sum all of the current player counts for servers that have pinged the server in the last hour
     * @param $after integer
     */
    public function sumPlayersOfServersLastUpdated($min, $max = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT SUM(Players) FROM ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ? AND ServerPlugin.Updated <= ?');
        $statement->execute(array($this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Count version changes for epoch times between the min and max for the given version
     *
     * @param $version
     * @param $min
     * @param $max
     * @return integer
     */
    public function countVersionChanges($version, $min, $max = -1)
    {
        $db_handle = get_slave_db_handle();

        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT COUNT(*) FROM VersionHistory WHERE Version = ? AND Created >= ? AND Created <= ?');
        $statement->execute(array($version, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Get a count of all of the servers using this plugin
     */
    public function countServers()
    {
        $db_handle = get_slave_db_handle();

        $statement = $db_handle->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ?');
        $statement->execute(array($this->id));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Count all of the servers that were updated after the given epoch
     * @param $after integer
     */
    public function countServersLastUpdated($min, $max = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ? AND Updated >= ? AND Updated <= ?');
        $statement->execute(array($this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Count all of the servers that were updated after the given epoch
     * @param $after integer
     */
    public function countServersLastUpdatedFromCountry($country, $min, $max = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT COUNT(*) FROM ServerPlugin
                                    LEFT OUTER JOIN Server ON (ServerPlugin.Server = Server.ID)
                                    WHERE Country = ? AND ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ? AND ServerPlugin.Updated <= ?');
        $statement->execute(array($country, $this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    public function countServersUsingVersion($version)
    {
        $db_handle = get_slave_db_handle();
        $weekAgo = time() - SECONDS_IN_WEEK;

        $statement = $db_handle->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ? AND Version = ? AND Updated >= ?');
        $statement->execute(array($this->id, $version, $weekAgo));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Get country timeline data between two epochs, showing the amount of players online per country
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCountry($minEpoch, $maxEpoch = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $db_handle->prepare('SELECT Country, Servers, Epoch FROM CountryTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch ASC');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']][$row['Country']] = $row['Servers'];
        }

        return $ret;
    }

    /**
     * Get the amount of players online between two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelinePlayers($minEpoch, $maxEpoch = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $db_handle->prepare('SELECT Players, Epoch FROM PlayerTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch ASC');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Players'];
        }

        return $ret;
    }

    /**
     * Get the amount of servers online and using the plugin between two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineServers($minEpoch, $maxEpoch = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $db_handle->prepare('SELECT Servers, Epoch FROM ServerTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch ASC');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Servers'];
        }

        return $ret;
    }

    /**
     * Get the amount of servers that used a given version between the given epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineVersion($versionID, $minEpoch, $maxEpoch = -1)
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $db_handle->prepare('SELECT Count, Epoch FROM VersionTimeline WHERE Plugin = ? AND Version = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch ASC');
        $statement->execute(array($this->id, $versionID, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Count'];
        }

        return $ret;
    }

    /**
     * Create the plugin in the database
     */
    public function create()
    {
        global $master_db_handle;

        // Prepare it
        $statement = $master_db_handle->prepare('INSERT INTO Plugin (Name, Author, Hidden, GlobalHits) VALUES (:Name, :Author, :Hidden, :GlobalHits)');

        // Execute
        $statement->execute(array(':Name' => $this->name, ':Author' => $this->authors, ':Hidden' => $this->hidden,
            ':GlobalHits' => $this->globalHits));
    }

    /**
     * Save the plugin to the database
     */
    public function save()
    {
        global $master_db_handle;

        // Prepare it
        $statement = $master_db_handle->prepare('UPDATE Plugin SET Name = :Name, Author = :Author, Hidden = :Hidden, GlobalHits = :GlobalHits WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Name' => $this->name, ':Author' => $this->authors,
            ':Hidden' => $this->hidden, ':GlobalHits' => $this->globalHits));
    }

    /**
     * Increment the global hits for the plugin and save
     */
    public function incrementGlobalHits()
    {
        $this->globalHits += 1;
        $this->save();
    }

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAuthors()
    {
        return $this->authors;
    }

    public function setAuthors($author)
    {
        $this->authors = $author;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    public function getGlobalHits()
    {
        return $this->globalHits;
    }

    public function setGlobalHits($globalHits)
    {
        $this->globalHits = $globalHits;
    }

}