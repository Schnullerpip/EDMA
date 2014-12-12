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
            $_errors = array();
    
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
            $fieldname = $item;
            foreach ($rules as $rule => $rule_value) {
                $value = trim($source[$item]);
                
                if ($rule === 'fieldname') {
                    $fieldname = $rule_value;
                }
                
                if ($rule === 'required' && empty($value)) {
                    $this->addError($fieldname . " ist ein Pflichtfeld.");
                } else if (!empty ($value)) {
                    switch ($rule) {
                        case 'min':
                            if (strlen($value) < $rule_value) {
                                $this->addError($item . ' muss mindestens ' .
                                        $rule_value . ' Zeichen lang sein.');
                            }
                            break;
                        case 'max':
                            if (strlen($value) > $rule_value) {
                                $this->addError($item . ' darf maximal ' .
                                        $rule_value . ' Zeichen lang sein.');
                            }
                            break;
                        case 'matches':
                            if ($value != $source[$rule_value]) {
                                if (isset($items[$rule_value]['fieldname'])) {
                                    $matchesFieldname = $source[$rule_value]['fieldname'];
                                } else {
                                    $matchesFieldname = $rule_value;
                                }
                                $this->addError($matchesFieldname . ' muss gleich '
                                        . 'sein wie ' . $fieldname);
                            }
                            break;
                        case 'unique':
                            // DB Anfrage
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
