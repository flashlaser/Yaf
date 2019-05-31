<?php
abstract class Yaf_Controller_Abstract {

	public $actions;

	protected $_module;

	protected $_name;

	protected $_request;

	protected $_response;

	protected $_invoke_args;

	protected $_view;
	/**
	 **/
	final private function __clone() {}

	/**
	 **/
	final private function __construct() {}

	/**
	 * @param string $tpl
	 * @param array $parameters
	 * @return bool
	 **/
	protected function display($tpl,$parameters) {}

	/**
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array $paramters
	 **/
	public function forward($module,$controller,$action,$paramters) {}

	/**
	 * @param string $name
	 **/
	public function getInvokeArg($name) {}

	/**
	 **/
	public function getInvokeArgs() {}

	/**
	 * @return string
	 **/
	public function getModuleName() {}

	/**
	 * @return Yaf_Request_Abstract
	 **/
	public function getRequest() {}

	/**
	 * @return Yaf_Response_Abstract
	 **/
	public function getResponse() {}

	/**
	 * @return Yaf_View_Interface
	 **/
	public function getView() {}

	/**
	 **/
	public function getViewpath() {}

	/**
	 **/
	public function init() {}

	/**
	 * @param array $options
	 **/
	public function initView($options) {}

	/**
	 * @param string $url
	 **/
	public function redirect($url) {}

	/**
	 * @param string $tpl
	 * @param array $parameters
	 * @return string
	 **/
	protected function render($tpl,$parameters) {}

	/**
	 * @param string $view_directory
	 **/
	public function setViewpath($view_directory) {}


}