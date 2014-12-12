<?php

/**
 * Hilfs-Klasse um schnell Werte aus der config ('core/init.php') zu holen.
 *
 * @author Sandro
 */
class Config {
    
    /**
     * Hole den Wert aus der Config
     * 
     * @param string $path der Config-Eintrag
     */
    public static function get($path = null) {
        if ($path) {
            $config = $GLOBALS['config']; 
            $path = explode('/', $path);
            
            foreach ($path as $bit) {
                if (isset($config[$bit])) {
                    $config = $config[$bit];
                }
            }
            
            return $config;
        }
    }
}
