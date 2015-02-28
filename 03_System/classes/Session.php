<?php

/**
 * Hilfs-Klasse um einfach Session-Variablen zu prüfen und bearbeiten.
 *
 * @author Sandro
 */
class Session {

    /**
     * Prüft ob die Session-Variable $name gesetzt ist.
     * 
     * @param string $name Name der Session-Variablen
     * @return boolean true, wenn sie gesetzt ist, false wenn nicht
     */
    public static function exists($name) {
        return (isset($_SESSION[$name])) ? true : false;
    }

    /**
     * Legt eine neue Session-Variable mit dem Namen $name und dem Wert $value an.
     * 
     * @param string $name Name der Session-Variablen
     * @param string/numeric $value Wert der Session-Variablen
     * @return string/numeric der Wert, der gesetzt wurde
     */
    public static function put($name, $value) {
        return $_SESSION[$name] = $value;
    }

    /**
     * Holt die Session-Variable mit dem Namen $name.
     * 
     * @param string $name Name der Session-Variablen
     * @return string/numeric Wert der Session-Variablen
     */
    public static function get($name) {
        return $_SESSION[$name];
    }

    /**
     * Löscht eine Session-Variable, falls diese gesetzt wurde.
     * 
     * @param string $name Name der zu löschenden Session-Variable
     */
    public static function delete($name) {
        if (self::exists($name)) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * Eine Hilfs-Funktion um Session-Variablen zu flashen.
     * Dabei wird beim ersten aufruf eine Session-Variable mit dem Namen $name
     * und der Nachricht $message (optional) gesetzt.
     * Beim zweiten aufruf wird die Session-Variable zurückgegeben und gelöscht.
     * Voraussetzung: Zwei identische Aufrufe mit den gleichen Parametern
     * 
     * @param string $name Name der Session-Variable
     * @param string/numeric $message Nachricht die geflasht werden soll.
     * @return string/numeric falls die Session-Variable gesetzt wurde, ansonsten void
     */
    public static function flash($name, $message = '') {
        if (self::exists($name)) {
            $session = self::get($name);
            self::delete($name);
            return $session;
        } else {
            self::put($name, $message);
        }
    }

}
