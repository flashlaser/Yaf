<?php
class Yaf_Route_Static implements Yaf_Router {
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	public function assemble($info,$query) {}

	/**
	 * @param string $uri
	 **/
	public function match($uri) {}

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	public function route($request) {}


}