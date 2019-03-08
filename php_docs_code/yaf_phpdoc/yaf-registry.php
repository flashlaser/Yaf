<?php
class Yaf_Registry {

	static $_instance;

	protected $_entries;
	/**
	 **/
	private function __clone() {}

	/**
	 **/
	function __construct() {}

	/**
	 * @param string $name
	 **/
	public static function del($name) {}

	/**
	 * @param string $name
	 * @return mixed
	 **/
	public static function get($name) {}

	/**
	 * @param string $name
	 * @return bool
	 **/
	public static function has($name) {}

	/**
	 * @param string $name
	 * @param string $value
	 * @return bool
	 **/
	public static function set($name,$value) {}


}