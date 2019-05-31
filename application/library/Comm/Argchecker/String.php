<?php
/**
 * 参数检查
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */
class Comm_Argchecker_String {

    /**
     * 是否可打印
     *
     * This function is compatible with multi-bytes utf-8.
     *
     * @param string $string 字符串
     * 
     * @return bool
     */
    static protected function safechars($string) {
        $string  = (string)$string;
        for ($i       = 0, $i_count = strlen($string); $i < $i_count; $i++) {
            $char_value = ord($string{$i});
            if (($char_value < 32 && ($char_value !== 13 && $char_value !== 10 && $char_value !== 9)) || $char_value == 127) {
                return false;
            }
        }
        return true;
    }

    /**
     * 默认规则
     *
     * @param string $data data 
     * 
     * @return bool
     */
    static public function basic($data) {
        return self::safechars($data);
    }

    /**
     * 可打印字符
     *
     * @param string $string          字符串
     * @param bool   $utf8_compatible 可选。如果为真，则认为多字节utf-8(包含0x80~0xFF)也为可打印字符。否则，该函数只允许包含32~126的之间的字符。
     * 
     * @return bool
     */
    static public function printable($string, $utf8_compatible = true) {
        $string  = (string)$string;
        for ($i       = 0, $i_count = strlen($string); $i < $i_count; $i++) {
            $char_value = ord($string{$i});
            if ($char_value < 32 || $char_value === 127 || !$utf8_compatible && $char_value > 127) {
                return false;
            }
        }
        return true;
    }

    /**
     * max 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function max($data, $length) {
        return strlen($data) <= $length;
    }

    /**
     * min 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function min($data, $length) {
        return strlen($data) >= $length;
    }
    
    /**
     * mb max 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function mbmax($data, $length) {
        return mb_strlen($data) <= $length;
    }
    
    /**
     * mb min 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function mbmin($data, $length) {
        return mb_strlen($data) >= $length;
    }

    /**
     * width max 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function widthMax($data, $length) {
        return mb_strwidth($data, 'utf-8') <= $length;
    }

    /**
     * width min 
     * 
     * @param unknown $data   dafa  
     * @param unknown $length length 
     * 
     * @return boolean
     */
    static public function widthMin($data, $length) {
        return mb_strwidth($data, 'utf-8') >= $length;
    }

    /**
     * use regular expression to validating
     *
     * @param string $data               data 
     * @param string $regular_expression reg exp 
     * 
     * @return bool
     */
    static public function preg($data, $regular_expression) {
        return (bool)preg_match($regular_expression, $data);
    }

    /**
     * Alias of Comm_Argchecker_String::preg()
     *
     * @param string $data               data 
     * @param string $regular_expression reg exp 
     * 
     * @return string
     */
    static public function re($data, $regular_expression) {
        return self::preg($data, $regular_expression);
    }

    /**
     * chars list
     * 
     * @param unknown $data     data 
     * @param unknown $charlist char list 
     * 
     * @return boolean
     */
    static public function charslist($data, $charlist) {
        return !trim($data, $charlist);
    }

    /**
     * num 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    static public function num($data) {
        return (bool)self::charslist($data, '0123456789');
    }

    /**
     * 是否是字母加数字
     *
     * @param mixed $data value
     * 
     * @return boolean
     */
    static public function alnum($data) {
        return ctype_alnum(( string ) $data);
    }

    /**
     * alpha 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    static public function alpha($data) {
        return ctype_alpha(( string ) $data);
    }

    /**
     * lower 
     * 
     * @param unknown $data data
     * 
     * @return boolean
     */
    static public function lower($data) {
        return ctype_lower(( string ) $data);
    }

    /**
     * upper 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    static public function upper($data) {
        return ctype_upper(( string ) $data);
    }

    /**
     * hex 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    static public function hex($data) {
        return ctype_xdigit((string)$data);
    }
    
    /**
     * alnum 
     * 
     * @param unknown $data data 
     * 
     * @return boolean
     */
    static public function alnumu($data) {
        return self::charslist($data, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_');
    }
}