<?php

/**
 * Eine Klasse zum Überprüfen von Eingabefeldern.
 * 
 * Es kann z.B. definiert werden, dass ein Input-Feld mit dem Namen 'user'
 * mindestens 5 Zeichen haben soll. Oder dass das Passwort Feld required ist etc.
 * Dabei wird ein array mit den Feldern angelegt, welche wiederrum ein Array mit
 * den kriterien haben. Das Array für ein required 'passwort'-Feld sieht z.B.
 * so aus:
 * 'password' => array('required' => true)
 * 
 * Es kann auch ein Ausgabewert für den Feldnamen angegeben werden, wenn die
 * Validierung des Feldes fehlschlägt, z.B.:
 * 'fieldname' => 'Passwort'
 *
 * @author Sandro
 */
class Validate {

    private $_passed = false,
            $_errors = array(),
            $_db;

    /**
     * Standardconstruktor
     */
    public function __construct() {
        $this->_db = DB::getInstance();
    }
    
    /**
     * Prüft die Eingabewerte von $source ($_POST oder $_GET) auf die angegebenen
     * Anforderungen in $items
     * 
     * @param array $source $_POST oder $_GET
     * @param array $items die Anforderungen
     * @return \Validate
     */
    public function check($source, $items = array()) {
        foreach ($items as $item => $rules) {
            if (isset($rules['fieldname'])) {
                $name = $rules['fieldname'];
            } else {
                $name = $item;
            }
            
            foreach ($rules as $rule => $rule_value) {
                $value = trim($source[$item]);

                if ($rule === 'required' && empty($value)) {
                    $this->addError($name . " ist ein Pflichtfeld.");
                } else if (!empty($value)) {
                    switch ($rule) {
                        case 'min':
                            if (strlen($value) < $rule_value) {
                                $this->addError($name . ' muss mindestens ' .
                                        $rule_value . ' Zeichen lang sein.');
                            }
                            break;
                        case 'max':
                            if (strlen($value) > $rule_value) {
                                $this->addError($name . ' darf maximal ' .
                                        $rule_value . ' Zeichen lang sein.');
                            }
                            break;
                        case 'matches':
                            if ($value != $source[$rule_value]) {
                                if (isset($items[$rule_value]['fieldname'])) {
                                    $matches = $items[$rule_value]['fieldname'];
                                } else {
                                    $matches = $rule_value;
                                }
                                $this->addError('"' . $matches . '" muss gleich '
                                        . 'sein wie "' . $name . '"');
                            }
                            break;
                        case 'unique':
                            // DB Anfrage
                            //$whereArray = array($rule_value['field'], '=', $value);
                            //$query = "SELECT * FROM {$rule_value['table']} WHERE {$rule_value['field']} = ? AND NOT {$rule_value['except']}";
                            $query = "SELECT * FROM ? WHERE ? = ? AND NOT ?";
                            //$this->_db->query($query, array($value));
                            $this->_db->query($query, array($rule_value['table'], $rule_value['field'], $value, $rule_value['except']));
                            if ($this->_db->count() !== 0) {
                                $this->addError("\"{$name}\" muss eindeutig sein. "
                                . "\"{$value}\" ist bereits vorhanden.");
                            }
                            break;
                    }
                }
            }
        }

        if (empty($this->_errors)) {
            $this->_passed = true;
        }

        return $this;
    }

    /**
     * Fuegt dem Errors-Array ein neuen Eintrag hinzu
     * 
     * @param string $error die Fehlermeldung
     */
    private function addError($error) {
        $this->_errors[] = $error;
    }

    /**
     * Gibt das Array mit eventuellen Fehlern aus.
     * 
     * @return string-array mit den Fehlern
     */
    public function errors() {
        return $this->_errors;
    }

    /**
     * Gibt true zurück, wenn die Validierung erfolgreich war. Ansonsten false
     * 
     * @return boolean
     */
    public function passed() {
        return $this->_passed;
    }

}
