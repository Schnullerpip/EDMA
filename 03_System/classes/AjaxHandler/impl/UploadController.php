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
            $_files,
            $_db;

    public function __construct($element, $files) {
        $this->_files = $files;
        $this->_maxSize = Input::get('maxsize');
        $this->_db = DB::getInstance();

        switch ($element) {
            case 'projektbeschreibung':
                $this->process(Input::get('projektID'));
                break;
            case 'messreihe':
                $this->import();
                break;

            default:
                break;
        }
    }

    private function validate($fileName, $fileSize, $fileType) {
        if ($fileSize > $this->_maxSize) {
            $this->_failed[] = array(
                'file' => $fileName,
                'error' => 'Die Datei ist zu groß (' . $fileSize . ' von ' . $this->_maxSize . ')!'
            );

            return false;
        }

        if (!in_array($fileType, $this->_description_mimetypes)) {
            $this->_failed[] = array(
                'Dateiname' => $fileName,
                'Fehler' => "Datei ist in ungültigem Dateiformat",
                'Unterstütze Formate' => "doc, docx, pdf, odt",
            );
            
            return false;
        }

        return true;
    }

    protected function process($id = null) {
        $uploadedFiles = array();
        if (Session::exists(Config::get('session/upload_name'))) {
            $uploadedFiles = Session::get(Config::get('session/upload_name'));
        }
        
        $filesToDelete = array();
        if (Session::exists(Config::get('session/removed_name'))) {
            $filesToDelete = Session::get(Config::get('session/removed_name'));
        }
        
        foreach ($this->_files['file']['name'] as $key => $fileName) {
 
            $fileSize = $this->_files['file']['size'][$key];
            $fileType = $this->_files['file']['type'][$key];
            $fileTemp = $this->_files['file']['tmp_name'][$key];
            
            if ($this->_files['file']['error'][$key] !== 0) {
                $this->_failed[] = array(
                    'Dateiname' => $fileName,
                    'PHP Errorcode' => $this->_files['file']['error'][$key]
                );
                continue;
            }

            // Validate Files
            if (!$this->validate($fileName, $fileSize, $fileType)) {
                continue;
            }
            
            if (array_key_exists($fileName, $uploadedFiles)) {
                // Datei wurde gerade hochgeladen
                $this->_succeeded[] = array("skipped");
                continue;
            }
            
            $anhangId = -1;
            $refreshList = false;
            if (array_key_exists($fileName, $filesToDelete)) {
                // Datei wurde vorhin geloescht, wieder herstellen
                $anhangId =  $filesToDelete[$fileName];
                unset($filesToDelete[$fileName]);
                
                $refreshList = true;
            } else {
                // Pruefen ob Datei schon in der DB und damit schon in der Liste ist
                $sql = 'SELECT id FROM anhang WHERE projekt_id = ? and dateiname = ?';
                
                if ($this->_db->query($sql, array($id, $fileName))->count() === 0) {
                    // Noch nicht in der Liste
                    $refreshList = true;
                }
            }
                        
            if (!file_exists('uploads/tmp')) {
               mkdir('uploads/tmp', 0775, true);
            }
            
            $filePath = "uploads/tmp/" . basename($fileTemp);
            if (move_uploaded_file($fileTemp, $filePath) === true) {  
                $uploadedFiles[$fileName] = array(
                    'fileTemp' => $filePath,
                    'fileSize' => $fileSize,
                    'fileType' => $fileType,
                );
                
                if ($refreshList) {
                    // Anhang neu oder wiederhergestellt, Eintrag in Frontend
                    $this->_succeeded[] = array(
                        'name' => $fileName,
                        'id' => $anhangId,
                    ); 
                } else {
                    $this->_succeeded[] = array(
                        'name' => $fileName,
                        'id' => 'refreshed'
                    );
                }
            } else {
                $this->_failed[] = array(
                    'Dateiname' => $fileName,
                    'Achtung' => 'Die Datei konnte nicht kopiert werden!'
                );
            }
        }
        
        if (count($uploadedFiles) >= 0) {
            Session::put(Config::get('session/upload_name'), $uploadedFiles);
        }
        Session::put(Config::get('session/removed_name'), $filesToDelete);
        
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
                        'Import beendet' => 'Die Datei wurde erfolgreich importiert!',
                        'messreiheID' => $parser->getID()
                    );
                }
                else {
                    $this->_failed[] = array(
                        'Dateiname' => $this->_files['file']['name'][0],
                        'error' => $parser->errors()
                    );
                }
                
                if (count($parser->warnings()) != 0) {
                    $this->_warned[] = array_merge($parser->warnings(), array(
                        'Warnung' => "Import wird bei ignoriertem Pflichtfeld abgebrochen."
                    ));
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
