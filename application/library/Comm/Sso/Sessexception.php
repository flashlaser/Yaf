<?php

/**
 * session manager使用的Exception类
 */

class Comm_Sso_Sessionexception extends Exception {
	private $_delaytime;
	
	public function __construct($message, $code, $delaytime = 0) {
		parent::__construct($message, $code);
		$this->_delaytime = $delaytime;
	}
	
	public function setDelayTime($delaytime) {
		$this->_delaytime = $delaytime;
	}
	
	public function getDelayTime() {
		return $this->_delaytime;
	}
}
