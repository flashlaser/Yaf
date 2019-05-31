<?php
final class Yaf_Dispatcher {

	protected $_router;

	protected $_view;

	protected $_request;

	protected $_plugins;

	protected $_instance;

	protected $_auto_render;

	protected $_return_response;

	protected $_instantly_flush;

	protected $_default_module;

	protected $_default_controller;

	protected $_default_action;
	/**
	 * @param bool $flag
	 * @return Yaf_Dispatcher
	 **/
	public function autoRender($flag) {}

	/**
	 * @param bool $flag
	 * @return Yaf_Dispatcher
	 **/
	public function catchException($flag) {}

	/**
	 **/
	private function __clone() {}

	/**
	 **/
	public function __construct() {}

	/**
	 * @return bool
	 **/
	public function disableView() {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return Yaf_Response_Abstract
	 **/
	public function dispatch($request) {}

	/**
	 * @return Yaf_Dispatcher
	 **/
	public function enableView() {}

	/**
	 * @param bool $flag
	 * @return Yaf_Dispatcher
	 **/
	public function flushInstantly($flag) {}

	/**
	 * @return Yaf_Application
	 **/
	public function getApplication() {}

	/**
	 * @return Yaf_Dispatcher
	 **/
	public static function getInstance() {}

	/**
	 * @return Yaf_Request_Abstract
	 **/
	public function getRequest() {}

	/**
	 * @return Yaf_Router
	 **/
	public function getRouter() {}

	/**
	 * @param string $templates_dir
	 * @param array $options
	 * @return Yaf_View_Interface
	 **/
	public function initView($templates_dir,$options) {}

	/**
	 * @param Yaf_Plugin_Abstract $plugin
	 * @return Yaf_Dispatcher
	 **/
	public function registerPlugin($plugin) {}

	/**
	 * @param bool $flag
	 * @return Yaf_Dispatcher
	 **/
	public function returnResponse($flag) {}

	/**
	 * @param string $action
	 * @return Yaf_Dispatcher
	 **/
	public function setDefaultAction($action) {}

	/**
	 * @param string $controller
	 * @return Yaf_Dispatcher
	 **/
	public function setDefaultController($controller) {}

	/**
	 * @param string $module
	 * @return Yaf_Dispatcher
	 **/
	public function setDefaultModule($module) {}

	/**
	 * @param call $callback
	 * @param int $error_types
	 * @return Yaf_Dispatcher
	 **/
	public function setErrorHandler($callback,$error_types) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return Yaf_Dispatcher
	 **/
	public function setRequest($request) {}

	/**
	 * @param Yaf_View_Interface $view
	 * @return Yaf_Dispatcher
	 **/
	public function setView($view) {}

	/**
	 **/
	private function __sleep() {}

	/**
	 * @param bool $flag
	 * @return Yaf_Dispatcher
	 **/
	public function throwException($flag) {}

	/**
	 **/
	private function __wakeup() {}


}