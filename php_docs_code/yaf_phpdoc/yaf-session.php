<?php
class Yaf_Session implements Iterator,Traversable,ArrayAccess,Countable {

	protected $_instance;

	protected $_session;

	protected $_started;
	/**
	 **/
	private function __clone() {}

	/**
	 **/
	function __construct() {}

	/**
	 **/
	public function count() {}

	/**
	 **/
	public function current() {}

	/**
	 * @param string $name
	 **/
	public function del($name) {}

	/**
	 * @param string $name
	 **/
	public function __get($name) {}

	/**
	 **/
	public static function getInstance() {}

	/**
	 * @param string $name
	 **/
	public function has($name) {}

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
	public function rewind() {}

	/**
	 * @param string $name
	 * @param string $value
	 **/
	public function __set($name,$value) {}

	/**
	 **/
	private function __sleep() {}

	/**
	 **/
	public function start() {}

	/**
	 * @param string $name
	 **/
	public function __unset($name) {}

	/**
	 **/
	public function valid() {}

	/**
	 **/
	private function __wakeup() {}


}