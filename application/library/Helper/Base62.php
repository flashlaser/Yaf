<?php

/**
 * BASE62 解析类，专门针对长mid与62进制互转，不适用其他操作
 *
 * @package Helper
 * @author  zhangbaojun <zhangbaojun@yixia.com>
 */
class Helper_Base62 {

    protected static $string          = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected static $encodeBlockSize = 7;
    protected static $decodeBlockSize = 4;

    /**
     * 禁止实例化
     */
    protected function __construct() {
        ;
    }

    /**
     * 将mid转换成62进制字符串
     *
     * @param string $mid /
     *
     * @return	string
     */
    static public function encode($mid) {
        $str      = "";
        $midlen   = strlen($mid);
        $segments = ceil($midlen / self::$encodeBlockSize);
        $start    = $midlen;
        for ($i = 1; $i < $segments; $i += 1) {
            $start -= self::$encodeBlockSize;
            $seg    = substr($mid, $start, self::$encodeBlockSize);
            $seg    = self::_encodeSegment($seg);
            $str    = str_pad($seg, self::$decodeBlockSize, '0', STR_PAD_LEFT) . $str;
        }
        $str = self::_encodeSegment(substr($mid, 0, $start)) . $str;
        return $str;
    }

    /**
     * 将62进制字符串转成10进制mid
     *
     * @param type $str     /
     * @param type $compat  /
     * @param type $for_mid /
     *
     * @return string
     */
    static public function decode($str, $compat = false, $for_mid = true) {
        $mid      = "";
        $strlen   = strlen($str);
        $segments = ceil($strlen / self::$decodeBlockSize);
        $start    = $strlen;
        for ($i        = 1; $i < $segments; $i += 1) {
            $start -= self::$decodeBlockSize;
            $seg = substr($str, $start, self::$decodeBlockSize);
            $seg = self::_decodeSegment($seg);
            $mid = str_pad($seg, self::$encodeBlockSize, '0', STR_PAD_LEFT) . $mid;
        }
        $mid = self::_decodeSegment(substr($str, 0, $start)) . $mid;
        //判断v3、v4版本mid
        if ($for_mid) {
            $midlen = strlen($mid);
            $first  = substr($mid, 0, 1);
            if ($midlen == 16 && ($first == '3' || $first == '4')) {
                return $mid;
            }
            if ($midlen == 19 && $first == '5') {
                return $mid;
            }
        }
        //end
        if ($compat && !in_array(substr($mid, 0, 3), array('109', '110', '201', '211', '221', '231', '241'))) {
            $mid = self::_decodeSegment(substr($str, 0, 4)) . self::_decodeSegment(substr($str, 4));
        }
        if ($for_mid) {
            if (substr($mid, 0, 1) == '1' && substr($mid, 7, 1) == '0') {
                $mid = substr($mid, 0, 7) . substr($mid, 8);
            }
        }
        return $mid;
    }

    /**
     * 将10进制转换成62进制
     *
     * @param string $str 10进制字符串
     *
     * @return	string
     */
    static private function _encodeSegment($str) {
        $out = '';
        while ($str > 0) {
            $idx = $str % 62;
            $out = substr(self::$string, $idx, 1) . $out;
            $str = floor($str / 62);
        }
        return $out;
    }

    /**
     * 将62进制转换成10进制
     *
     * @param string $str 62进制字符串
     * 
     * @return	string
     */
    static private function _decodeSegment($str) {
        $out  = 0;
        $base = 1;
        for ($t    = strlen($str) - 1; $t >= 0; $t -= 1) {
            $out = $out + $base * strpos(self::$string, substr($str, $t, 1));
            $base *= 62;
        }
        return $out . "";
    }

}
