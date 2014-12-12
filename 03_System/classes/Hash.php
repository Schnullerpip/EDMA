<?php

/**
 * Description of Hash
 *
 * @author Sandro
 */
class Hash {
    
    /**
     * Generiert für einen String mit dem Salt einen Hash
     * 
     * @param type $string
     * @param type $salt
     * @return type
     */
    public static function make($string, $salt = '') {
        return hash('sha256', $string . $salt);
    }
    
    public static function salt($length) {
        return mcrypt_create_iv($length);
    }
    
    public static function unique() {
        return self::make(uniqid());
    }
}
