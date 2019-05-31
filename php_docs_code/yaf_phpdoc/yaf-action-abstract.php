<?php
abstract class Yaf_Action_Abstract extends Yaf_Controller_Abstract {

	protected $_controller;
	/**
	 * @param mixed $arg
	 * @param mixed $...
	 * @return mixed
	 **/
	abstract public function execute($arg) ;

	/**
	 * @return Yaf_Controller_Abstract
	 **/
	public function getController() {}


}