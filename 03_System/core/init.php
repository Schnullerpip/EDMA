<?php

session_start();

$GLOBALS['config'] = array(
    'mysql' => array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'db' => 'EDMA'
    ),
    'remember' => array(
        'cookie_name' => 'hash',
        'cookie_expiry' => 604800
    ),
    'session' => array(
        'session_name' => 'projekt',
        'token_name' => 'token'
    ),
    'settings' => array(
        'masterpassword' => 'master'
    )
);

define('HOME', 'http://localhost/virtual/EDMANetBeans/');

/**
 * Lädt eine Klasse automatisch beim Aufruf $class = new Class();
 * Somit muss nicht ständig ein require auf die benötigte Klasse gemacht werden.
 * Stattdessen wird sie nur required, wenn sie auch tatsächlich benutzt wird.
 */
spl_autoload_register('autoload');

/**
 * autoload
 *
 * @author Joe Sexton <joe.sexton@bigideas.com>
 * @param  string $class
 * @param  string $dir
 * @return bool
 */
function autoload($class, $dir = null) {
    if (is_null($dir))
        $dir = 'classes/';

    foreach (scandir($dir) as $file) {

        // directory?
        if (is_dir($dir . $file) && substr($file, 0, 1) !== '.')
            autoload($class, $dir . $file . '/');

        // php file?
        if (substr($file, 0, 2) !== '._' && preg_match("/.php$/i", $file)) {

            // filename matches class?
            if (str_replace('.php', '', $file) == $class || str_replace('.class.php', '', $file) == $class) {

                include $dir . $file;
            }
        }
    }
}


/**
 * Kann leider nicht so wie oben gemacht werden, da functions keine Klasse ist.
 */
require_once 'functions/sanitize.php';

if (Cookie::exists(Config::get('remember/cookie_name')) && !Session::exists(Config::get('session/session_name'))) {
    $hash = Cookie::get(Config::get('remember/cookie_name'));
    $hashCheck = DB::getInstance()->get('users_session', array('hash', '=', $hash));

    if ($hashCheck->count()) {
        $user = new User($hashCheck->first()->user_id);
        $user->login();
    }
}
