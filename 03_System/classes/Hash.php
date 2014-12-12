<?php

/**
 * Hilfs-Klasse um Passwörter zu verschlüsseln.
 *
 * @author Sandro
 */
class Hash {
    
    /**
     * Generiert einen Hash für $string + $salt.
     * 
     * @param string $string der zu verschlüsselnde String
     * @param string $salt der Salt, mit dem der String verschlüsselt werden soll
     * @return string der generierte Hash für das Passwort
     */
    public static function make($string, $salt = '') {
        return hash('sha256', $string . $salt);
    }
    
    /**
     * Generiert einen Salt mit der Länge $length.
     * 
     * @param int $length die Länge des zu generierenden Salts
     * @return string der generierte Salt
     */
    public static function salt($length) {
        return mcrypt_create_iv($length);
    }
    
    /**
     * Erstellt eine gehashte unique ID.
     * 
     * @return type
     */
    public static function unique() {
        return self::make(uniqid());
    }
}
