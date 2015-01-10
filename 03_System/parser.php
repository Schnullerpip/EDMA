<?php
require_once 'header.php';
if (Input::exists()) {
    if (Token::check(Input::get("token"))) {
        if ($_FILES['datei']['size'] > 0) {
            $parser = new Parser($projekt->data()->id, $_FILES['datei']['tmp_name']);
        } else {
            Session::flash("error", "Fehler beim Upload der Datei");
            Redirect::to("messreihen.php?id=neu");
        }
    }
}