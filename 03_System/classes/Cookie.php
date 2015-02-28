<?php

/**
 * Hilfs-Klasse um Cookies anzulegen oder löschen
 *
 * @author Sandro
 */
class Cookie {

    /**
     * Prüft ob der Cookie $name existiert
     * 
     * @param string $name Der Name des Cookies
     * @return boolean
     */
    public static function exists($name) {
        return (isset($_COOKIE[$name])) ? true : false;
    }

    /**
     * Liest Wert des Cookies $name aus.
     * 
     * @param String $name Der Name des Cookies
     * @return string
     */
    public static function get($name) {
        return $_COOKIE[$name];
    }

    /**
     * Setzt neuen Cookie beim Client
     * 
     * @param string $name Cookiename
     * @param type $value Wert des Cookies
     * @param int $expiry Verfallsdatum des Cookies in Sekunden
     * @return boolean
     */
    public static function put($name, $value, $expiry) {
        if (setcookie($name, $value, time() + $expiry, '/')) {
            return true;
        }
        return false;
    }

    /**
     * Löscht den Cookie mit dem Namen $name
     * 
     * @param string $name Name des zu löschenden Cookies
     */
    public static function delete($name) {
        self::put($name, '', time() - 1);
    }

}
