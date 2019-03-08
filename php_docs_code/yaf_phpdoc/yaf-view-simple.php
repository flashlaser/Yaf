<?php
class Yaf_View_Simple implements Yaf_View_Interface {

	protected $_tpl_vars;

	protected $_tpl_dir;
	/**
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 **/
	public function assign($name,$value) {}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 **/
	public function assignRef($name,$value) {}

	/**
	 * @param string $name
	 * @return bool
	 **/
	public function clear($name) {}

	/**
	 * @param string $tempalte_dir
	 * @param array $options
	 **/
	final public function __construct($tempalte_dir,$options) {}

	/**
	 * @param string $tpl
	 * @param array $tpl_vars
	 * @return bool
	 **/
	public function display($tpl,$tpl_vars) {}

	/**
	 * @param string $tpl_content
	 * @param array $tpl_vars
	 * @return string
	 **/
	public function eval($tpl_content,$tpl_vars) {}

	/**
	 * @param string $name
	 **/
	public function __get($name) {}

	/**
	 * @return string
	 **/
	public function getScriptPath() {}

	/**
	 * @param string $name
	 **/
	public function __isset($name) {}

	/**
	 * @param string $tpl
	 * @param array $tpl_vars
	 * @return string
	 **/
	public function render($tpl,$tpl_vars) {}

	/**
	 * @param string $name
	 * @param mixed $value
	 **/
	public function __set($name,$value) {}

	/**
	 * @param string $template_dir
	 * @return bool
	 **/
	public function setScriptPath($template_dir) {}


}