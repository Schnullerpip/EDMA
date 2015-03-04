<?php
require_once 'core/init.php';

$projekt = new Projekt();
if (!$projekt->isLoggedIn() && curPageName() !== 'login.php') {
    Redirect::to('logout.php');
}

if(!Input::itemExists('projekt_save')) {
    if (Session::exists(Config::get('session/upload_name'))) {
        $uploadedFiles = Session::get(Config::get('session/upload_name'));
        foreach ($uploadedFiles as $uploadedFile) {
            unlink($uploadedFile['fileTemp']);
        }
        Session::delete(Config::get('session/upload_name'));
    }
    if (Session::exists(Config::get('session/removed_name'))) {
        Session::delete(Config::get('session/removed_name'));
    }
}
if (Input::itemExists('projekt_cancel')) {
    if (Session::get(Config::get('session/session_name')) === 'Neues Projekt') {
        Redirect::to('logout.php');
    } else {
        Redirect::to('index.php');
    }
}
?>
