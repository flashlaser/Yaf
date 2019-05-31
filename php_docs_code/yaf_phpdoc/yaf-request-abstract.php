<?php
class Yaf_Request_Abstract {

	const SCHEME_HTTP = NULL;

	const SCHEME_HTTPS = NULL;

	public $module;

	public $controller;

	public $action;

	public $method;

	protected $params;

	protected $language;

	protected $_exception;

	protected $_base_uri;

	protected $uri;

	protected $dispatched;

	protected $routed;
	/**
	 **/
	public function getActionName() {}

	/**
	 **/
	public function getBaseUri() {}

	/**
	 **/
	public function getControllerName() {}

	/**
	 * @param string $name
	 * @param string $default
	 **/
	public function getEnv($name,$default) {}

	/**
	 **/
	public function getException() {}

	/**
	 **/
	public function getLanguage() {}

	/**
	 **/
	public function getMethod() {}

	/**
	 **/
	public function getModuleName() {}

	/**
	 * @param string $name
	 * @param string $default
	 **/
	public function getParam($name,$default) {}

	/**
	 **/
	public function getParams() {}

	/**
	 **/
	public function getRequestUri() {}

	/**
	 * @param string $name
	 * @param string $default
	 **/
	public function getServer($name,$default) {}

	/**
	 **/
	public function isCli() {}

	/**
	 **/
	public function isDispatched() {}

	/**
	 **/
	public function isGet() {}

	/**
	 **/
	public function isHead() {}

	/**
	 **/
	public function isOptions() {}

	/**
	 **/
	public function isPost() {}

	/**
	 **/
	public function isPut() {}

	/**
	 **/
	public function isRouted() {}

	/**
	 **/
	public function isXmlHttpRequest() {}

	/**
	 * @param string $action
	 **/
	public function setActionName($action) {}

	/**
	 * @param string $uir
	 **/
	public function setBaseUri($uir) {}

	/**
	 * @param string $controller
	 **/
	public function setControllerName($controller) {}

	/**
	 **/
	public function setDispatched() {}

	/**
	 * @param string $module
	 **/
	public function setModuleName($module) {}

	/**
	 * @param string $name
	 * @param string $value
	 **/
	public function setParam($name,$value) {}

	/**
	 * @param string $uir
	 **/
	public function setRequestUri($uir) {}

	/**
	 * @param string $flag
	 **/
	public function setRouted($flag) {}


}