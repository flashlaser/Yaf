<?php
class Yaf_Router {

	protected $_routes;

	protected $_current;
	/**
	 * @param Yaf_Config_Abstract $config
	 **/
	public function addConfig($config) {}

	/**
	 * @param string $name
	 * @param Yaf_Route_Abstract $route
	 * @return Yaf_Router
	 **/
	public function addRoute($name,$route) {}

	/**
	 **/
	public function __construct() {}

	/**
	 * @return string
	 **/
	public function getCurrentRoute() {}

	/**
	 * @param string $name
	 **/
	public function getRoute($name) {}

	/**
	 **/
	public function getRoutes() {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}