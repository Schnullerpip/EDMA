<?php
session_start();

$GLOBALS['config'] = array(
    'mysql' => array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'db' => 'teamprojekt'
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
spl_autoload_register(function($class) {
    require_once 'classes/' . $class . '.php';
});

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

function setMasterPassWord($newPass) {
    $GLOBALS['config']['settings']['masterpassword'] = $newPass;
} 
