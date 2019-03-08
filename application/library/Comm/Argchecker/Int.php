<?php
/**
 * 参数检查
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Argchecker_Int{

    /**
     * basic 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    public static function basic($data) {
        return (bool)preg_match('/^-?[\d]+$/iD', $data, $match);
    }

    /**
     * min 
     * 
     * @param unknown $data data 
     * @param unknown $min  min
     * 
     * @return boolean
     */
    public static function min($data, $min) {
        if ($data < $min) {
            return false;
        }

        return true;
    }

    /**
     * max 
     * 
     * @param unknown $data data
     * @param unknown $max  max 
     * 
     * @return boolean
     */
    public static function max($data, $max) {
        if ($data > $max) {
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

    /**
     * len 
     * 
     * @param unknown $data    data
     * @param number  $min_len min len
     * @param unknown $max_len max len
     * 
     * @throws Exception_Arg
     * @return boolean
     */
    public static function len($data, $min_len = 1, $max_len = null) {
        if (!$min_len) {
            throw new Exception_Arg('param_is_uncorrect');
        }

        if ($min_len && $max_len) {
            if ($min_len > $max_len) {
                $min_len = $min_len + $max_len;
                $max_len = $min_len - $max_len;
                $min_len = $min_len - $max_len;
            }
            if ($min_len == $max_len) {
                $match_string = '/\d{' . $min_len . '}/';
            } else {
                $match_string = '/\d{' . $min_len . ',' . $max_len . '}/';
            }
        } else {
            $match_string = '/\d{' . $min_len . ',}/';
        }

        return (bool)preg_match($match_string, $data, $match);
    }

}