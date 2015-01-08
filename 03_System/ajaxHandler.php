<?php

require_once 'core/init.php';

if (Input::get('function') !== '') {
    switch (Input::get('function')) {
        case 'delete':
            $controller = new DeleteController(Input::get('element'));
            break;
        case 'upload':
            $controller = new UploadController(Input::get('element'), $_FILES);
            break;

        default:
            break;
    }

    $controller->toString(Input::get('ajax'));
}

