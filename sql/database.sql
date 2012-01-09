CREATE TABLE IF NOT EXISTS Plugin (
  ID INT NOT NULL AUTO_INCREMENT,

  -- Name of the plugin
  Name VARCHAR(20) NOT NULL,

  -- Author of the plugin
  Author VARCHAR(20) NOT NULL,

  -- If the plugin should be hidden from the main page
  Hidden BOOLEAN NOT NULL,

  -- The total amount of hits for the plugin since time started
  GlobalHits INTEGER NOT NULL,

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS Server (
  ID INT NOT NULL AUTO_INCREMENT,

  -- Foreign key to the related plugin
  Plugin INT NOT NULL,

  -- GUID
  GUID VARCHAR(40) NOT NULL,

  -- Last known amount of players to be on the server
  Players INT NOT NULL,

  -- Current server version
  ServerVersion VARCHAR(100) NOT NULL,

  -- The last known version of LWC the server was using
  CurrentVersion VARCHAR(40) NOT NULL,

  -- Incremented each time the server pings us
  Hits INT NOT NULL,

  -- When the server was created on our end; epoch
  Created INTEGER NOT NULL,

  -- When the server last pinged us; epoch
  Updated INTEGER NOT NULL,

  --
  INDEX (GUID),

  --
  INDEX (ServerVersion),

  --
  INDEX (CurrentVersion),

  --
  INDEX (Hits),

  --
  INDEX (Created),

  --
  INDEX (Updated),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS VersionHistory (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  -- Name of the plugin
  Server INT NOT NULL,

  -- The version they changed to
  Version VARCHAR(40) NOT NULL,

  -- The epoch time they changed at
  Created INTEGER NOT NULL,

  --
  INDEX (Plugin),

  --
  INDEX (Server),

  --
  INDEX (Version),

  --
  INDEX (Created),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  FOREIGN KEY (Server) REFERENCES Server (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

-- These tables are using for post-processing raw data into easy to use data
-- for example, getting the amount of servers that were online in the last hour and storing that
CREATE TABLE IF NOT EXISTS PlayerTimeline (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  --
  Players INT NOT NULL,

  -- The unix timestamp this timeline entry refers to
  Epoch INTEGER NOT NULL,

  --
  Index (Plugin),

  --
  INDEX (Players),

  --
  UNIQUE INDEX (Epoch),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS ServerTimeline (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  --
  Servers INT NOT NULL,

  -- The unix timestamp this timeline entry refers to
  Epoch INTEGER NOT NULL,

  --
  Index (Plugin),

  --
  INDEX (Servers),

  --
  UNIQUE INDEX (Epoch),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;