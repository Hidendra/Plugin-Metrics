<?php

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
     * The total amount of hits this plugin has received
     * @var integer
     */
    private $globalHits;

    /**
     * Get a server by its GUID. If not found, this will create it.
     * @param $guid
     * @param $attemptedToCreate
     */
    public function getOrCreateServer($guid, $attemptedToCreate = false)
    {
        global $pdo;

        // Try to select it first
        $statement = $pdo->prepare('SELECT ID, Plugin, GUID, CurrentVersion, Hits, Created, Updated FROM Server WHERE GUID = :GUID');
        $statement->execute(array(':GUID' => $guid));

        if ($row = $statement->fetch())
        {
            // Exists, begin creating it
            $server = new Server();
            $server->setID($row['ID']);
            $server->setPlugin($row['Plugin']);
            $server->setGUID($row['GUID']);
            $server->setCurrentVersion($row['CurrentVersion']);
            $server->setHits($row['Hits']);
            $server->setCreated($row['Created']);
            $server->setUpdated($row['Updated']);

            return $server;
        }

        // Did we already try to create it?
        if ($attemptedToCreate)
        {
            exit('ERR Failed to create server for GUID.');
        }

        // It doesn't exist so we are going to create it ^^

        // now try to create it
        $statement = $pdo->prepare('INSERT INTO Server (Plugin, GUID, CurrentVersion, Hits, Created, Updated) VALUES(:Plugin, :GUID, :CurrentVersion, :Hits, :Created, :Updated)');
        $statement->execute(array(':Plugin' => $this->id, ':GUID' => $guid, 'CurrentVersion' => 'unknown', ':Hits' => 0, ':Created' => time(), ':Updated' => time()));

        // reselect it
        return $this->getOrCreateServer($guid, TRUE);
    }

    /**
     * Save the plugin to the database
     */
    public function save()
    {
        global $pdo;

        // Prepare it
        $statement = $pdo->prepare('UPDATE Plugin SET Name = :Name, GlobalHits = :GlobalHits WHERE ID = :ID');

        // Execute
        $statement->execute(array(':ID' => $this->ID, ':Name' => $this->name, ':GlobalHits' => $this->globalHits));
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

    public function getGlobalHits()
    {
        return $this->globalHits;
    }

    public function setGlobalHits($globalHits)
    {
        $this->globalHits = $globalHits;
    }

}