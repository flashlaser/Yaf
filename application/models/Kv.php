<?php

/**
 * kv操作
 *
 * @package Model
 * @author  张宝军 <baojun4545@sina.com>
 */

class KvModel extends Abstract_M{

    /**
     * get 
     * 
     * @param unknown $key key
     * 
     * @return string
     */
    static public function get($key) {
        return self::getData()->get($key);
    }

    /**
     * get 
     * 
     * @param unknown $key   key
     * @param unknown $value v
     * 
     * @return boolean
     */
    static public function set($key, $value) {
        $result = self::get($key);

        if (!$result) {
            $ret = self::getData()->add($key, $value);
        }

        if ($result || empty($ret)) {
            $ret = self::getData()->modify($key, $value);
        }

        return $ret;
    }

    /**
     * 获取data对象
     *
     * @return Data_Kv
     */
    static protected function getData() {
        return new Data_Kv();
    }

}

