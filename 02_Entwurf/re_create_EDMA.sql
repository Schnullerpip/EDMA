-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 06. Dez 2014 um 16:42
-- Server Version: 5.6.21
-- PHP-Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";


--
-- Datenbank: `EDMA`
--
CREATE DATABASE IF NOT EXISTS `EDMA` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `EDMA`;


-- clean up

ALTER TABLE `anhang`
DROP FOREIGN KEY fk_anhang_projekt;

--
-- Foreign keys der Tabelle `datentyp`
--

--
-- FOREIGN KEYs der Tabelle `messreihe`
--

ALTER TABLE `messreihe`
DROP FOREIGN KEY `fk_messreihe_projekt`;

--
-- Foreign keys der Tabelle `messreihe_metainfo`
--

ALTER TABLE `messreihe_metainfo`
DROP FOREIGN KEY fk_messreihe_metainfo_messreihe,
DROP FOREIGN KEY fk_messreihe_metainfo_metainfo;

--
-- Foreign keys der Tabelle `messreihe_sensor`
--

ALTER TABLE `messreihe_sensor`
DROP FOREIGN KEY fk_messreihe_sensor_messreihe,
DROP FOREIGN KEY fk_messreihe_sensor_sensor;

--
-- Foreign keys der Tabelle `messung`
--

ALTER TABLE `messung`
DROP FOREIGN KEY fk_messung_messreihe,
DROP FOREIGN KEY fk_messung_sensor;

--
-- Foreign keys der Tabelle `metainfo`
--

ALTER TABLE `metainfo`
DROP FOREIGN KEY fk_metainfo_datentyp;

--
-- Foreign keys der Tabelle `passwort`
--

ALTER TABLE `passwort`
DROP FOREIGN KEY fk_passwort_projekt;

--
-- Foreign keys der Tabelle `projekt`
--

ALTER TABLE `projekt`
DROP FOREIGN KEY fk_projekt_passwort;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anhang`
--

DROP TABLE IF EXISTS `anhang`;
CREATE TABLE IF NOT EXISTS `anhang` (
`id` bigint(20) unsigned NOT NULL,
  `projekt_id` bigint(20) unsigned NOT NULL,
  `dateiname` varchar(256) NOT NULL,
  `inhalt` mediumblob NOT NULL,
  `groesse` int(10) unsigned NOT NULL,
  `dateityp` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `anhang`
ADD CONSTRAINT pk_anhang PRIMARY KEY (id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `datentyp`
--

DROP TABLE IF EXISTS `datentyp`;
CREATE TABLE IF NOT EXISTS `datentyp` (
`id` bigint(20) unsigned NOT NULL,
  `typ` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `datentyp`
ADD CONSTRAINT pk_datentyp PRIMARY KEY (id),
ADD CONSTRAINT unique_typ UNIQUE (typ);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messreihe`
--

