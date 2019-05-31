<?php
/**
 * 检查数组参数
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */
class Comm_Argchecker_Array {

    /**
     * 默认规则
     *
     * @param array $data 数据
     * 
     * @return bool
     */
    static public function basic($data) {
        return is_array($data);
    }

    /**
     * 正则匹配
     * 
     * @param array  $data               数据
     * @param string $regular_expression 表达式
     * 
     * @return	boolean
     */
    static public function preg(array $data, $regular_expression) {
        return self::_process('_preg', $data, $regular_expression);
    }

    /**
     * 判断给定数据是否是纯数字
     * 
     * @param int $data 数据
     * 
     * @return boolean
     */
    static public function int(array $data) {
        return self::_process('_int', $data);
    }

    /**
     * use regular expression to validating
     *
     * @param string $data               数据
     * @param string $key                键值
     * @param string $regular_expression 表达式
     * 
     * @return boolean
     */
    static protected function _preg($data, $key, $regular_expression) {
        Comm_Assert::true(preg_match($regular_expression, $data));
    }

    /**
     * 核心方法：判断给定数据是否是纯数字
     * 
     * @param int    $data 数据
     * @param string $key  键值
     * 
     * @return void
     */
    static protected function _int($data, $key) {
        Comm_Assert::true(is_numeric($data));
    }

    /**
     * 递归处理数据
     * @param type  $method    方法
     * @param array $data      数据
     * @param type  $user_data 自定义数据
     * 
     * @return boolean
     */
    static protected function _process($method, array $data, $user_data = null) {
        try {
            array_walk_recursive($data, array('Comm_Argchecker_Array', $method), $user_data);
            return true;
        } catch (Exception_Msg $e) {
            return false;
        }
    }

}
