<?php
require_once 'core/init.php';

$projekt = new Projekt();
$projekt->logout();

Redirect::to('login');
