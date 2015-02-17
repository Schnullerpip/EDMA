<?php
/**
 * Description of Parser
 *
 * Parset die Datei mit RegularExpression.
 * Ergebnis ist ein Array mit Groesse 7:
 * [0]: Array mit allen gefundenen Zeilen (komplett)
 * [1]: Array mit allen Metanamen (ohne _TYP)
 * [2]: Array mit dem Typ (n, s oder d)
 * [3]: Array mit allen Metawerten (gemischt)
 * [4]: Array mit allen Metawerten von Typ String
 * [5]: Array mit allen Metawerten von Typ Datum
 * [6]: Array mit allen Metawerten von Typ Nummer
 * @author phwachte
 */
class Parser {
    
    private $_file;
    private $_db;
    private $_projektID;
    private $_messreiheID;
    private $_error = array();
    private $_warning = array();
    private $_zeilennummerMessdaten;
    
    public function __construct($file, $projekt_id) {
        $this->_file = $file;
        $this->_db = DB::getInstance();
        $this->_projektID = $projekt_id;
        $this->parse();
    }
    
    private function parse() {
        $stringFile = file_get_contents($this->_file);
        
        $charset = mb_detect_encoding($stringFile, "UTF-8, ISO-8859-1");
        // falls Datei in ISO Format ist muss konvertiert werden.
        // falls weitere charsets vorkommen muss iconv() statt utf8_encode benutzt werden
        if ($charset === "ISO-8859-1") {
            $stringFile = utf8_encode($stringFile);
        }
        $stringFile = str_replace("\r", "", $stringFile);
        $stringFile = explode("###", $stringFile);
        if (count($stringFile) == 1) {
            $this->_error = array(
                'Fehler' => "Trennzeichen'###' nicht vorhanden!",
                'ABBRUCH' => "Import abgebrochen!"
            );
            return;
        }
        $metadaten = $stringFile[0];        
        $messdaten = $stringFile[1];
        
        $this->_db->beginTransaction();
        try {
            $this->parseMetaDaten($metadaten);
            $this->parseMessDaten($messdaten);
        } catch (ParserException $e) {
            $exceptionArray = array(
                'Parser.php' => $e->getMessage(),
            );
            $this->_error = array_merge($this->_error, $exceptionArray);
            $this->_error = array_merge($this->_error, $e->getMessages());
            $abbruch = array(
                'ABBRUCH' => "Import abgebrochen!"
            );
            $this->_error = array_merge($this->_error, $abbruch);
            $this->_db->rollback();
            return;
        }
        $this->_db->commit();
    }
    
