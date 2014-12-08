<?php

/**
 * Description of Validation
 *
 * @author Sandro
 */
class Validate {
    private $_passed = false,
            $_errors = array();
    
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
     * @param type $error
     */
    private function addError($error) {
        $this->_errors[] = $error;
    }
    
    public function errors() {
        return $this->_errors;
    }
    
    public function passed() {
        return $this->_passed;
    }
}
