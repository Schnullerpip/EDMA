<?php

/**
 * Description of Projekt
 *
 * @author sandro
 */
class Projekt {

    private $_db,
            $_data,
            $_sessionName,
            $_isMaster,
            $_isLoggedIn;

    public function __construct($projekt = null) {
        $this->_db = DB::getInstance();
        $this->_sessionName = Config::get('session/session_name');

        if (!$projekt) {
            if (Session::exists($this->_sessionName)) {
                $projekt = Session::get($this->_sessionName);

                if (Session::exists('master')) {
                    $this->_isMaster = true;
                }

                if ($projekt === 'Neues Projekt' or $this->find($projekt)) {
                    $this->_isLoggedIn = true;
                }
            }
        } else {
            $this->find($projekt);
        }
    }

    private function find($projekt = null) {
        if ($projekt) {
            if ($projekt === 'new') {
                $tmpData = array('id' => 'Neues Projekt');
                $this->_data = (object) $tmpData;

                return true;
            } else {
                $field = (is_numeric($projekt)) ? 'id' : 'projektname';

                $data = $this->_db->get('projekt', array($field, '=', $projekt));

                if ($data->count()) {
                    $this->_data = $data->first();
                    return true;
                }
            }
        }
    }

    public function login($projekt = null, $password = null) {
        if (!$projekt && !$password && $this->exists()) {
            Session::put($this->_sessionName, $this->data()->id);
        } else {
            $projekt = $this->find($projekt);

            if ($projekt) {
                if ($this->_data->id === 'Neues Projekt') {
                    $data = $this->_db->query("SELECT * FROM passwort WHERE projekt_id IS NULL");
                } else {
                    $data = $this->_db->get('passwort', array('id', '=', $this->data()->passwort_id));
                }
                
                if ($data->count()) {
                    $masterpw = $data->first();
                    
                    if ($masterpw->hash == Hash::make($password, $masterpw->salt)) {
                        Session::put($this->_sessionName, $this->data()->id);
                        Session::put('master', 1);

                        return true;
                    }
                }
                
                $data = $this->_db->get('passwort', array('projekt_id', '=', $this->data()->id));
                
                if ($data->count()) {
                    $pws = $data->results();
    
                    foreach ($pws as $pw) {
                        if ($pw->hash === Hash::make($password, $pw->salt)) {
                            Session::put($this->_sessionName, $this->data()->id);
                            
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function exists() {
        return !is_null($this->data());
    }

    public function logout() {
        Session::delete($this->_sessionName);
        Session::delete('master');
    }

    public function data() {
        return $this->_data;
    }

    public function isLoggedIn() {
        return $this->_isLoggedIn;
    }

    public function isMaster() {
        return $this->_isMaster;
    }

}
