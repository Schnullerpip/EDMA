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
        $name = $element;
        $this->_files = $files;
        $this->_maxSize = Input::get('maxsize');

        switch ($name) {
            case 'projektbeschreibung':
                $this->process('anhang');
                break;
            case 'messreihe':
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

    protected function process($name, $id = null) {
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

}
