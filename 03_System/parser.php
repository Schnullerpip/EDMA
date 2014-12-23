<?php

if (Input::exists()) {
    if (Token::check(Input::get("token"))) {
        if ($_FILES['datei']['size'] == 0) {
            $parser = new Parser($_FILES['datei']['tmp_name']);
        }
        // TODO: errorhandling
    }
}