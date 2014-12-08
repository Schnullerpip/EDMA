<?php

/**
 * Description of Uploader
 *
 * @author Sandro
 */
class Uploader {

    private $_allowed = ['pdf'],
            $_failed = [],
            $_succeeded = [],
            $_maxSize;

    public function __construct($files, $ajax, $maxsize) {
        $this->_maxSize = $maxsize;

        foreach ($files['file']['name'] as $key => $name) {
            if ($files['file']['error'][$key] === 0) {

                $temp = $_FILES['file']['tmp_name'][$key];

                // Validate Files
                $fileName = $_FILES['file']['name'][$key];
                $fileSize = $_FILES['file']['size'][$key];
                if (!$this->validate($fileName, $fileSize)) {
                    continue;
                }

                if (move_uploaded_file($temp, "uploads/tmp/{$name}") === true) {
                    $this->_succeeded[] = array(
                        'name' => $name,
                        'date' => date('d.m.Y')
                    );
                } else {
                    $this->_failed[] = array(
                        'name' => $name,
                        'error' => 'Die Datei konnte nicht hochgeladen werden!'
                    );
                }
            } else {
                $this->_failed[] = array(
                    'name' =>  $name,
                    'error' => $files['file']['error'][$key]
                );
            }
        }

        if ($ajax) {
            echo json_encode(array(
                'succeeded' => $this->_succeeded,
                'failed' => $this->_failed
            ));
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
        
    }

    public function errors() {
        return $this->_failed;
    }

    public function succeeded() {
        return $this->_succeeded;
    }

}
