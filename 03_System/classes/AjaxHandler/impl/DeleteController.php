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
    private $_filenName; // Dateiname der Projektbeschreibung

    public function __construct($element, $id, $filename) {
        $this->_id = $id;
        $this->_filenName = $filename;
        $this->_db = DB::getInstance();
        switch ($element) {
            case 'projektbeschreibung':
                $this->process();
                break;

            case 'messreihe':
                $this->deleteMessreihe();

                break;

            case 'projekt':
                $this->deleteProjekt();

                break;

            default:
                break;
        }
    }

    public function process() {
        if ($this->_id == -1) {
            // Neue Beschreibung, noch nicht in der DB aber in der Session
            $uploadedFiles = Session::get(Config::get('session/upload_name'));
            // Datei aus uploads-Verzeichnis loeschen
            unlink($uploadedFiles[$this->_filenName]['fileTemp']);
            // Aus Session entfernen damit die Datei nicht hochgeladen wird.
            unset($uploadedFiles[$this->_filenName]);
            Session::put(Config::get('session/upload_name'), $uploadedFiles);
        } else {
            // Beschreibung wurde bereits importiert, zum Loeschen vormerken
            if (Session::exists(Config::get('session/removed_name'))) {
                $fielsToDelete = Session::get(Config::get('session/removed_name'));
            } else {
                $fielsToDelete = array();
            }
            $fielsToDelete[$this->_filenName] = $this->_id;
            Session::put(Config::get('session/removed_name'), $fielsToDelete);
        }
        
        $this->_succeeded[] = array(
            'id' => $this->_id,
            'filename' => $this->_filenName
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
    
    private function deleteProjekt() {
        $this->_db->get('projekt', array('id', '=', $this->_id));
        $projekt = $this->_db->first();
        
        $this->_db->delete('projekt', array('id', '=', $this->_id));
        
        // Not in if wegmachen! 
       if ($this->_db->error()) {
            $this->_failed = array(
                'name' => $projekt->projektname,
                'message' => 'Das Projekt konnte nicht gelöscht werden!'
            );
        }
    }

}
