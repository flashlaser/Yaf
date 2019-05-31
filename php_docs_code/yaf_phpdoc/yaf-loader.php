<?php
class Yaf_Loader {

	protected $_local_ns;

	protected $_library;

	protected $_global_library;

	static $_instance;
	/**
	 **/
	public function autoload() {}

	/**
	 **/
	public function clearLocalNamespace() {}

	/**
	 **/
	private function __clone() {}

	/**
	 **/
	public function __construct() {}

	/**
	 **/
	public static function getInstance() {}

	/**
	 * @param bool $is_global
	 * @return Yaf_Loader
	 **/
	public function getLibraryPath($is_global) {}

	/**
	 **/
	public function getLocalNamespace() {}

	/**
	 **/
	public static function import() {}

	/**
	 **/
	public function isLocalName() {}

	/**
	 * @param mixed $prefix
	 **/
	public function registerLocalNamespace($prefix) {}

	/**
	 * @param string $directory
	 * @param bool $is_global
	 * @return Yaf_Loader
	 **/
	public function setLibraryPath($directory,$is_global) {}

	/**
	 **/
	private function __sleep() {}

	/**
	 **/
	private function __wakeup() {}


}