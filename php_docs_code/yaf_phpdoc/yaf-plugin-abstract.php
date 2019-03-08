<?php
class Yaf_Plugin_Abstract {
	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function dispatchLoopShutdown($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function dispatchLoopStartup($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function postDispatch($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function preDispatch($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function preResponse($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function routerShutdown($request,$response) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @param Yaf_Response_Abstract $response
	 **/
	public function routerStartup($request,$response) {}


}