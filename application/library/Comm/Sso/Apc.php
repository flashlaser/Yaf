<?php
/**
 * APC本地缓存操作类
 * 
 * @copyright	(c) 2010, 新浪网 MiniBlog All rights reserved.
 * @author		王兆源 <zhaoyuan@staff.sina.com.cn>
 * @version		1.0 - 2010-06-01
 * @package		Lib
 */

class Comm_Sso_Apc {
	private static $_instance = false;

	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * 添加信息至APC
	 * @param	string	$key	键名
	 * @param	string	$val	键值
	 * @param	integer	$expire	生命周期
	 * @return	boolean
	 */
	public function add($key, $val, $expire=0) {
		if(!function_exists('apc_store') or !function_exists('apc_fetch')) return false;
		$result = apc_fetch($key);
		if($result!==false) {
			if($result['liveTime']==0 or $result['liveTime']>$this->_getMtime()) return false;
		}
		$val = array(
			'liveTime'	=> $this->_getLiveTime($expire),
			'value'		=> $val,
		);
		$bRet = apc_store($key, $val);
		return $bRet;
	}
	
	/**
	 * 添加并覆盖信息至APC
	 * @param	string	$key	键名
	 * @param	string	$val	键值
	 * @param	integer	$expire	生命周期
	 * @return	boolean
	 */
	public function set($key, $val, $expire=0) {
		if(!function_exists('apc_store')) return false;
		$val = array(
			'liveTime'	=> $this->_getLiveTime($expire),
			'value'		=> $val,
		);
		$bRet = apc_store($key, $val);
		return $bRet;
	}
	
	/**
	 * 从APC获取一条信息
	 * @param	string	$key	键名
	 * @return	string
	 */
	public function get($key) {
		if(!function_exists('apc_fetch')) return false;
		$result = apc_fetch($key);
		if($result===false) return false;
		if($result['liveTime']>0 && $result['liveTime']<=$this->_getMtime()) return false;
		return $result['value'];
	}
	
	/**
	 * 从APC删除一条信息
	 * @param	string	$key	键名
	 * @return	boolean
	 */
	public function delete($key) {
		if(!function_exists('apc_delete')) return false;
		$bRet = apc_delete($key);
		return $bRet;
	}
	
	/**
	 * 根据expire参数运算到期时间
	 * @param	integer	$expire	生命周期
	 * @return	boolean
	 */
	private function _getLiveTime($expire) {
		$liveTime = $expire<=0 ? 0 : $this->_getMtime()+$expire;
		return $liveTime;
	}
	
	/**
	 * 获取当前毫秒级时间
	 * @return	float
	 */
	private function _getMtime() {
		$mtime = explode(' ', microtime());
		$time = $mtime[0] + $mtime[1];
		return $time;
	}
	
}

