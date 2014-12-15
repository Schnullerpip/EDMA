<?php

function escape($string) {
    return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Hole aktuelle Seite
 * 
 * @return type
 */
function curPageName() {
    return substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);
}
