<?php

require_once 'core/init.php';

/**
 * Description of DeleteController
 *
 * @author Sandro
 */
class DeleteController extends AjaxController {

    public function __construct($element) {
        $name = $element['name'];
        $id = $element['id'];

        switch ($name) {
            case 'projektbeschreibung':
                $this->process('anhang', $id);
                break;

            default:
                break;
        }
    }

    public function process($name, $id) {
        // delete from anhang where id = $id
        $this->_succeeded[] = array(
            'id' => 0
        );
    }
}
