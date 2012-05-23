/*
Navicat MySQL Data Transfer

Source Server         : griefcraft
Source Server Version : 50161
Source Host           : 176.31.107.170:3306
Source Database       : metrics

Target Server Type    : MYSQL
Target Server Version : 50161
File Encoding         : 65001

Date: 2012-04-17 16:51:09
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `Author`
-- ----------------------------
DROP TABLE IF EXISTS `Author`;
CREATE TABLE `Author` (
`ID`  int(11) NOT NULL ,
`Name`  varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Password`  varchar(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Created`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `AuthorACL`
-- ----------------------------
DROP TABLE IF EXISTS `AuthorACL`;
CREATE TABLE `AuthorACL` (
`Author`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
PRIMARY KEY (`Author`, `Plugin`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `Country`
-- ----------------------------
DROP TABLE IF EXISTS `Country`;
CREATE TABLE `Country` (
`ShortCode`  char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`FullName`  varchar(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
PRIMARY KEY (`ShortCode`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `CountryTimeline`
-- ----------------------------
DROP TABLE IF EXISTS `CountryTimeline`;
CREATE TABLE `CountryTimeline` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Country`  char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Servers`  int(11) NOT NULL ,
`Epoch`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `CustomColumn`
-- ----------------------------
DROP TABLE IF EXISTS `CustomColumn`;
CREATE TABLE `CustomColumn` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Graph`  int(11) NOT NULL ,
`Name`  varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci

;

-- ----------------------------
-- Table structure for `CustomData`
-- ----------------------------
DROP TABLE IF EXISTS `CustomData`;
CREATE TABLE `CustomData` (
`ID`  int(11) NOT NULL ,
`Server`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`ColumnID`  int(11) NOT NULL ,
`DataPoint`  int(11) NOT NULL ,
`Updated`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `CustomDataTimeline`
-- ----------------------------
DROP TABLE IF EXISTS `CustomDataTimeline`;
CREATE TABLE `CustomDataTimeline` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`ColumnID`  int(11) NOT NULL ,
`DataPoint`  int(11) NOT NULL ,
`Epoch`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `Graph`
-- ----------------------------
DROP TABLE IF EXISTS `Graph`;
CREATE TABLE `Graph` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Type`  int(11) NOT NULL ,
`Active`  tinyint(1) NOT NULL ,
`Name`  varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `PlayerTimeline`
-- ----------------------------
DROP TABLE IF EXISTS `PlayerTimeline`;
CREATE TABLE `PlayerTimeline` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Players`  int(11) NOT NULL ,
`Epoch`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `Plugin`
-- ----------------------------
DROP TABLE IF EXISTS `Plugin`;
CREATE TABLE `Plugin` (
`ID`  int(11) NOT NULL ,
`Name`  varchar(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Author`  varchar(75) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
`Hidden`  tinyint(1) NOT NULL ,
`GlobalHits`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `Server`
-- ----------------------------
DROP TABLE IF EXISTS `Server`;
CREATE TABLE `Server` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`GUID`  varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`ServerVersion`  varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`CurrentVersion`  varchar(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
`Hits`  int(11) NOT NULL ,
`Created`  int(11) NOT NULL ,
`Updated`  int(11) NOT NULL ,
`Players`  int(11) NULL DEFAULT NULL ,
`Country`  char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'ZZ' ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `ServerPlugin`
-- ----------------------------
DROP TABLE IF EXISTS `ServerPlugin`;
CREATE TABLE `ServerPlugin` (
`Server`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Version`  varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Updated`  int(11) NOT NULL
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `ServerTimeline`
-- ----------------------------
DROP TABLE IF EXISTS `ServerTimeline`;
CREATE TABLE `ServerTimeline` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Servers`  int(11) NOT NULL ,
`Epoch`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `VersionHistory`
-- ----------------------------
DROP TABLE IF EXISTS `VersionHistory`;
CREATE TABLE `VersionHistory` (
`ID`  int(11) NOT NULL ,
`Plugin`  int(11) NOT NULL ,
`Server`  int(11) NOT NULL ,
`Version`  varchar(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
`Created`  int(11) NOT NULL ,
PRIMARY KEY (`ID`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Table structure for `Versions`
-- ----------------------------
DROP TABLE IF EXISTS `Versions`;
CREATE TABLE `Versions` (
`Plugin`  int(11) NOT NULL ,
`Version`  varchar(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Created`  int(11) NOT NULL ,
PRIMARY KEY (`Plugin`, `Version`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci

;

-- ----------------------------
-- Indexes structure for table Author
-- ----------------------------
CREATE UNIQUE INDEX `name` USING BTREE ON `Author`(`Name`) ;

-- ----------------------------
-- Indexes structure for table AuthorACL
-- ----------------------------
CREATE INDEX `Plugin` USING BTREE ON `AuthorACL`(`Plugin`) ;

-- ----------------------------
-- Indexes structure for table CountryTimeline
-- ----------------------------
CREATE UNIQUE INDEX `Plugin_2` USING BTREE ON `CountryTimeline`(`Plugin`, `Country`, `Epoch`) ;
CREATE INDEX `Plugin` USING BTREE ON `CountryTimeline`(`Plugin`) ;
CREATE INDEX `Servers` USING BTREE ON `CountryTimeline`(`Servers`) ;
CREATE INDEX `Country` USING BTREE ON `CountryTimeline`(`Country`) ;
CREATE INDEX `Timeline-Country` USING BTREE ON `CountryTimeline`(`Plugin`, `Epoch`) ;

-- ----------------------------
-- Indexes structure for table CustomColumn
-- ----------------------------
CREATE UNIQUE INDEX `getColumnID` USING BTREE ON `CustomColumn`(`Plugin`, `Graph`, `Name`) ;
CREATE INDEX `Plugin` USING BTREE ON `CustomColumn`(`Plugin`) ;
CREATE INDEX `Name` USING BTREE ON `CustomColumn`(`Name`) ;
CREATE INDEX `Graph` USING BTREE ON `CustomColumn`(`Graph`) ;
CREATE INDEX `Active2` USING BTREE ON `CustomColumn`(`Plugin`) ;

-- ----------------------------
-- Indexes structure for table CustomData
-- ----------------------------
CREATE UNIQUE INDEX `Trikey` USING BTREE ON `CustomData`(`Server`, `Plugin`, `ColumnID`) ;
CREATE INDEX `Server` USING BTREE ON `CustomData`(`Server`) ;
CREATE INDEX `Plugin` USING BTREE ON `CustomData`(`Plugin`) ;
CREATE INDEX `ColumnID` USING BTREE ON `CustomData`(`ColumnID`) ;
CREATE INDEX `Updated` USING BTREE ON `CustomData`(`Updated`) ;
CREATE INDEX `crontrikey` USING BTREE ON `CustomData`(`Plugin`, `ColumnID`, `Updated`) ;

-- ----------------------------
-- Indexes structure for table CustomDataTimeline
-- ----------------------------
CREATE UNIQUE INDEX `Plugin_2` USING BTREE ON `CustomDataTimeline`(`Plugin`, `ColumnID`, `Epoch`) ;
CREATE INDEX `Plugin` USING BTREE ON `CustomDataTimeline`(`Plugin`) ;
CREATE INDEX `ColumnID` USING BTREE ON `CustomDataTimeline`(`ColumnID`) ;
CREATE INDEX `Ajax-Timeline` USING BTREE ON `CustomDataTimeline`(`ColumnID`, `Plugin`, `Epoch`) ;

-- ----------------------------
-- Indexes structure for table Graph
-- ----------------------------
CREATE INDEX `Plugin` USING BTREE ON `Graph`(`Plugin`) ;
CREATE INDEX `Type` USING BTREE ON `Graph`(`Type`) ;
CREATE INDEX `Name` USING BTREE ON `Graph`(`Name`) ;
CREATE INDEX `Active` USING BTREE ON `Graph`(`Active`) ;
CREATE INDEX `Active2` USING BTREE ON `Graph`(`Plugin`, `Active`) ;

-- ----------------------------
-- Indexes structure for table PlayerTimeline
-- ----------------------------
CREATE UNIQUE INDEX `Epoch` USING BTREE ON `PlayerTimeline`(`Plugin`, `Epoch`) ;
CREATE INDEX `Plugin` USING BTREE ON `PlayerTimeline`(`Plugin`) ;
CREATE INDEX `Players` USING BTREE ON `PlayerTimeline`(`Players`) ;

-- ----------------------------
-- Indexes structure for table Plugin
-- ----------------------------
CREATE INDEX `Name` USING BTREE ON `Plugin`(`Name`) ;

-- ----------------------------
-- Indexes structure for table Server
-- ----------------------------
CREATE INDEX `GUID` USING BTREE ON `Server`(`GUID`) ;
CREATE INDEX `ServerVersion` USING BTREE ON `Server`(`ServerVersion`) ;
CREATE INDEX `CurrentVersion` USING BTREE ON `Server`(`CurrentVersion`) ;
CREATE INDEX `Hits` USING BTREE ON `Server`(`Hits`) ;
CREATE INDEX `Created` USING BTREE ON `Server`(`Created`) ;
CREATE INDEX `Updated` USING BTREE ON `Server`(`Updated`) ;
CREATE INDEX `Plugin` USING BTREE ON `Server`(`Plugin`) ;
CREATE INDEX `Country` USING BTREE ON `Server`(`Country`) ;

-- ----------------------------
-- Indexes structure for table ServerPlugin
-- ----------------------------
CREATE UNIQUE INDEX `Server` USING BTREE ON `ServerPlugin`(`Server`, `Plugin`) ;
CREATE INDEX `Server_2` USING BTREE ON `ServerPlugin`(`Server`) ;
CREATE INDEX `Plugin` USING BTREE ON `ServerPlugin`(`Plugin`) ;
CREATE INDEX `Version` USING BTREE ON `ServerPlugin`(`Version`) ;
CREATE INDEX `Updated` USING BTREE ON `ServerPlugin`(`Updated`) ;
CREATE INDEX `Count` USING BTREE ON `ServerPlugin`(`Plugin`, `Version`, `Updated`) ;
CREATE INDEX `Count2` USING BTREE ON `ServerPlugin`(`Plugin`, `Updated`) ;

-- ----------------------------
-- Indexes structure for table ServerTimeline
-- ----------------------------
CREATE UNIQUE INDEX `Epoch` USING BTREE ON `ServerTimeline`(`Plugin`, `Epoch`) ;
CREATE INDEX `Plugin` USING BTREE ON `ServerTimeline`(`Plugin`) ;
CREATE INDEX `Servers` USING BTREE ON `ServerTimeline`(`Servers`) ;

-- ----------------------------
-- Indexes structure for table VersionHistory
-- ----------------------------
CREATE INDEX `Plugin` USING BTREE ON `VersionHistory`(`Plugin`) ;
CREATE INDEX `Server` USING BTREE ON `VersionHistory`(`Server`) ;
CREATE INDEX `Version` USING BTREE ON `VersionHistory`(`Version`) ;
CREATE INDEX `Created` USING BTREE ON `VersionHistory`(`Created`) ;

-- ----------------------------
-- Indexes structure for table Versions
-- ----------------------------
CREATE INDEX `Plugin` USING BTREE ON `Versions`(`Plugin`) ;
CREATE INDEX `Created` USING BTREE ON `Versions`(`Created`) ;