    private function parseMetaDaten($metadaten) {        
        // Metanamen besteht aus beliebiger Anzahl von 
        // Buchstaben, Zahlen, Leerzeichen, Prozentzeichen, 
        // Gradzeichen, Slash / und Unterstrich _
        $metaNamePattern = "([\p{L}\p{N} \x{00B0}\x{0025}/\-]+)_([dns])";
        
        // String Pattern nach s:\t beliebige Anzahl von
        // Buchstaben /p{L}, Zahlen /p{N}, Punkt, Leerzeichen, Komma, Slash / 
        // und Unterstrich _
        $stringPattern = "((?<=s:\t)[\p{L}\p{N}\. ,_/\-]+)";
        
        // Datum Pattern nach d:\t DD.MM.YYYY
        $datePattern = "((?<=d:\t)\p{N}{2}\.\p{N}{2}\.\p{N}{4})";
        
        // Nummer Pattern nach n:\t 
        // Optional -, beliebig viele Zahlen \p{N}, Optional Punkt oder Komma
        // Danach Optional beliebig viele Zahlen
        $numberPattern = "((?<=n:\t)-?\p{N}+[\.,]?\p{N}*)";
        
        $pattern = "#^" . $metaNamePattern . ":\t(" . $stringPattern . "|" . $datePattern . "|" . $numberPattern . ")\t*$#mu";
        $matches = array();
        $count = preg_match_all($pattern, $metadaten, $matches);
        $metalines = explode("\n", $metadaten);
        array_pop($metalines);  // letztes Element löschen, da leer
        $lines = count($metalines);
        $this->_zeilennummerMessdaten = $lines + 3; // Metazeilen + Trennzeichenzeile + Sensornamenzeile + Indexbeginn
        if ($count != $lines) {
            $this->_warning = $this->metalinesToIgnore($matches[0], $metalines);
        }

        $messreihenname = array_search("Name", $matches[1]);
        if ($messreihenname === FALSE) {
            $nameError = "Metainfo 'Name' nicht gefunden oder Wert ungültig!";
            $this->throwMetaException($nameError);
        }
        
        $datum_index = array_search("Datum", $matches[1]);
        if ($datum_index === FALSE) {
            $datumError = "Metainfo 'Datum' nicht gefunden oder Wert ungültig!";
            $this->throwMetaException($datumError);
        }        

        $datum_german = $matches[3][$datum_index];
        $datum_mysql = Utils::convertDate($datum_german);

        // insert messreihe
        $messreihen_name = $matches[3][$messreihenname];
        $messreihe = array (
            "messreihenname" => $messreihen_name,
            "datum" => $datum_mysql,
            "projekt_id" => $this->_projektID,
        );
        $this->_db->insert('messreihe', $messreihe);
        if ($this->_db->error()) {
            $this->throwMetaException("Fehler beim INSERT von 'messreihe' ("
                    . $messreihen_name . "), wahrscheinlich ist der Messreihenname bereits vorhanden");
        }
        $messreihe_id = $this->_db->getIdBySelectOrInsert('messreihe', $messreihe);
        $this->_messreiheID = $messreihe_id;
        
        // alle Indizes außer Indizes von "Name" und "Datum"
        $meta_indizes = array();
        foreach ($matches[1] as $meta_index => $metarow) {
            if ($metarow != "Name" && $metarow != "Datum") {
                array_push($meta_indizes, $meta_index);
            }
        }

        // datentyp_id bestimmen
        $datentyp_s = array(
            "typ" => "string",
        );
        $datentyp_id_s = $this->_db->getIdBySelectOrInsert('datentyp', $datentyp_s);
        if ($this->_db->error()) {
            $this->throwMetaException("Fehler beim INSERT von 'datentyp'");
        }
        $datentyp_d = array(
            "typ" => "datum",
        );
        $datentyp_id_d = $this->_db->getIdBySelectOrInsert('datentyp', $datentyp_d);
        if ($this->_db->error()) {
            $this->throwMetaException("Fehler beim INSERT von 'datentyp'");
        }
        $datentyp_n = array(
            "typ" => "numerisch",
        );
        $datentyp_id_n = $this->_db->getIdBySelectOrInsert('datentyp', $datentyp_n);
        if ($this->_db->error()) {
            $this->throwMetaException("Fehler beim INSERT von 'datentyp'");
        }

        // insert metainfo und messreihe_metainfo
        $preparedInsert = $this->_db->createStatement("INSERT INTO messreihe_metainfo (messreihe_id, metainfo_id, metawert) VALUES (?, ?, ?)");
        if ($preparedInsert === FALSE) {
            $this->throwMetaException("Fehler beim Erstellen des prepared statements von 'messreihe_metainfo'");
        }
        foreach ($meta_indizes as $index) {
            if ($matches[4][$index] != null) {
                $metainfo = array(
                    "metaname" => $matches[1][$index],
                    "datentyp_id" => $datentyp_id_s,
                );
                $metawert = $matches[4][$index];
            } elseif ($matches[5][$index] != null) {
                $metainfo = array(
                    "metaname" => $matches[1][$index],
                    "datentyp_id" => $datentyp_id_d,
                );
                $metawert = $matches[5][$index];
            } else {
                $metainfo = array(
                    "metaname" => $matches[1][$index],
                    "datentyp_id" => $datentyp_id_n,
                );
                $metawert = $matches[6][$index];
            }
            // TODO: was, wenn metainfo eines anderen Projekts gleichen Namen, aber anderen Datentyp hat?
            $metainfo_id = $this->_db->getIdBySelectOrInsert('metainfo', $metainfo);
            if ($this->_db->error()) {
                $metainfo_name = $metainfo["metaname"];
                $this->throwMetaException("Fehler beim INSERT von 'metainfo' (" . $metainfo_name . ")");
            }
            
            $messreihe_metainfo = array(
                $messreihe_id,
                $metainfo_id,
                $metawert,
            );
            
            $this->_db->executeStatement($preparedInsert, $messreihe_metainfo);
            if ($this->_db->error()) {
                $this->throwMetaException("Fehler beim INSERT von 'messreihe_metainfo' ("
                        . "messreihe_id: " . $messreihe_id
                        . ", metainfo_id: " . $metainfo_id
                        . ", metawert: " . $metawert . ")");
            }
        }
    }
    
    private function parseMessDaten($messdaten) {
        $saveExTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 1000);
        $messdaten = preg_split("/\n/", $messdaten);
        $messdaten = array_slice($messdaten, 1);    // array_slice() löscht erstes Element, da leer

        $spaltennamen = preg_split("/:[\t]?/", $messdaten[0]);
        $spaltenanzahl = count($spaltennamen) - 1;   // letztes Element leer aufgrund der preg_split-Bedingung
        
