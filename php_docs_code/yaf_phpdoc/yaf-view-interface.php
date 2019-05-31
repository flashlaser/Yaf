<?php
abstract class Yaf_View_Interface {
	/**
	 * @param string $name
	 * @param string $value
	 * @return bool
	 **/
	abstract public function assign($name,$value=null) ;

	/**
	 * @param string $tpl
	 * @param array $tpl_vars
	 * @return bool
	 **/
	abstract public function display($tpl,$tpl_vars) ;

	/**
	 **/
	abstract public function getScriptPath() ;

	/**
	 * @param string $tpl
	 * @param array $tpl_vars
	 * @return string
	 **/
	abstract public function render($tpl,$tpl_vars) ;

	/**
	 * @param string $template_dir
	 **/
	abstract public function setScriptPath($template_dir) ;


}