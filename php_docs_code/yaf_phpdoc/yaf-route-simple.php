<?php
class Yaf_Route_Simple implements Yaf_Route_Interface {

	protected $controller;

	protected $module;

	protected $action;
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	public function assemble($info,$query) {}

	/**
	 * @param string $module_name
	 * @param string $controller_name
	 * @param string $action_name
	 **/
	public function __construct($module_name,$controller_name,$action_name) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}