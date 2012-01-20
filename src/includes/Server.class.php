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

    public function addVersionHistory($version)
    {
        global $pdo;

        $statement = $pdo->prepare('INSERT INTO VersionHistory (Plugin, Server, Version, Created) VALUES (:Plugin, :Server, :Version, :Created)');
        $statement->execute(array(':Plugin' => $this->plugin, ':Server' => $this->id, ':Version' => $version, ':Created' => time()));
        $statement = $pdo->prepare('INSERT INTO Versions (Plugin, Version, Created) VALUES (:Plugin, :Version, :Created)');
        $statement->execute(array(':Plugin' => $this->plugin, ':Version' => $version, ':Created' => time()));
    }

    /**
     * Save the server to the database
     */
    public function save()
    {
        global $pdo;

        // set the last updated time to now
        $this->setUpdated(time());

        // Prepare it
        $statement = $pdo->prepare('UPDATE Server SET Plugin = :Plugin, GUID = :GUID, Players = :Players, ServerVersion = :ServerVersion, Hits = :Hits, Created = :Created WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Plugin' => $this->plugin, ':Players' => $this->players, ':GUID' => $this->guid, ':ServerVersion' => $this->serverVersion,
            ':Hits' => $this->hits, ':Created' => $this->created));

        // update the plugin part of it
        $this->updatePlugin();
    }

    /**
     * Update the plugin
     */
    public function updatePlugin()
    {
        global $pdo;

        // inserts or updates into the ServerPlugin table
        $statement = $pdo->prepare('UPDATE ServerPlugin SET Version = :Version, Updated = :Updated WHERE Server = :Server AND Plugin = :Plugin');

        // Execute
        $statement->execute(array(':Server' => $this->id, ':Plugin' => $this->plugin, ':Version' => $this->currentVersion, ':Updated' => $this->getUpdated()));
    }

    /**
     * Get or create a custom column and return the id
     *
     * @param $columnName string
     * @return int
     */
    public function getCustomColumnID($columnName, $attemptedToCreate = false) {
        global $pdo;

        // Execute  the query
        $statement = $pdo->prepare('SELECT ID FROM CustomColumn WHERE Name = ?');
        $statement->execute(array($columnName));

        // Did we get it?
        if ($row = $statement->fetch())
        {
            return $row['ID'];
        }

        if ($attemptedToCreate)
        {
            throw new Exception("Failed to create custom column: $columnName");
        }

        // Nope...
        $statement = $pdo->prepare('INSERT INTO CustomColumn (Plugin, Name) VALUES (:Plugin, :Name)');
        $statement->execute(array(':Plugin' => $this->plugin, ':Name' => $columnName));

        return $this->getCustomColumnID($columnName, true);
    }

    /**
     * Add custom data to the CustomData table. This assumes the value field has already been validated as a number
     *
     * @param $columnName string
     * @param $value int
     */
    public function addCustomData($columnName, $value) {
        global $pdo;

        // get the id for the column
        $columnID = $this->getCustomColumnID($columnName);

        // Does the server already have a data point for this column?
        $statement = $pdo->prepare('SELECT ID FROM CustomData WHERE Server = :Server AND Plugin = :Plugin AND ColumnID = :ColumnID');
        $statement->execute(array(':Server' => $this->id, ':Plugin' => $this->plugin, ':ColumnID' => $columnID));

        // If we found it, update it instead
        if ($row = $statement->fetch()) {
            $id = $row['ID'];

            $statement = $pdo->prepare('UPDATE CustomData SET DataPoint = :DataPoint, Updated = :Updated WHERE ID = :ID');
            $statement->execute(array(':DataPoint' => $value, ':Updated' => time(), ':ID' => $id));
            return;
        }

        // Not there yet, insert it
        $statement = $pdo->prepare('INSERT INTO CustomData (Server, Plugin, ColumnID, DataPoint, Updated) VALUES (:Server, :Plugin, :ColumnID, :DataPoint, :Updated)');
        $statement->execute(array(
            ':Server' => $this->id,
            ':Plugin' => $this->plugin,
            ':ColumnID' => $this->getCustomColumnID($columnName),
            ':DataPoint' => $value,
            ':Updated' => time()
        ));
    }

    /**
     * Increment the hits for the server
     */
    public function incrementHits()
    {
        $this->hits += 1;
    }

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    public function getGUID()
    {
        return $this->guid;
    }

    public function setGUID($guid)
    {
        $this->guid = $guid;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function setPlayers($players)
    {
        $this->players = $players;
    }

    public function getServerVersion()
    {
        return $this->serverVersion;
    }

    public function setServerVersion($serverVersion)
    {
        $this->serverVersion = $serverVersion;
    }

    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

}