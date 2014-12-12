<?php

/**
 * Hilfs-Klasse um Input-Felder auf eventuelle Eingaben zu prüfen.
 *
 * @author Sandro
 */
class Input {

    /**
     * Prüft ob in dem vom User übermittelten Formular etwas eingetragen wurde.
     * 
     * @param string $type die Art wie das Formular abgeschickt wird. post oder get
     * @return boolean true, falls der User etwas per post oder get in einem
     * Formular abgeschickt hat. False, falls der $type unbekannt oder keine
     * Eingaben gemacht wurden.
     */
    public static function exists($type = 'post') {
        switch ($type) {
            case 'post':
                return (!empty($_POST)) ? true : false;
            case 'get':
                return (!empty($_GET)) ? true : false;
            default:
                return false;
        }
    }

    /**
     * Holt den Wert von dem Input-Feld mit dem name="$item", falls dieser
     * existiert.
     * 
     * @param string $item Name des Input-Feldes
     * @return string Wert des Input-Feldes oder leerer String, falls der Wert
     * nicht existiert.
     */
    public static function get($item) {
        if (isset($_POST[$item])) {
            return $_POST[$item];
        } else if (isset($_GET[$item])) {
            return $_GET[$item];
        }
        return '';
    }

}
