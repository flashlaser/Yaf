<?php
class Yaf_Route_Regex extends Yaf_Route_Interface implements Yaf_Route_Interface {

	protected $_route;

	protected $_default;

	protected $_maps;

	protected $_verify;
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	public function assemble($info,$query) {}

	/**
	 * @param string $match
	 * @param array $route
	 * @param array $map
	 * @param array $verify
	 **/
	public function __construct($match,$route,$map,$verify) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}