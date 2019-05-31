<?php
abstract class Yaf_Route_Interface {
	/**
	 * @param array $info
	 * @param array $query
	 * @return string
	 **/
	abstract public function assemble($info,$query) ;

	/**
	 * @param Yaf_Request_Abstract $request
	 * @return bool
	 **/
	abstract public function route($request) ;


}