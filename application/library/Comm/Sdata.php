<?php
/**
 * 静态变量读写
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Sdata{

    //数据内容
    protected static $data = array();

    /**
     * 向静态数据中写入数据
     * 
     * @param string $ns    命名空间（调用时建议使用__CLASS__常量）
     * @param string $key   KEY名
     * @param mixed	 $value 值
     * 
     * @return void
     */
    static public function set($ns, $key, $value) {
        if (!Yaf_Dispatcher::getInstance()->getRequest()->isCli()) {
            self::$data[$ns][$key] = $value;
        }
    }

    /**
     * 从静态数据中读取数据
     * 
     * @param string $ns  命名空间（调用时建议使用__CLASS__常量）
     * @param string $key KEY名
     * 
     * @return	mixed
     */
    static public function get($ns, $key) {
        return isset(self::$data[$ns][$key]) ? self::$data[$ns][$key] : false;
    }
    
    /**
     * 从静态数据中销毁数据
     * 
     * @param string $ns  命名空间（调用时建议使用__CLASS__常量）
     * @param string $key KEY名
     * 
     * @return boolean
     */
    static public function delete($ns, $key) {
        if (isset(self::$data[$ns][$key])) {
            unset(self::$data[$ns][$key]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 返回所有的Data
     * @return	array
     */
    static public function showAll() {
        return self::$data;
    }

}
