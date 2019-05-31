<?php
class Yaf_Response_Abstract {

	const DEFAULT_BODY = NULL;

	protected $_header;

	protected $_body;

	protected $_sendheader;
	/**
	 * @param string $content
	 * @param string $key
	 * @return bool
	 **/
	public function appendBody($content,$key) {}

	/**
	 * @param string $key
	 * @return bool
	 **/
	public function clearBody($key) {}

	/**
	 **/
	public function clearHeaders() {}

	/**
	 **/
	private function __clone() {}

	/**
	 **/
	public function __construct() {}

	/**
	 **/
	public function __destruct() {}

	/**
	 * @param string $key
	 * @return mixed
	 **/
	public function getBody($key) {}

	/**
	 **/
	public function getHeader() {}

	/**
	 * @param string $content
	 * @param string $key
	 * @return bool
	 **/
	public function prependBody($content,$key) {}

	/**
	 **/
	public function response() {}

	/**
	 **/
	protected function setAllHeaders() {}

	/**
	 * @param string $content
	 * @param string $key
	 * @return bool
	 **/
	public function setBody($content,$key) {}

	/**
	 **/
	public function setHeader() {}

	/**
	 **/
	public function setRedirect() {}

	/**
	 **/
	private function __toString() {}


}