<?php
if (!defined('ROOT')) exit('For science.');

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
    private $author;

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
     * Get an array of possible versions
     * @return array
     */
    public function getVersions()
    {
        global $pdo;

        $versions = array();
        $statement = $pdo->prepare('SELECT Version FROM Versions WHERE Plugin = ? ORDER BY Created DESC');
        $statement->execute(array($this->id));

        while (($row = $statement->fetch()) != null)
        {
            $versions[] = $row['Version'];
        }

        return $versions;
    }

    /**
     * Get all of the custom columns available for grapinh for this plugin
     * @return array, [id] => name
     */
    public function getCustomColumns()
    {
        global $pdo;

        $columns = array();
        $statement = $pdo->prepare('SELECT ID, Name FROM CustomColumn WHERE Plugin = ?');
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
     */
    public function sumCustomData($columnID, $min, $max = -1)
    {
        global $pdo;

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $data = array();
        $statement = $pdo->prepare('SELECT SUM(DataPoint) FROM CustomData WHERE ColumnID = ? AND Plugin = ? AND Updated >= ? AND Updated <= ?');
        $statement->execute(array($columnID, $this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Get the amount of servers online and using LWC between two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineCustom($columnID, $minEpoch, $maxEpoch = -1)
    {
        global $pdo;

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();
        $statement = $pdo->prepare('SELECT DataPoint, Epoch FROM CustomDataTimeline WHERE ColumnID = ? AND Plugin = ? AND Epoch >= ? AND Epoch <= ?');
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
        global $pdo;

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $pdo->prepare('SELECT SUM(Players) FROM ServerPlugin LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server WHERE ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ? AND ServerPlugin.Updated <= ?');
        $statement->execute(array($this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Count version changes for epoch times between the min and max
     *
     * @param $min
     * @param $max
     * @return integer
     */
    public function countVersionChanges($min, $max)
    {
        global $pdo;

        $statement = $pdo->prepare('SELECT COUNT(*) FROM VersionHistory WHERE Created >= ? AND Created <= ?');
        $statement->execute(array($min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    /**
     * Get a count of all of the servers using this plugin
     */
    public function countServers()
    {
        global $pdo;

        $statement = $pdo->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ?');
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
        global $pdo;

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ? AND Updated >= ? AND Updated <= ?');
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
        global $pdo;

        // use time() if $max is -1
        if ($max == -1)
        {
            $max = time();
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM ServerPlugin
                                    LEFT OUTER JOIN Server ON (ServerPlugin.Server = Server.ID)
                                    WHERE Country = ? AND ServerPlugin.Plugin = ? AND ServerPlugin.Updated >= ? AND ServerPlugin.Updated <= ?');
        $statement->execute(array($country, $this->id, $min, $max));

        $row = $statement->fetch();
        return $row != null ? $row[0] : 0;
    }

    public function countServersUsingVersion($version)
    {
        global $pdo;
        $weekAgo = time() - SECONDS_IN_WEEK;

        $statement = $pdo->prepare('SELECT COUNT(*) FROM ServerPlugin WHERE Plugin = ? AND Version = ? AND Updated >= ?');
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
        global $pdo;

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $pdo->prepare('SELECT Country, Servers, Epoch FROM CountryTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ?');
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
        global $pdo;

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $pdo->prepare('SELECT Players, Epoch FROM PlayerTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ?');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Players'];
        }

        return $ret;
    }

    /**
     * Get the amount of servers online and using LWC between two epochs
     * @param $minEpoch int
     * @param $maxEpoch int
     * @return array keyed by the epoch
     */
    function getTimelineServers($minEpoch, $maxEpoch = -1)
    {
        global $pdo;

        // use time() if $max is -1
        if ($maxEpoch == -1)
        {
            $maxEpoch = time();
        }

        $ret = array();

        $statement = $pdo->prepare('SELECT Servers, Epoch FROM ServerTimeline WHERE Plugin = ? AND Epoch >= ? AND Epoch <= ?');
        $statement->execute(array($this->id, $minEpoch, $maxEpoch));

        while ($row = $statement->fetch())
        {
            $ret[$row['Epoch']] = $row['Servers'];
        }

        return $ret;
    }

    /**
     * Get a server by its GUID. If not found, this will create it.
     * @param $guid
     * @param $attemptedToCreate
     */
    public function getOrCreateServer($guid, $attemptedToCreate = false)
    {
        global $pdo;

        // Try to select it first
        $statement = $pdo->prepare('SELECT ID, GUID, ServerVersion, Version, Country, Hits, Created, ServerPlugin.Plugin, ServerPlugin.Version, ServerPlugin.Updated FROM Server
                                    LEFT OUTER JOIN ServerPlugin ON (ServerPlugin.Server = Server.ID AND ServerPlugin.Plugin = :Plugin)
                                    WHERE GUID = :GUID');
        $statement->execute(array(':GUID' => $guid, ':Plugin' => $this->id));

        if ($row = $statement->fetch())
        {
            // Exists, begin creating it
            $server = new Server();
            $server->setID($row['ID']);
            $server->setPlugin($this->id);
            $server->setGUID($row['GUID']);
            $server->setCountry($row['Country']);
            $server->setPlayers($row['Players']);
            $server->setServerVersion($row['ServerVersion']);
            $server->setCurrentVersion($row['Version']);
            $server->setHits($row['Hits']);
            $server->setCreated($row['Created']);
            $server->setUpdated($row['Updated']);

            // verify we have the plugin
            $server->verifyPlugin($this->id);

            return $server;
        }

        // Did we already try to create it?
        if ($attemptedToCreate)
        {
            exit('ERR Failed to create server for GUID.');
        }

        // It doesn't exist so we are going to create it ^^
        $statement = $pdo->prepare('INSERT INTO Server (Plugin, GUID, Players, Country, ServerVersion, Hits, Created) VALUES(:Plugin, :GUID, :Players, :Country, :ServerVersion, :Hits, :Created)');
        $statement->execute(array(':Plugin' => $this->id, ':GUID' => $guid, ':Players' => 0, ':Country' => 'ZZ', ':ServerVersion' => '', ':Hits' => 0, ':Created' => time()));

        // get the last id
        $serverId = $pdo->lastInsertId();

        // insert it into ServerPlugin
        $statement = $pdo->prepare('INSERT INTO ServerPlugin (Server, Plugin, Version, Updated) VALUES (:Server, :Plugin, :Version, :Updated)');
        $statement->execute(array(':Server' => $serverId, ':Plugin' => $this->id, ':Version' => '', ':Updated' => time()));

        // reselect it
        return $this->getOrCreateServer($guid, TRUE);
    }

    /**
     * Create the plugin in the database
     */
    public function create()
    {
        global $pdo;

        // Prepare it
        $statement = $pdo->prepare('INSERT INTO Plugin (Name, Author, Hidden, GlobalHits) VALUES (:Name, :Author, :Hidden, :GlobalHits)');

        // Execute
        $statement->execute(array(':Name' => $this->name, ':Author' => $this->author, ':Hidden' => $this->hidden,
            ':GlobalHits' => $this->globalHits));
    }

    /**
     * Save the plugin to the database
     */
    public function save()
    {
        global $pdo;

        // Prepare it
        $statement = $pdo->prepare('UPDATE Plugin SET Name = :Name, Author = :Author, Hidden = :Hidden, GlobalHits = :GlobalHits WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Name' => $this->name, ':Author' => $this->author,
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

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
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