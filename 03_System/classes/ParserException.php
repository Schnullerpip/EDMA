<?php

class ParserException extends Exception {

    private $_messages;

    public function __construct($message, $code = 0, Exception $previous = null, $messages = array('params')) {
        parent::__construct($message, $code, $previous);
        $this->_messages = $messages;
    }

    public function getMessages() {
        return $this->_messages;
    }
}
