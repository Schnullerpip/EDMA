<?php
/**
 * Description of Parser
 *
 * @author phwachte
 */
class Parser {
    
    private $_file;
    private $db;
    
    public function __construct($file) {
        $this->_file = $file;
        $this->db = DB::getInstance();
        $this->parse();
    }
    
    private function parse() {
        $stringFile = file_get_contents($this->_file);
        $eol_unix = str_replace("\r", "", $stringFile);
        $file_array = explode("###", $eol_unix);
        $metadaten = $file_array[0];
        $messdaten = $file_array[1];
        
        $this->parseMetaDaten($metadaten);
        $this->parseMessDaten($messdaten);
    }
    
    private function parseMetaDaten($metadaten) {
        $metaNamePattern = "([\p{L}\p{N} \x{00B0}\x{0025}/\-]+)_([dns])";
        $stringPattern = "((?<=s:\t)[\p{L}\p{N}\. ,/\-]+)";
        $datePattern = "((?<=d:\t)\p{N}{2}\.\p{N}{2}\.\p{N}{4})";
        $numberPattern = "((?<=n:\t)[,\p{N}\.-]+)";
        $pattern = "#^" . $metaNamePattern . ":\t(" . $stringPattern . "|" . $datePattern . "|" . $numberPattern . ")$#um";
        $count = preg_match_all($pattern, $metadaten, $matches);
        $metalines = explode("\n", $metadaten);
        $lines = count($metalines);
        if ($count != $lines) {
            // TODO: errorhandling
        }

        // projekt_id holen
        // TODO: wie geht das über $projekt->data()->projektname?
        $projektname = array(
            "projektname" => "Testprojekt zum Testen",
        );
        $projekt_id = $this->$db->getIdBySelectOrInsert('projekt', $projektname);

        $messreihenname = array_search("Name", $matches[1]);
        $datum_index = array_search("Datum", $matches[1]);
        // TODO: STR_TO_DATE funktioniert nicht, noch nicht genauer angeschaut
        //$datum_mysql = "STR_TO_DATE(\"" . $matches[3][$datum_index] . "\", '%d.%m.%Y')";
        $datum_german = $matches[3][$datum_index];
        $datum_mysql = Utils::convertDate($datum_german);

        // insert messreihe
        $messreihe = array (
            "messreihenname" => $matches[3][$messreihenname],
            "datum" => $datum_mysql,
            "projekt_id" => $projekt_id,
        );
        $messreihe_id = $this->$db->getIdBySelectOrInsert('messreihe', $messreihe);

        // alle Indizes außer Indizes von "Name" und "Datum"
        $meta_indizes = array();
        foreach ($matches[1] as $metarow) {
            if ($metarow != "Name" && $metarow != "Datum") {
                $meta_index = array_search($metarow, $matches[1]);
                array_push($meta_indizes, $meta_index);
            }
        }

        // datentyp_id bestimmen
        $datentyp_s = array(
            "typ" => "string",
        );
        $datentyp_id_s = $this->$db->getIdBySelectOrInsert('datentyp', $datentyp_s);
        $datentyp_d = array(
            "typ" => "datum",
        );
        $datentyp_id_d = $this->$db->getIdBySelectOrInsert('datentyp', $datentyp_d);
        $datentyp_n = array(
            "typ" => "numerisch",
        );
        $datentyp_id_n = $this->$db->getIdBySelectOrInsert('datentyp', $datentyp_n);

        // insert metainfo und messreihe_metainfo
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
            $metainfo_id = $this->$db->getIdBySelectOrInsert('metainfo', $metainfo);

            $messreihe_metainfo = array(
                "messreihe_id" => $messreihe_id,
                "metainfo_id" => $metainfo_id,
                "metawert" => $metawert,
            );
            $this->$db->getIdBySelectOrInsert('messreihe_metainfo', $messreihe_metainfo);
        }
    }
    
    private function parseMessDaten($messdaten) {
        $messzeilen0 = preg_split("/\n/", $messdaten);
        $messzeilen = array_slice($messzeilen0, 1);	// array_slice() löscht erstes Element, da leer

        $messspalten = array();
        $spaltennamen = preg_split("/:[\t]*/", $messzeilen[0]);
        $elem_col = count($spaltennamen) - 1;   // letztes Element leer aufgrund der preg_split-Bedingung

        // insert sensor und messreihe_sensor
        $sensor_id_array = array();
        for ($i = 0; $i < $elem_col; $i++) {
            array_push($messspalten, array($spaltennamen[$i]));
            if ($i > 1) {
                $sensorname = $spaltennamen[$i];
                $sensor = array(
                    "sensorname" => $sensorname,
                );
                $sensor_id = $this->$db->getIdBySelectOrInsert('sensor', $sensor);
                array_push($sensor_id_array, $sensor_id);
                print_r("sensor_id: " . $sensor_id);
                echo "<br/><br/>";
                $messreihe_sensor = array(
                    "messreihe_id" => 2, //$messreihe_id,
                    "sensor_id" => $sensor_id,
                    "anzeigename" => $sensorname,
                );
                $this->$db->getIdBySelectOrInsert('messreihe_sensor', $messreihe_sensor);
            }
        }
        $messungen = array_slice($messzeilen, 1);	// löschen des Elements mit Sensornamen, da nicht mehr benötigt

        foreach ($messungen as $messungszeile) {
            $tmp_row = preg_split("/\t/", $messungszeile);
            // TODO: undefined offset, falls letzer Messwert der Zeile nicht vorhanden
            // nicht vorhandene Werte in DB als Zahl 0 gespeichert - Problem?
            for ($i = 0; $i < $elem_col; $i++) {
                    array_push($messspalten[$i], $tmp_row[$i]);
            }
        }

        // insert messung
        $elem_row = count($messspalten[0]);
        for ($i = 2; $i < $elem_col; $i++) {
            for ($j = 1; $j < $elem_row; $j++) {
                $messung = array(
                    "messreihe_id" => 2, //$messreihe_id,
                    "sensor_id" => $sensor_id_array[$i - 2],
                    "zeitpunkt" => $j,
                    "messwert" => str_replace(",", ".", $messspalten[$i][$j]),
                    // TODO:
        //            "datum_und_zeit" => "STR_TO_DATE(\"" . messspalten[0][$j] . messspalten[1][$j] . "\"%d.%m.%Y %h:%i:%s\")",
                );
                $this->$db->getIdBySelectOrInsert('messung', $messung);
            }
        }
    }

    public function errors() {
        return true;
    }
}
