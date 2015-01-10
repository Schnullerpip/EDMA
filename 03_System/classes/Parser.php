<?php
/**
 * Description of Parser
 *
 * Parsed die Datei mit RegularExpression.
 * Ergebniss ist ein Array mit Groesse 7:
 * [0]: Array mit alle gefundenen Zeilen (komplett)
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
    private $db;
    private $_projektID;
    private $_messreiheID;
    
    public function __construct($file, $projekt_id) {
        $this->_file = $file;
        $this->db = DB::getInstance();
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
        $metadaten = $stringFile[0];
        $messdaten = $stringFile[1];
        
        $this->db->beginTransaction();
        $this->parseMetaDaten($metadaten);
        $this->parseMessDaten($messdaten);
        $this->db->commit();
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
        // -1 wegen Leerzeile am Schluss.
        $lines = count($metalines) - 1;
        if ($count != $lines) {
            // TODO: errorhandling, kann abweichen wegen Leerzeile in metainfo, evtl. $count -1
        }

        $messreihenname = array_search("Name", $matches[1]);
        if ($messreihenname === FALSE) {
            $this->errors("Metainfo 'Name' nicht gefunden!");
            die();
        }
        
        $datum_index = array_search("Datum", $matches[1]);
        if ($datum_index === FALSE) {
            $this->errors("Metainfo 'Datum' nicht gefunden!");
            die();
        }        

        $datum_german = $matches[3][$datum_index];
        $datum_mysql = Utils::convertDate($datum_german);

        // insert messreihe
        $messreihe = array (
            "messreihenname" => $matches[3][$messreihenname],
            "datum" => $datum_mysql,
            "projekt_id" => $this->_projektID,
        );
        $messreihe_id = $this->db->getIdBySelectOrInsert('messreihe', $messreihe);
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
        $datentyp_id_s = $this->db->getIdBySelectOrInsert('datentyp', $datentyp_s);
        $datentyp_d = array(
            "typ" => "datum",
        );
        $datentyp_id_d = $this->db->getIdBySelectOrInsert('datentyp', $datentyp_d);
        $datentyp_n = array(
            "typ" => "numerisch",
        );
        $datentyp_id_n = $this->db->getIdBySelectOrInsert('datentyp', $datentyp_n);

        // insert metainfo und messreihe_metainfo
        $preapredInsert = $this->db->createStatement("INSERT INTO messreihe_metainfo (messreihe_id, metainfo_id, metawert) VALUES (?, ?, ?)");
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
            $metainfo_id = $this->db->getIdBySelectOrInsert('metainfo', $metainfo);
            
            $messreihe_metainfo = array(
                $messreihe_id,
                $metainfo_id,
                $metawert,
            );
            
            $this->db->executeStatement($preapredInsert, $messreihe_metainfo);
            if ($this->db->error()) {
                $this->errors("Fehler beim Import von Metainfo " . $metainfo_id);
                die();
            }
        }
    }
    
    private function parseMessDaten($messdaten) {
        $saveExTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 1000);
        $messdaten = preg_split("/\n/", $messdaten);
        $messdaten = array_slice($messdaten, 1, -1);	// array_slice() löscht erstes und letztes Element, da leer

        $spaltennamen = preg_split("/:[\t]?/", $messdaten[0]);
        $spaltenanzahl = count($spaltennamen) - 1;   // letztes Element leer aufgrund der preg_split-Bedingung
        
        // insert sensor und messreihe_sensor
        $sensor_id_array = array();
        $statement_messreihe_sensor = $this->db->createStatement("INSERT INTO messreihe_sensor (messreihe_id, sensor_id, anzeigename) VALUES (?, ?, ?)");
        // ab Spalte 2, ohne Datum und Uhrzeit
        for ($i = 2; $i < $spaltenanzahl; $i++) {
            $sensorname = $spaltennamen[$i];
            $sensor = array(
                "sensorname" => $sensorname,
            );
            $sensor_id = $this->db->getIdBySelectOrInsert('sensor', $sensor);
            array_push($sensor_id_array, $sensor_id);

            $messreihe_sensor = array(
                $this->_messreihe_id,
                $sensor_id,
                $sensorname,
            );
            $this->db->executeStatement($statement_messreihe_sensor, $messreihe_sensor);
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
        
        $statement_messung = $this->db->createStatement($sql);
        
        // Iteration ueber alle Zeilen
        for ($j = 0; $j < count($messdaten); ++$j) {
            $messungsSpalte = preg_split("/\t/", $messdaten[$j]);
            
            // Pruefung auf zu wenig Zeilen
            if (count($messungsSpalte) !== $spaltenanzahl) {
                // Pruefung auf Leerzeile in letzter Zeile
                if ($j === count($messdaten) - 1 and count($messungsSpalte) === 0) {
                    continue;
                } else {
                    $this->errors("Falsche Anzahl Spalten in Zeile " . $j . ", " . count($messungsSpalte) . " statt " . $spaltenanzahl);
                    die();        
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
            
            $this->db->executeStatement($statement_messung, $values);
            
            if ($this->db->error()) {
                $this->errors("Fehler bei INSERT von messung");
                die();
            }
        }
        ini_set('max_execution_time', $saveExTime);
    }

    public function errors($msg) {
        $this->db->rollback();
        Session::flash("error", $msg);
        Redirect::to("messreihen.php?id=neu");
        return true;
    }
}
