<?php

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

        $statement = $pdo->prepare('INSERT INTO VersionHistory (Server, Version, Created) VALUES (:Server, :Version, :Created)');
        $statement->execute(array(':Server' => $this->id, ':Version' => $version, ':Created' => time()));
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
        $statement = $pdo->prepare('UPDATE Server SET Plugin = :Plugin, GUID = :GUID, CurrentVersion = :CurrentVersion, Hits = :Hits, Created = :Created, Updated = :Updated WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->id, ':Plugin' => $this->plugin, ':GUID' => $this->guid, ':CurrentVersion' => $this->currentVersion,
            ':Hits' => $this->hits, ':Created' => $this->created, ':Updated' => $this->updated));
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