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

-- clean up
DROP DATABASE IF EXISTS `EDMA`;
--
-- Datenbank: `EDMA`
--
CREATE DATABASE `EDMA` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `EDMA`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anhang`
--

CREATE TABLE `anhang` (
`id` bigint(20) unsigned NOT NULL,
  `projekt_id` bigint(20) unsigned NOT NULL,
  `dateiname` varchar(100) NOT NULL,
  `inhalt` mediumblob NOT NULL,
  `groesse` int(10) unsigned NOT NULL,
  `dateityp` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `anhang`
ADD CONSTRAINT pk_anhang PRIMARY KEY (id),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `datentyp`
--

CREATE TABLE `datentyp` (
`id` bigint(20) unsigned NOT NULL,
  `typ` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `datentyp`
ADD CONSTRAINT pk_datentyp PRIMARY KEY (id),
ADD CONSTRAINT unique_typ UNIQUE (typ),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messreihe`
--

CREATE TABLE `messreihe` (
`id` bigint(20) unsigned NOT NULL,
  `projekt_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messreihe`
ADD CONSTRAINT pk_messreihe PRIMARY KEY (id),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messreihe_metainfo`
--

CREATE TABLE `messreihe_metainfo` (
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

CREATE TABLE `messreihe_sensor` (
  `messreihe_id` bigint(20) unsigned NOT NULL,
  `sensor_id` bigint(20) unsigned NOT NULL,
  `anzeigename` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `messreihe_sensor`
ADD CONSTRAINT pk_messreihe_sensor PRIMARY KEY (messreihe_id, sensor_id);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messung`
--

CREATE TABLE `messung` (
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

CREATE TABLE `metainfo` (
`id` bigint(20) unsigned NOT NULL,
  `metaname` varchar(30) NOT NULL,
  `datentyp_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `metainfo`
ADD CONSTRAINT pk_metaindo PRIMARY KEY (id),
ADD CONSTRAINT unique_metaname UNIQUE (metaname),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `passwort`
--

CREATE TABLE `passwort` (
`id` bigint(20) unsigned NOT NULL,
  `hash` varchar(64) NOT NULL,
  `projekt_id` bigint(20) unsigned DEFAULT NULL,
  `salt` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `passwort`
ADD CONSTRAINT pk_passwort PRIMARY KEY (id),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `projekt`
--

CREATE TABLE `projekt` (
`id` bigint(20) unsigned NOT NULL,
  `projektname` varchar(100) NOT NULL,
  `passwort_id` bigint(20) unsigned NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `projekt`
ADD CONSTRAINT pk_projekt PRIMARY KEY (id),
ADD CONSTRAINT unique_projektname UNIQUE (projektname),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

CREATE INDEX index_projektname ON `projekt` (projektname);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensor`
--

CREATE TABLE `sensor` (
`id` bigint(20) unsigned NOT NULL,
  `sensorname` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `sensor`
ADD CONSTRAINT pk_sensor PRIMARY KEY (id),
ADD CONSTRAINT unique_sensorname UNIQUE (sensorname),
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;


-- ----------------------------------------
-- ----------------------------------------

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