DROP TABLE IF EXISTS `messreihe`;
CREATE TABLE IF NOT EXISTS `messreihe` (
`id` bigint(20) unsigned NOT NULL,
  `projekt_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messreihe`
ADD CONSTRAINT pk_messreihe PRIMARY KEY (id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messreihe_metainfo`
--

DROP TABLE IF EXISTS `messreihe_metainfo`;
CREATE TABLE IF NOT EXISTS `messreihe_metainfo` (
  `messreihe_id` bigint(20) unsigned NOT NULL,
  `metainfo_id` bigint(20) unsigned NOT NULL,
  `metawert` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messreihe_metainfo`
ADD CONSTRAINT pk_messreihe_metainfo PRIMARY KEY (messreihe_id, metainfo_id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messreihe_sensor`
--

DROP TABLE IF EXISTS `messreihe_sensor`;
CREATE TABLE IF NOT EXISTS `messreihe_sensor` (
  `messreihe_id` bigint(20) unsigned NOT NULL,
  `sensor_id` bigint(20) unsigned NOT NULL,
  `anzeigename` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messreihe_sensor`
ADD CONSTRAINT pk_messreihe_sensor PRIMARY KEY (messreihe_id, sensor_id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messung`
--

DROP TABLE IF EXISTS `messung`;
CREATE TABLE IF NOT EXISTS `messung` (
  `messreihe_id` bigint(20) unsigned NOT NULL,
  `sensor_id` bigint(20) unsigned NOT NULL,
  `zeitpunkt` int(10) unsigned NOT NULL,
  `messwert` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messung`
ADD CONSTRAINT pk_messung PRIMARY KEY (messreihe_id, sensor_id, zeitpunkt);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `metainfo`
--

DROP TABLE IF EXISTS `metainfo`;
CREATE TABLE IF NOT EXISTS `metainfo` (
`id` bigint(20) unsigned NOT NULL,
  `metaname` varchar(30) NOT NULL,
  `datentyp_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `metainfo`
ADD CONSTRAINT pk_metaindo PRIMARY KEY (id),
ADD CONSTRAINT unique_metaname UNIQUE (metaname);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `passwort`
--

DROP TABLE IF EXISTS `passwort`;
CREATE TABLE IF NOT EXISTS `passwort` (
`id` bigint(20) unsigned NOT NULL,
  `hash` varbinary(256) NOT NULL,
  `projekt_id` bigint(20) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `passwort`
ADD CONSTRAINT pk_passwort PRIMARY KEY (id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `projekt`
--

DROP TABLE IF EXISTS `projekt`;
CREATE TABLE IF NOT EXISTS `projekt` (
`id` bigint(20) unsigned NOT NULL,
  `projektname` varchar(128) NOT NULL,
  `salt` varchar(8) NOT NULL,
  `passwort_id` bigint(20) unsigned NOT NULL DEFAULT '1' COMMENT 'masterpasswort'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `projekt`
ADD CONSTRAINT pk_projekt PRIMARY KEY (id),
ADD CONSTRAINT unique_projektname UNIQUE (projektname);

CREATE INDEX index_projektname ON `projekt` (projektname);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor`
--

DROP TABLE IF EXISTS `sensor`;
CREATE TABLE IF NOT EXISTS `sensor` (
`id` bigint(20) unsigned NOT NULL,
  `sensorname` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `sensor`
ADD CONSTRAINT pk_sensor PRIMARY KEY (id),
ADD CONSTRAINT unique_sensorname UNIQUE (sensorname);


-- ----------------------------------------
-- ----------------------------------------

--
-- AUTO_INCREMENT für Tabelle `Messreihen`
--
ALTER TABLE `anhang`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `datentyp`
--

ALTER TABLE `datentyp`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `messreihe`
--
ALTER TABLE `messreihe`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `metainfo`
--
ALTER TABLE `metainfo`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `passwort`
--
ALTER TABLE `passwort`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `projekt`
--
ALTER TABLE `projekt`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensor`
--
ALTER TABLE `sensor`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- --------------------------------------------------------

-- 
-- Foreign keys der exportierten Tabellen
-- 

--
-- Foreign keys der Tabelle `anhang`
--

ALTER TABLE `anhang`
ADD CONSTRAINT fk_anhang_projekt FOREIGN KEY (projekt_id) REFERENCES projekt(id);

--
-- Foreign keys der Tabelle `datentyp`
--

--
-- Foreign keys der Tabelle `messreihe`
--

ALTER TABLE `messreihe`
ADD CONSTRAINT fk_messreihe_projekt FOREIGN KEY (projekt_id) REFERENCES projekt(id);

--
-- Foreign keys der Tabelle `messreihe_metainfo`
--

ALTER TABLE `messreihe_metainfo`
ADD CONSTRAINT fk_messreihe_metainfo_messreihe FOREIGN KEY (messreihe_id) REFERENCES messreihe(id),
ADD CONSTRAINT fk_messreihe_metainfo_metainfo FOREIGN KEY (metainfo_id) REFERENCES metainfo(id);

--
-- Foreign keys der Tabelle `messreihe_sensor`
--

ALTER TABLE `messreihe_sensor`
ADD CONSTRAINT fk_messreihe_sensor_messreihe FOREIGN KEY (messreihe_id) REFERENCES messreihe(id),
ADD CONSTRAINT fk_messreihe_sensor_sensor FOREIGN KEY (sensor_id) REFERENCES sensor(id);

--
-- Foreign keys der Tabelle `messung`
--

ALTER TABLE `messung`
ADD CONSTRAINT fk_messung_messreihe FOREIGN KEY (messreihe_id) REFERENCES messreihe(id),
ADD CONSTRAINT fk_messung_sensor FOREIGN KEY (sensor_id) REFERENCES sensor(id);

--
-- Foreign keys der Tabelle `metainfo`
--

ALTER TABLE `metainfo`
ADD CONSTRAINT fk_metainfo_datentyp FOREIGN KEY (datentyp_id) REFERENCES datentyp(id);

--
-- Foreign keys der Tabelle `passwort`
--

ALTER TABLE `passwort`
ADD CONSTRAINT fk_passwort_projekt FOREIGN KEY (projekt_id) REFERENCES projekt(id);

--
-- Foreign keys der Tabelle `projekt`
--

ALTER TABLE `projekt`
ADD CONSTRAINT fk_projekt_passwort FOREIGN KEY (passwort_id) REFERENCES passwort(id);

--
-- Foreign keys der Tabelle `sensor`
--

