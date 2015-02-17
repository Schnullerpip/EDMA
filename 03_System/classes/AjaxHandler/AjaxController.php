<?php

/**
 * Description of Controller
 *
 * @author Sandro
 */
abstract class AjaxController {

    public $_succeeded = [];
    public $_warned = [];
    public $_failed = [];

    public function toString($ajax) {
        if ($ajax !== '') {
            echo json_encode(array(
                'succeeded' => $this->_succeeded,
                'warned' => $this->_warned,
                'failed' => $this->_failed
            ));
        } else {
            print_r($this->_failed);
        }
    }

}
