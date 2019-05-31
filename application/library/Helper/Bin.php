<?php
/**
 * 二进制处理类
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

class Helper_Bin{

    /**
     * 判断给定的位在数字中是否存在
     * 
     * @param int $byte	 二进制位的序号(从右到左 0-63)
     * @param int $value 值
     * 
     * @return int 正确返回$byte对应的值
     */
    static public function inValue($byte, $value) {
        Comm_Argchecker::int($byte, 'range,0,63', Comm_Argchecker::NEED, Comm_Argchecker::RIGHT);
        $byte = pow(2, $byte);
        return $byte & (int)$value;
    }

    /**
     * 拼接二进制位数
     * 
     * @param array $bytes 传入的位置集
     * 
     * @return	int
     */
    static public function concatValue(array $bytes) {
        $result = 0;
        foreach ($bytes as $byte) {
            $result = pow(2, $byte) | $result;
        }
        return $result;
    }

    /**
     * 拼接二进制位数并过滤值
     * 
     * @param array $bytes       传入的位置集
     * @param array $allow_bytes 允许的位置集
     * 
     * @return	int
     */
    static public function concatFilterValue(array $bytes, array $allow_bytes) {
        $bytes = array_intersect($allow_bytes, $bytes);
        return self::concatValue($bytes);
    }

    /**
     * 修改二进制位的数值
     * 
     * @param array  $bytes       需要置为1的位置集
     * @param array  $allow_bytes 允许的二进制位
     * @param string $value       现有的二进制位
     * 
     * @return	int
     */
    static public function modify(array $bytes, array $allow_bytes, $value) {
        $result = (int)$value;
        foreach ($allow_bytes as $byte) {
            if (in_array($byte, $bytes)) { //有，追加
                $result = $result | pow(2, $byte);
            } else { //没有，删除
                $result = $result & ~pow(2, $byte);
            }
        }
        return $result;
    }

    /**
     * 修改二进制位值
     *
     * @param int   $value 被修改的数据
     * @param array $list  array(二进制位=>对应的值0或1),二进制位从0开始编号，从右往左
     *
     * @return int
     */
    static public function modifyBit($value, $list) {
        $result = (int)$value;
        foreach ($list as $bit_num => $bit_val) {
            if ($bit_val) {// 把第$bit_num位的值改1
                $result = $result | pow(2, $bit_num);
            } else { //把第$bit_num位的值改0
                $result = $result & ~pow(2, $bit_num);
            }
        }

        return $result;
    }

    /**
     * 修改整数的某个位
     * 
     * @param int    $num      num 
     * @param string $location location 
     * @param string $value    value
     * 
     * @return int
     */
    static function setByte($num, $location, $value){
        if ($value==0) {
            $num = $num & ~(1<<$location);
        } else {
            $num = $num | (1<<$location);
        }
        return $num;
    }

}
