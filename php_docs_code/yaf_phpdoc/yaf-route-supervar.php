<?php
class Yaf_Route_Supervar implements Yaf_Route_Interface {

	protected $_var_name;
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	public function assemble($info,$query) {}

	/**
	 * @param string $supervar_name
	 **/
	public function __construct($supervar_name) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}