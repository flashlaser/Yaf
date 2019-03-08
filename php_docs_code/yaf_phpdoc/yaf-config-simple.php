<?php
class Yaf_Config_Simple extends Yaf_Config_Abstract implements Iterator,Traversable,ArrayAccess,Countable {

	protected $_readonly;
	/**
	 * @param string $config_file
	 * @param string $section
	 **/
	public function __construct($config_file,$section) {}

	/**
	 **/
	public function count() {}

	/**
	 **/
	public function current() {}

	/**
	 * @param string $name
	 **/
	public function __get($name) {}

	/**
	 * @param string $name
	 **/
	public function __isset($name) {}

	/**
	 **/
	public function key() {}

	/**
	 **/
	public function next() {}

	/**
	 * @param string $name
	 **/
	public function offsetExists($name) {}

	/**
	 * @param string $name
	 **/
	public function offsetGet($name) {}

	/**
	 * @param string $name
	 * @param string $value
	 **/
	public function offsetSet($name,$value) {}

	/**
	 * @param string $name
	 **/
	public function offsetUnset($name) {}

	/**
	 **/
	public function readonly() {}

	/**
	 **/
	public function rewind() {}

	/**
	 * @param string $name
	 * @param string $value
	 **/
	public function __set($name,$value) {}

	/**
	 **/
	public function toArray() {}

	/**
	 **/
	public function valid() {}


}