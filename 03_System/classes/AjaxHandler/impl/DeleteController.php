<?php

require_once 'core/init.php';

/**
 * Description of DeleteController
 *
 * @author Sandro
 */
class DeleteController extends AjaxController {

    private $_id; // ID der zu loeschenden Messreihe oder Projektbeschreibung
    private $_db;

    public function __construct($element, $id) {
        $this->_id = $id;

        $this->_db = DB::getInstance();

        switch ($element) {
            case 'projektbeschreibung':
                $this->process('anhang');
                break;

            case 'messreihe':
                $this->deleteMessreihe();
                break;

            default:
                break;
        }
    }

    public function process() {
        $this->_succeeded[] = array(
            'id' => $this->_id
        );
    }

    private function deleteMessreihe() {
        $this->_db->get('messreihe', array('id', '=', $this->_id));
        $messreihe = $this->_db->results();

        $this->_db->delete('messreihe', array('id', '=', $this->_id));
        if ($this->_db->error()) {
            $this->_failed = array(
                'name' => $messreihe->messreihenname,
                'message' => 'Die Messreihe konnte nicht gelöscht werden!'
            );
        }
    }

}
