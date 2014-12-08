<?php
header('Content-Type: application/json');

require_once 'core/init.php';

$ajax = isset($_POST['ajax']);

$maxsize = null;
if (isset($_POST['maxsize'])) {
    $maxsize = $_POST['maxsize'];
}

$uploader = new Uploader($_FILES, $ajax, $maxsize);
