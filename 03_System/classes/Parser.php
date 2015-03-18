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
    private $_logger;
    private $_metadata;

    public function __construct($file, $projekt_id) {
        // Logger init
        $this->_logger = new Logger();
        $this->_logger->activate(false);
        $this->_logger->lfile(realpath("logs/parser.txt"));
        $this->_logger->lwrite("-------------------- NEW --------------------");
        $this->_logger->lwrite("Init:");
        $this->_logger->lwrite("Memory Usage: " . Utils::convert(memory_get_usage(true)));

        $this->_file = new SplFileObject($file);
        $this->_db = DB::getInstance();
        $this->_projektID = $projekt_id;
        $this->parse();

        // close log file
        $this->_logger->lclose();
    }

    private function parse() {
        $this->_metadata = "";
        while (!$this->_file->eof()) {
            $string = $this->_file->fgets();
            if ($string === FALSE) {
                $this->_error = array(
                    'Fehler' => "Aus Datei kann nicht eingelesen werden!",
                    'ABBRUCH' => "Import abgebrochen!"
                );
                return;
            }
            // Pruefung auf Trennzeichen
            if (strpos($string, "###") === 0) {
                break;
            }
            $this->_metadata .= $string;
        }
        // Filepointer darf zu diesem Zeitpunkt nicht auf das Ende zeigen
        if ($this->_file->eof()) {
            $this->_error = array(
                'Fehler' => "Trennzeichen '###' nicht oder ganz am Ende der Datei vorhanden!",
                'ABBRUCH' => "Import abgebrochen!"
            );
            return;
        }

        $this->_metadata = $this->encodeString($this->_metadata);

        $this->_db->beginTransaction();
        try {
            $this->parseMetaDaten();
            $this->parseMessDaten();
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

    private function parseMetaDaten() {
        // Metanamen besteht aus beliebiger Anzahl von Buchstaben, Ziffern, 
        // Leerzeichen, Prozentzeichen, Gradzeichen, Slash, Minuszeichen und am
        // Ende Unterstrich mit Datentyp (_d, _n oder _s)
        $metaNamePattern = "([\p{L}\p{N} \x{00B0}\x{0025}/\-]+)_([dns])";

        // String Pattern 
        // nach s:\t beliebige Anzahl von Buchstaben \p{L}, Ziffern \p{N}, Punkt,
        // Leerzeichen, Komma, Unterstrich, Slash, Minuszeichen
        $stringPattern = "((?<=s:\t)[\p{L}\p{N}\. ,_/\-]+)";

        // Datum Pattern
        // nach d:\t DD.MM.YYYY
        $datePattern = "((?<=d:\t)\p{N}{2}\.\p{N}{2}\.\p{N}{4})";

        // Nummer Pattern 
        // nach n:\t optionales Minuszeichen gefolgt von beliebig vielen Ziffern 
        // \p{N}, zwischen den Ziffern optional Punkt oder Komma
        $numberPattern = "((?<=n:\t)-?\p{N}+[\.,]?\p{N}*)";

        $pattern = "#^" . $metaNamePattern . ":\t(" . $stringPattern . "|" . $datePattern . "|" . $numberPattern . ")\t*$#mu";
        $matches = array();
        $count = preg_match_all($pattern, $this->_metadata, $matches);
        $metalines = explode("\n", $this->_metadata);
        array_pop($metalines);  // letztes Element löschen, da leer
        $lines = count($metalines);
        $this->_zeilennummerMessdaten = $lines + 3; // Metazeilen + (Trennzeichenzeile + Sensornamenzeile + Indexbeginn = 3)
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
            $datumError = "Metainfo 'Datum' nicht gefunden!";
            $this->throwMetaException($datumError);
        }
        $datumWert = $matches[3][$datum_index];
        $tag = intval(substr($datumWert, 0, 2));    // index: 0 1 2 3 4 5 6 7 8 9
        $monat = intval(substr($datumWert, 3, 2));  //        | | | | | | | | | |
        $jahr = intval(substr($datumWert, 6, 4));   // datum: d d . m m . y y y y
        if ($jahr < 1970) {
            $datumError = "Metainfo 'Datum' hat ungültige Jahresangabe kleiner 1970 (" . $datumWert . ")!";
            $this->throwMetaException($datumError);
        }
        if (!checkdate($monat, $tag, $jahr)) {
            $datumError = "Metainfo 'Datum' hat ungültigen Wert (" . $datumWert . ")!";
            $this->throwMetaException($datumError);
        }
        $datum_mysql = Utils::convertDate($datumWert);

        // insert messreihe
        $messreihen_name = $matches[3][$messreihenname];
        $messreihe = array(
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

    private function parseMessDaten() {
        $spaltenString = $this->encodeString($this->_file->fgets());
        $spaltennamen = preg_split("/:[\t]?/", $spaltenString);
        $spaltenanzahl = count($spaltennamen) - 1;   // letztes Element leer aufgrund der preg_split-Bedingung ':'
        if ($spaltenanzahl > 1) {
            if ($spaltennamen[0] != "Datum") {     // 1. Spalte der Messungen muss Datum sein
                $this->throwMessException("Die 1. Spalte der Messungen lautet nicht 'Datum' sondern: " . $spaltennamen[0]);
            }
            if ($spaltennamen[1] != "Uhrzeit") {       // 2. Spalte der Messungen muss Uhrzeit sein
                $this->throwMessException("Die 2. Spalte der Messungen lautet nicht 'Uhrzeit' sondern: " . $spaltennamen[1]);
            }
            if ($spaltenanzahl == 2) {   // wenn gleich 2, dann Fehler, da mindestens ein Sensor vorhanden sein muss (plus Datum und Uhrzeit)
                $this->throwMessException("Keine Sensoren(-Namen) vorhanden, nur Datum und Uhrzeit");
            }
        }

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
        // Sql Statement fuer eine Zeile erstellen
        $sql = "INSERT INTO messung (messreihe_id, zeitpunkt, datum_uhrzeit, mikrosekunden, sensor_id, messwert) VALUES ";
        for ($i = 2; $i < $spaltenanzahl; ++$i) {
            if ($i != 2) {
                $sql = $sql . ",";
            }
            $sql = $sql . "(?, ?, STR_TO_DATE(? ?, '%d.%m.%Y %H:%i:%s'), ?, ?, ?)";
        }

        $statement_messung = $this->_db->createStatement($sql);
        if ($statement_messreihe_sensor === FALSE) {
            $this->throwMessException("Fehler beim Erstellen des prepared statements von 'messung'");
        }

        // Iteration ueber alle Zeilen
//        $startTime = microtime(true); // Zeitmessung (fuer Log)
        $j = 0;
        while (!$this->_file->eof()) {
//            if ($j === 1000) {
//                $this->_logger->lwrite("Zeit für 1000 Zeilen: " . number_format(( microtime(true) - $startTime), 4) . " Sekunden");
//            }

            $messungsString = $this->encodeString($this->_file->fgets());
            $messungsSpalte = preg_split("/\t/", $messungsString);

            // Pruefung auf zu wenig Spalten
            if (count($messungsSpalte) !== $spaltenanzahl) {
                if ($this->_file->eof()) {
                    continue;
                } else {
                    $executeError = "Falsche Anzahl an Spalten in Zeile " . ($j + $this->_zeilennummerMessdaten)
                            . " (" . count($messungsSpalte) . " statt " . $spaltenanzahl . ")";
                    $this->throwMessException($executeError);
                }
            }

            $datum = &$messungsSpalte[0];
            $zeit = &$messungsSpalte[1];

            $zeit = $this->checkUhrzeitFormat($zeit, $j);

            $defaults = array($this->_messreiheID, $j, $datum, $zeit["uhrzeit"], $zeit["mikroseks"]);
            $values = array();

            for ($i = 2; $i < count($messungsSpalte); ++$i) {
                $messwert = $messungsSpalte[$i];
                // Ersetze NaN mit 0
                if ($messwert === "NaN") {
                    $messwert = "0";
                }
                // Ersetze Komma durch Punkt
                $messwert = str_replace(",", ".", $messwert);

                $values = array_merge($values, $defaults);
                array_push($values, $sensor_id_array[$i - 2]);
                array_push($values, $messwert);
            }

            $this->_db->executeStatement($statement_messung, $values);
            if ($this->_db->error()) {
                $this->throwMessException("Fehler beim INSERT von 'messung' "
                        . "(Zeile: " . ($j + $this->_zeilennummerMessdaten) . ")");
            }
            ++$j;
        }
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
            'Fehler' => $msg
        );
        throw new ParserException("Fehler in Funktion parseMetaDaten()", 0, null, $dbErrorMsg);
    }

    private function throwMessException($msg) {
        $dbErrorMsg = array(
            'Fehler' => $msg
        );
        throw new ParserException("Fehler in Funktion parseMessDaten()", 0, null, $dbErrorMsg);
    }

    private function checkUhrzeitFormat($zeit, $zeile) {
        $retVal = array();
        $zeit_splitted = preg_split("/[\.,]/", $zeit);
        $zeiten_teile = count($zeit_splitted);
        if ($zeiten_teile < 2) {
            $hh_mm_ss = explode(":", $zeit);
            if ($hh_mm_ss[2] > 59) {        // Fehler, wenn Sekunden groesser als 59 (Mikrosekunden ohne '.' oder ',' angehaengt
                $this->throwMessException("Ungültiges Uhrzeitformat in Zeile " .
                        ($zeile + $this->_zeilennummerMessdaten) . ": " . $zeit);
            }
            $uhrzeit = $zeit;
            $mikrosekunden = "000000";
        } else if ($zeiten_teile > 2) {
            $this->throwMessException("Ungültiges Uhrzeitformat in Zeile " .
                    ($zeile + $this->_zeilennummerMessdaten) . ": " . $zeit);
        } else {
            $uhrzeit = $zeit_splitted[0];
            $mikrosekunden = $zeit_splitted[1];
            if ($mikrosekunden > 999999) {      // Fehler, wenn Mikrosekunden aus mehr als 6 Stellen bestehen
                $this->throwMessException("Sekunden haben mehr als sechs Nachkommastellen in Zeile " .
                        ($zeile + $this->_zeilennummerMessdaten) . ": " . $zeit);
            }
            $mikrosekunden = $this->fillMikroseks($mikrosekunden);
        }
        $retVal["uhrzeit"] = $uhrzeit;
        $retVal["mikroseks"] = $mikrosekunden;
        return $retVal;
    }

    private function fillMikroseks($mikroseks) {
        $length = strlen($mikroseks);
        if ($length < 6) {
            $i = 6 - $length;
            for ($i = $length; $i < 6; ++$i) {
                $mikroseks .= "0";
            }
        }
        return $mikroseks;
    }

    private function encodeString($string) {
        $charset = mb_detect_encoding($string, "UTF-8, ISO-8859-1");
        // falls Datei/String in ISO Format ist muss konvertiert werden.
        // falls weitere charsets vorkommen muss iconv() statt utf8_encode benutzt werden
        if ($charset === "ISO-8859-1") {
            // $stringFile = utf8_encode($stringFile);
            $string = iconv("ISO-8859-1", "UTF-8", $string);
        }
        $string = str_replace("\r", "", $string);
        return $string;
    }

    public function errors() {
        return $this->_error;
    }

    public function warnings() {
        return $this->_warning;
    }

    public function getID() {
        return $this->_messreiheID;
    }

}
