<?php
final class Yaf_Application {

	protected $config;

	protected $dispatcher;

	protected $_app;

	protected $_modules;

	protected $_running;

	protected $_environ;
	/**
	 **/
	public static function app() {}

	/**
	 * @param Yaf_Bootstrap_Abstract $bootstrap
	 **/
	public function bootstrap($bootstrap) {}

	/**
	 * @return Yaf_Application
	 **/
	public function clearLastError() {}

	/**
	 **/
	private function __clone() {}

	/**
	 * @param mixed $config
	 * @param string $envrion
	 **/
	public function __construct($config,$envrion) {}

	/**
	 **/
	public function __destruct() {}

	/**
	 **/
	public function environ() {}

	/**
	 * @param callable $entry
	 * @param string $...
	 **/
	public function execute($entry) {}

	/**
	 * @return Yaf_Application
	 **/
	public function getAppDirectory() {}

	/**
	 * @return Yaf_Config_Abstract
	 **/
	public function getConfig() {}

	/**
	 * @return Yaf_Dispatcher
	 **/
	public function getDispatcher() {}

	/**
	 * @return string
	 **/
	public function getLastErrorMsg() {}

	/**
	 * @return int
	 **/
	public function getLastErrorNo() {}

	/**
	 * @return array
	 **/
	public function getModules() {}

	/**
	 **/
	public function run() {}

	/**
	 * @param string $directory
	 * @return Yaf_Application
	 **/
	public function setAppDirectory($directory) {}

	/**
	 **/
	private function __sleep() {}

	/**
	 **/
	private function __wakeup() {}


}