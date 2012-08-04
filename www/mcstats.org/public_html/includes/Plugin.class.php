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
     * The parent plugin
     * @var integer
     */
    private $parent;

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
     * When the plugin was created
     * @var
     */
    private $created;

    /**
     * Order the plugin's active graphs to have linear position arrangements. For example,
     * [ 1, 2, 5, 1543, 9000, 90001 ]
     * Where [ 1, 9000, 9001 ] are enforced graphs, it will become
     * [ 1, 2, 3, 4, 9000, 90001 ]
     */
    public function orderGraphs()
    {
        $graphs = $this->getActiveGraphs();

        $count = count($graphs);

        // do they even have any custom graphs ?
        if ($count == 3)
        {
            return;
        }

        $current = 2; // the current position to use
        for ($i = 1; $i < $count - 2; $i++)
        {
            $graph = $graphs[$i];
            $graph->setPosition($current++);
            $graph->save();
        }
    }

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
    public function getOrCreateGraph($name, $attemptedToCreate = false, $active = 0, $type = GraphType::Line, $readonly = FALSE, $position = 2)
    {
        global $master_db_handle;

        // Try to get it from the database
        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Readonly, Name, DisplayName, Scale, Position FROM Graph WHERE Plugin = ? AND Name = ?');
        $statement->execute(array($this->id, $name));

        if ($row = $statement->fetch())
        {
            return new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Readonly'], $row['Position'], $row['Scale']);
        }

        if ($attemptedToCreate)
        {
            error_fquit('Failed to create graph for "' . $name . '"');
        }

        $statement = $master_db_handle->prepare('INSERT INTO Graph (Plugin, Type, Name, DisplayName, Active, Readonly, Position)
                                                            VALUES(:Plugin, :Type, :Name, :DisplayName, :Active, :Readonly, :Position)');
        $statement->execute(array(
            ':Plugin' => $this->id,
            ':Type' => $type,
            ':Name' => $name,
            ':DisplayName' => $name,
            ':Active' => $active,
            ':Readonly' => $readonly ? 1 : 0,
            ':Position' => $position
        ));

        // reselect it
        return $this->getOrCreateGraph($name, TRUE, $active, $readonly);
    }

    /**
     * Load a graph using its ID
     * @param $id integer
     * @return Graph if found, otherwise NULL
     */
    public function getGraph($id)
    {
        global $master_db_handle;

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Readonly, Name, DisplayName, Scale, Position FROM Graph WHERE ID = ?');
        $statement->execute(array($id));

        if ($row = $statement->fetch())
        {
            return new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Readonly'], $row['Position'], $row['Scale']);
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

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active, Readonly, Name, DisplayName, Scale, Position FROM Graph WHERE Plugin = ? AND Active = 1 ORDER BY Position ASC');
        $statement->execute(array($this->id));

        while ($row = $statement->fetch())
        {
            $graphs[] = new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Readonly'], $row['Position'], $row['Scale']);
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

        $statement = $master_db_handle->prepare('SELECT ID, Plugin, Type, Active,Readonly,  Name, DisplayName, Scale, Position FROM Graph WHERE Plugin = ? ORDER BY Active DESC, Position ASC');
        $statement->execute(array($this->id));

        while ($row = $statement->fetch())
        {
            $graphs[] = new Graph($row['ID'], $this, $row['Type'], $row['Name'], $row['DisplayName'], $row['Active'], $row['Readonly'], $row['Position'], $row['Scale']);
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
    public function sumCustomData($columnID, $min, $max = -1, $table = 'CustomData')
    {
        $db_handle = get_slave_db_handle();

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $db_handle->prepare('SELECT SUM(DataPoint) FROM ' . $table . ' WHERE ColumnID = ? AND Plugin = ? AND Updated >= ? AND Updated <= ?');
        $statement->execute(array($columnID, $this->id, $min, $max));

        $row = $statement->fetch();
        return is_numeric($row[0]) ? $row[0] : 0;
    }

    /**
     * Get the custom timeline data for all times between the two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCustom($columnID, $minEpoch)
    {
        $db_handle = get_slave_db_handle();

        $ret = array();
        $statement = $db_handle->prepare('SELECT Sum, Epoch FROM CustomDataTimeline WHERE Plugin = ? AND ColumnID = ? AND Epoch >= ?');
        $statement->execute(array($this->id, $columnID, $minEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Sum'];
        }

        return $ret;
    }

    /**
     * Get the custom timeline data for the last graph that was generated
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCustomLast($columnID)
    {
        $db_handle = get_slave_db_handle();

        $epoch = getLastGraphEpoch();
        $ret = array();
        $statement = $db_handle->prepare('SELECT Sum FROM CustomDataTimeline WHERE ColumnID = ? AND Plugin = ? AND Epoch = ?');
        $statement->execute(array($columnID, $this->id, $epoch));

        while ($row = $statement->fetch())
        {
            $sum = $row['Sum'];
            $ret[$epoch] = $sum;
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
        $weekAgo = time() - SECONDS_IN_DAY;

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

        $statement = $db_handle->prepare('SELECT Country, Servers, Epoch FROM CountryTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ? ORDER BY Epoch DESC');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']][$row['Country']] = $row['Servers'];
        }

        return $ret;
    }

    /**
     * Gets the last set of country timelines
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCountryLast()
    {
        $db_handle = get_slave_db_handle();

        $ret = array();

        $epoch = getLastGraphEpoch();
        $statement = $db_handle->prepare('SELECT Country, Servers FROM CountryTimeline WHERE Plugin = ? AND Epoch = ?');
        $statement->execute(array($this->id, $epoch));

        while ($row = $statement->fetch())
        {
            $country = $row['Country'];
            $servers = $row['Servers'];

            $ret[$epoch][$country] = $servers;
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
        $statement = $master_db_handle->prepare('INSERT INTO Plugin (Name, Author, Hidden, GlobalHits, Created) VALUES (:Name, :Author, :Hidden, :GlobalHits, UNIX_TIMESTAMP())');

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
        $statement = $master_db_handle->prepare('UPDATE Plugin SET Name = :Name, Author = :Author, Hidden = :Hidden, GlobalHits = :GlobalHits, Created = :Created WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Name' => $this->name, ':Author' => $this->authors,
            ':Hidden' => $this->hidden, ':GlobalHits' => $this->globalHits, ':Created' => $this->created));
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

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param  $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

}