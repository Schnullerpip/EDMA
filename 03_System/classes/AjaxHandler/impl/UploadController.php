<?php

/**
 * Description of UploadController
 *
 * @author Sandro
 */
class UploadController extends AjaxController {

    private $_allowed = ['.pdf', '.doc', '.csv'],
            $_maxSize,
            $_files;

    public function __construct($element, $files) {
        $this->_files = $files;
        $this->_maxSize = Input::get('maxsize');

        switch ($element) {
            case 'projektbeschreibung':
                $this->process();
                break;
            case 'messreihe':
                $this->import();
                break;

            default:
                break;
        }
    }

    private function validate($fileName, $fileSize) {
        if ($fileSize > $this->_maxSize) {
            $this->_failed[] = array(
                'file' => $fileName,
                'error' => 'Die Datei ist zu groß (' . $fileSize . ' von ' . $this->_maxSize . ')!'
            );

            return false;
        }

        // Other Stuff

        return true;
    }

    /**
     * Verschiebt die hochgeladenen Dateien in das Projekt-Verzeichnis
     */
    public function moveFiles() {
        // TODO: DB Insert
    }

    protected function process($id = null) {
        foreach ($this->_files['file']['name'] as $key => $filename) {
            if ($this->_files['file']['error'][$key] === 0) {

                $temp = $this->_files['file']['tmp_name'][$key];

                // Validate Files
                $fileName = $this->_files['file']['name'][$key];
                $fileSize = $this->_files['file']['size'][$key];
                if (!$this->validate($fileName, $fileSize)) {
                    continue;
                }

                if (move_uploaded_file($temp, "uploads/tmp/{$filename}") === true) {
                    $this->_succeeded[] = array(
                        'name' => $filename,
                        'date' => date('d.m.Y')
                    );
                } else {
                    $this->_failed[] = array(
                        'name' => $filename,
                        'error' => 'Die Datei konnte nicht hochgeladen werden!'
                    );
                }
            } else {
                $this->_failed[] = array(
                    'name' => $filename,
                    'error' => $this->_files['file']['error'][$key]
                );
            }
        }
    }
    
    protected function import() {
        if ($this->_files['file']['size'] >= 0) {
            $parser = new Parser($_FILES['file']['tmp_name'], Input::get('projektid'));
        }
        
        // TODO: Errorhandling / Successhandling
        // So in der Art:
        // der Parser hält ein Array "errors" in dem Fehler gespeichert werden
        if (count($parser->errors()) == 0) {
            $this->_succeeded[] = array(
                'name' => $this->_files['file']['name'],
                'message' => 'Die Datei wurde erfolgreich importiert!'
            );
        } else {
            $this->_failed[] = array(
                'name' => $this->_files['file']['name'],
                'message' => 'Fehler beim Importieren der Datei!',
                'error' => array(
                    $parser->errors()
                )
            );
        }
    }

}
