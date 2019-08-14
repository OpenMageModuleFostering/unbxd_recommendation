<?php

class Unbxd_Recscore_Model_State_Response {

	protected $_status = false;

	protected $_message = array();
	
	public function getStatus() {
		return $this->_status;
	}
	
	public function setStatus($status = true) {
		$this->_status = ($status)?true:false;
	}

	public function getMessage() {
		return $this->_message;
	}

	public function setMessage(array $_message = array()) {
		$this->_message = $_message;
	}

	public function _asArray() {
		return array('status' => $this->_status, 'message' => $this->_message);
	}
		
}
