<?php
class Yaf_Request_Http extends Yaf_Request_Abstract {
	/**
	 **/
	private function __clone() {}

	/**
	 **/
	function __construct() {}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 **/
	public function get($name,$default) {}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 **/
	public function getCookie($name,$default) {}

	/**
	 **/
	public function getFiles() {}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 **/
	public function getPost($name,$default) {}

	/**
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 **/
	public function getQuery($name,$default) {}

	/**
	 **/
	public function getRequest() {}

	/**
	 * @return bool
	 **/
	public function isXmlHttpRequest() {}


}