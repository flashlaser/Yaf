<?php
class Yaf_Route_Map implements Yaf_Route_Interface {

	protected $_ctl_router;

	protected $_delimeter;
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	public function assemble($info,$query) {}

	/**
	 * @param string $controller_prefer
	 * @param string $delimiter
	 **/
	public function __construct($controller_prefer,$delimiter) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}