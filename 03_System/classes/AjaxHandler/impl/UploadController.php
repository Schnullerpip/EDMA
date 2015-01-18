<?php

/**
 * Description of UploadController
 *
 * @author Sandro
 */
class UploadController extends AjaxController {

    private $_csv_mimetypes = array(
                'text/csv',
                'text/plain',
                'application/csv',
                'text/comma-separated-values',
                'application/excel',
                'application/vnd.ms-excel',
                'application/vnd.msexcel',
                'text/anytext',
                'application/octet-stream',
                'application/txt',
            ),
            $_description_mimetypes = array (
                'application/msword', // doc
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', //docx
                'application/vnd.oasis.opendocument.text', // openoffice
                'application/pdf', // pdf
            ),
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

    protected function process($id = null) {
        $uploadedFiles = array();
        if (Session::exists(Config::get('session/upload_name'))) {
            $uploadedFiles = Session::get(Config::get('session/upload_name'));
        }
        
        foreach ($this->_files['file']['name'] as $key => $fileName) {
            
            $fileSize = $this->_files['file']['size'][$key];
            $fileType = $this->_files['file']['type'][0];
            $fileTemp = $this->_files['file']['tmp_name'][$key];
            
            if ($this->_files['file']['error'][$key] !== 0) {
                $this->_failed[] = array(
                    'Dateiname' => $fileName,
                    'PHP Errorcode' => $this->_files['file']['error'][$key]
                );
                continue;
            }

            if (!in_array($fileType, $this->_description_mimetypes)) {
                $this->_failed[] = array(
                    'Dateiname' => $fileName,
                    'Fehler' => "Datei ist in ungültigem Dateiformat",
                    'Unterstütze Formate' => "doc, docx, pdf, odt",
                );
                continue;
            }

            // Validate Files
            if (!$this->validate($fileName, $fileSize)) {
                continue;
            }
            
            if (!file_exists('uploads/tmp')) {
               mkdir('uploads/tmp', 0775, true);
            }
            
            $filePath = "uploads/tmp/" . basename($fileTemp);
            if (move_uploaded_file($fileTemp, $filePath) === true) {
                $this->_succeeded[] = array(
                    'name' => $fileName,
                    'id' => $id,
                );
                $uploadedFiles[$fileName] = array(
                    'fileTemp' => $filePath,
                    'fileSize' => $fileSize,
                    'fileType' => $fileType,
                );
            } else {
                $this->_failed[] = array(
                    'Dateiname' => $fileName,
                    'Achtung' => 'Die Datei konnte nicht hochgeladen werden!'
                );
            }
        }
        
        if (count($uploadedFiles) >= 0) {
            Session::put(Config::get('session/upload_name'), $uploadedFiles);
        }
    }

    protected function import() {
        if ($this->_files['file']['size'][0] >= 0) {
            if (in_array($this->_files['file']['type'][0], $this->_csv_mimetypes)) {
                $parser = new Parser($this->_files['file']['tmp_name'][0], Input::get('projektid'));

                // TODO: Errorhandling / Successhandling
                // So in der Art:
                // der Parser hält ein Array "errors" in dem Fehler gespeichert werden
                if (count($parser->errors()) == 0) {
                    $this->_succeeded[] = array(
                        'Dateiname' => $this->_files['file']['name'][0],
                        'Import beendet' => 'Die Datei wurde erfolgreich importiert!'
                    );
                    if (count($parser->warnings()) != 0) {
                        $this->_warned[] = array(
                            'Warnung' => "Importieren der Datei wird möglicherweise abgebrochen."
                        );
                    }
                }
                else {
                    $this->_failed[] = array(
                        'Dateiname' => $this->_files['file']['name'][0],
                        'error' => $parser->errors()
                    );
                    if (count($parser->warnings()) != 0) {
                        $this->_warned[] = array(
                            'Warnung' => "Importieren der Datei wird möglicherweise abgebrochen."
                        );
                    }
                }
            } else {
                $this->_failed[] = array(
                    'name' => $this->_files['file']['name'][0],
                    'message' => 'Die Datei ist keine CSV Datei!'
                );
                return;
            }
        }
    }

}
