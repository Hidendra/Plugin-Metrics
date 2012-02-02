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

  -- The server's country, 2 letter country code
  Country CHAR(2) NOT NULL,

  -- Last known amount of players to be on the server
  Players INT NOT NULL,

  -- Current server version
  ServerVersion VARCHAR(100) NOT NULL,

  -- Incremented each time the server pings us
  Hits INT NOT NULL,

  -- When the server was created on our end; epoch
  Created INTEGER NOT NULL,

  --
  INDEX (GUID),

  --
  INDEX (Country),

  --
  INDEX (ServerVersion),

  --
  INDEX (Hits),

  --
  INDEX (Created),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

-- Stores which plugins a server is using
-- note: no primary key, only relation between Server <-> Plugin
CREATE TABLE IF NOT EXISTS ServerPlugin (
  -- FK to Server
  Server INT NOT NULL,

  -- FK to Plugin
  Plugin INT NOT NULL,

  -- The last known version of LWC the server was using
  Version VARCHAR(40) NOT NULL,

  -- epoch of when the plugin last pinged us
  Updated INTEGER NOT NULL,

  -- We only want one of each
  UNIQUE INDEX (Server, Plugin),

  -- FK
  INDEX (Server),

  -- FK
  INDEX (Plugin),

  --
  INDEX (Version),

  --
  INDEX (Updated),

  --
  INDEX (Plugin, Version, Updated),

  FOREIGN KEY (Server) REFERENCES Server (ID),
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID)
);

-- Custom plugin-created columns
CREATE TABLE IF NOT EXISTS CustomColumn (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  --
  Name VARCHAR(100) NOT NULL,

  -- FK
  INDEX (Plugin),
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  INDEX (Name),

  PRIMARY KEY (ID)
);

-- Custom plugin data
-- This is unique per Server / Plugin / Column. Every hour, the data for the past hour
-- is collected into CustomDataTimeline which is graphed
CREATE TABLE IF NOT EXISTS CustomData (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Server INT NOT NULL,

  --
  Plugin INT NOT NULL,

  --
  ColumnID INT NOT NULL,

  --
  DataPoint INT NOT NULL,

  --
  Updated INTEGER NOT NULL,

  -- FK
  INDEX (Server),

  -- FK
  INDEX (Plugin),

  -- FK
  INDEX (ColumnID),

  --
  INDEX (Updated),

  FOREIGN KEY (Server) REFERENCES Server (ID),
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),
  FOREIGN KEY (ColumnID) REFERENCES CustomColumn (ID),

  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS CustomDataTimeline (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  --
  ColumnID INT NOT NULL,

  --
  DataPoint INT NOT NULL,

  -- The unix timestamp this timeline entry refers to
  Epoch INTEGER NOT NULL,

  --
  Index (Plugin),

  -- FK
  INDEX (ColumnID),

  --
  UNIQUE INDEX (Plugin, ColumnID, Epoch),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),
  FOREIGN KEY (ColumnID) REFERENCES CustomColumn (ID),

  --
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS Country (
  -- 2char representation of the country e.g CA
  ShortCode CHAR(2) NOT NULL,

  -- The full name of the country e.g Canada
  FullName VARCHAR(40) NOT NULL,

  PRIMARY KEY (ShortCode)
);
INSERT INTO Country (ShortCode, FullName) VALUES ('ZZ' , 'Unknown');

--
CREATE TABLE IF NOT EXISTS Versions (
  --
  Plugin INT NOT NULL,

  -- The version they changed to
  Version VARCHAR(40) NOT NULL,

  -- The epoch time they changed at
  Created INTEGER NOT NULL,

  --
  INDEX (Plugin),

  --
  INDEX (Created),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (Version)
);

-- deprecated
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
  UNIQUE INDEX (Plugin, Epoch),

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
  UNIQUE INDEX (Plugin, Epoch),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS CountryTimeline (
  ID INT NOT NULL AUTO_INCREMENT,

  --
  Plugin INT NOT NULL,

  --
  Country CHAR(2) NOT NULL,

  --
  Servers INT NOT NULL,

  -- The unix timestamp this timeline entry refers to
  Epoch INTEGER NOT NULL,

  --
  Index (Plugin),

  --
  INDEX (Servers),

  --
  UNIQUE INDEX (Plugin, Country, Epoch),

  --
  FOREIGN KEY (Plugin) REFERENCES Plugin (ID),

  --
  FOREIGN KEY (Country) REFERENCES Country (ShortCode),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;