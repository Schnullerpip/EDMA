<?php

/**
 * Hilfs-Klasse mit verschiedenen Hilfs-Funktionen.
 *
 * @author Sandro
 */
class Utils {

    /**
     * Konvertiert eine Größenangabe im von MB, GB etc. in Bytes.
     * 
     * Als Einheit möglich sind:
     * k
     * m
     * g
     * 
     * @param string/numeric $value
     * @return int Größe in Byte
     */
    public static function convertBytes($value) {
        if (is_numeric($value)) {
            return $value;
        } else {
            $value_length = strlen($value);
            $qty = substr($value, 0, $value_length - 1);
            $unit = strtolower(substr($value, $value_length - 1));
            switch ($unit) {
                case 'k':
                    $qty *= 1024;
                    break;
                case 'm':
                    $qty *= 1048576;
                    break;
                case 'g':
                    $qty *= 1073741824;
                    break;
            }
            return $qty;
        }
    }
    /**
     * Konvertiert ein deutschformatiges Datum in ein für die Datenbank
     * geeignetes Format, z.B.:
     * 25.07.2014 --> 2014-07-25
     * 
     * @param deutschformatiges Datum $date
     * @return Datum in mysql-Format
     */
    public static function convertDate($date) {
        $datum_array = explode(".", $date);
        $datum_mysql = $datum_array[2] . "-" 
                . $datum_array[1] . "-" 
                . $datum_array[0];
        return $datum_mysql;
    }

}
