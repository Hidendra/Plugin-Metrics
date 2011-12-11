CREATE TABLE IF NOT EXISTS Plugin (
  ID int NOT NULL AUTO_INCREMENT,

  -- Name of the plugin
  Name VARCHAR(20) NOT NULL,

  -- The total amount of hits for the plugin since time started
  GlobalHits INTEGER NOT NULL,

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS Server (
  ID int NOT NULL AUTO_INCREMENT,

  -- Foreign key to the related plugin
  Plugin INT NOT NULL,

  -- GUID
  GUID VARCHAR(40) NOT NULL,

  -- The last known version of LWC the server was using
  CurrentVersion VARCHAR(20) NOT NULL,

  -- Incremented each time the server pings us
  Hits INT NOT NULL,

  -- When the server was created on our end; epoch
  Created INTEGER NOT NULL,

  -- When the server last pinged us; epoch
  Updated INTEGER NOT NULL,

  --
  INDEX (GUID),

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
  ID int NOT NULL AUTO_INCREMENT,

  -- Name of the plugin
  Server INT NOT NULL,

  -- The version they changed to
  Version VARCHAR(20) NOT NULL,

  -- The epoch time they changed at
  Created INTEGER NOT NULL,

  --
  INDEX (Server),

  --
  INDEX (Version),

  --
  INDEX (Created),

  --
  FOREIGN KEY (Server) REFERENCES Server (ID),

  --
  PRIMARY KEY (ID)
) ENGINE = InnoDB;