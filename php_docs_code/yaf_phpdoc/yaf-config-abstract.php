<?php
abstract class Yaf_Config_Abstract {

	protected $_config;

	protected $_readonly;
	/**
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 **/
	abstract public function get($name,$value) ;

	/**
	 * @return bool
	 **/
	abstract public function readonly() ;

	/**
	 * @return Yaf_Config_Abstract
	 **/
	abstract public function set() ;

	/**
	 * @return array
	 **/
	abstract public function toArray() ;


}