        // insert sensor und messreihe_sensor
        $sensor_id_array = array();
        $statement_messreihe_sensor = $this->_db->createStatement("INSERT INTO messreihe_sensor (messreihe_id, sensor_id, anzeigename) VALUES (?, ?, ?)");
        if ($statement_messreihe_sensor === FALSE) {
            $this->throwMessException("Fehler beim Erstellen des prepared statements von 'messreihe_sensor'");
        }
        // ab Spalte 2, ohne Datum und Uhrzeit
        for ($i = 2; $i < $spaltenanzahl; $i++) {
            $sensorname = $spaltennamen[$i];
            $sensor = array(
                "sensorname" => $sensorname,
            );
            $sensor_id = $this->_db->getIdBySelectOrInsert('sensor', $sensor);
            if ($this->_db->error()) {
                $this->throwMessException("Fehler beim INSERT von 'sensor' (" . $sensorname . ")");
            }
            array_push($sensor_id_array, $sensor_id);

            $messreihe_sensor = array(
                $this->_messreiheID,
                $sensor_id,
                $sensorname,
            );
            $this->_db->executeStatement($statement_messreihe_sensor, $messreihe_sensor);
            if ($this->_db->error()) {
                $this->throwMessException("Fehler beim INSERT von 'messreihe_sensor' ("
                        . "messreihe_id: " . $this->_messreiheID
                        . ", sensor_id: " . $sensor_id
                        . ", sensorname: " . $sensorname . ")");
            }
        }
        $messdaten = array_slice($messdaten, 1);	// löschen des Elements mit Sensornamen, da nicht mehr benötigt
        
        // Sql Statement fuer eine Zeile erstellen
        $sql = "INSERT INTO messung (messreihe_id, zeitpunkt, datum_uhrzeit, sensor_id, messwert) VALUES ";
        for ($i = 2; $i < $spaltenanzahl; ++$i) {
            if ($i != 2) {
                $sql = $sql . ",";
            }
            $sql = $sql . "(?, ?, STR_TO_DATE(? ?, '%d.%m.%Y %H:%i:%s'), ?, ?)";
        }
        
        $statement_messung = $this->_db->createStatement($sql);
        if ($statement_messreihe_sensor === FALSE) {
            $this->throwMessException("Fehler beim Erstellen des prepared statements von 'messung'");
        }
        
        // Iteration ueber alle Zeilen
        for ($j = 0; $j < count($messdaten); ++$j) {
            $messungsSpalte = preg_split("/\t/", $messdaten[$j]);
            
            // Pruefung auf zu wenig Spalten
            if (count($messungsSpalte) !== $spaltenanzahl) {
                // Pruefung auf Leerzeile in letzter Zeile
                if ($j === count($messdaten) - 1 and count($messungsSpalte) === 1) {
                    continue;
                } else {
                    $executeError = array(
                        'Error' => "Falsche Anzahl an Spalten in Zeile " . ($j + $this->_zeilennummerMessdaten)
                        . " (" . count($messungsSpalte) . " statt " . $spaltenanzahl . ")"
                    );
                    throw new ParserException("Fehler in Funktion parseMetaDaten()", 0, null, $executeError);
                }
            }
            $datum = $messungsSpalte[0];
            $uhrzeit = $messungsSpalte[1];
                        
            $defaults = array($this->_messreiheID, $j, $datum, $uhrzeit);
            $values = array();
            
            for ($i = 2; $i < count($messungsSpalte); ++$i) {
                $messwert = $messungsSpalte[$i];
                // Ersetze NaN mit 0
                if ($messwert === "NaN") {
                    $messwert = "0";
                }
                // Ersetze Komma durch Punkt
                $messwert = str_replace(",", ".",$messwert);
                
                $values = array_merge($values, $defaults);
                array_push($values, $sensor_id_array[$i - 2]);
                array_push($values, $messwert);
            }
            
            $this->_db->executeStatement($statement_messung, $values);
            if ($this->_db->error()) {
                $this->throwMessException("Fehler beim INSERT von 'messung'");
            }
        }
        ini_set('max_execution_time', $saveExTime);
    }
    
    private function metalinesToIgnore($parsedLines, $allLines) {
        $retArray = array();
        $retArray["Folgende Header-Zeile(n) wurde(n) ignoriert"] = "";
        for ($i = 0; $i < count($allLines); ++$i) {
            if (!in_array($allLines[$i], $parsedLines)) {
                $retArray["Zeile " . ($i + 1)] = $allLines[$i];
            }
        }
        return $retArray;
    }
    
    private function throwMetaException($msg) {
        $dbErrorMsg = array(
            'DB Error' => $msg
        );
        throw new ParserException("Fehler in Funktion parseMetaDaten()", 0, null, $dbErrorMsg);
    }
    
    private function throwMessException($msg) {
        $dbErrorMsg = array(
            'DB Error' => $msg
        );
        throw new ParserException("Fehler in Funktion parseMessDaten()", 0, null, $dbErrorMsg);
    }

    public function errors() {
        return $this->_error;
    }
    
    public function warnings() {
        return $this->_warning;
    }
}
