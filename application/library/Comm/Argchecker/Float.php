<?php
/**
 * 参数检查
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */
class Comm_Argchecker_Float {

    /**
     * basic 
     * 
     * @param unknown $data dafa 
     * 
     * @return boolean
     */
    public static function basic($data) {
        return is_float($data);
    }

    /**
     * max 
     * 
     * @param unknown $data data   
     * @param unknown $min  min
     * 
     * @return boolean 
     */
    public static function max($data, $min) {
        if ($data <= $min) {
            return false;
        }
        return true;
    }

    /**
     * min
     * 
     * @param unknown $data data
     * @param unknown $max  max 
     * 
     * @return boolean
     */
    public static function min($data, $max) {
        if ($data >= $max) {
            return false;
        }
        return true;
    }

    /**
     * range 
     * 
     * @param unknown $data        data
     * @param unknown $left_value  left v
     * @param unknown $right_value right v
     * 
     * @return boolean
     */
    public static function range($data, $left_value, $right_value) {
        if ($left_value >= $right_value) {
            $min = $right_value;
            $max = $left_value;
        } else {
            $min = $left_value;
            $max = $right_value;
        }
        if ($data > $max || $data < $min) {
            return false;
        }
        return true;
    }

}