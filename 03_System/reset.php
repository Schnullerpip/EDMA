<?php
require_once 'header.php';

Session::delete(Config::get('session/upload_name'));
Redirect::to('index.php');