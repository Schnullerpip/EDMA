<?php

require_once 'core/init.php';

if (Input::itemExists('id')) {

    $id = Input::get('id');
    $db = DB::getInstance();

    try {
        $data = $db->get("anhang", array('id', '=', $id))->first();

        header("Content-length: $data->groesse");
        header("Content-type: $data->dateityp");
        header('Content-Disposition: attachment; filename="' . $data->dateiname . '"');
        echo $data->inhalt;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    exit;
}
?>
