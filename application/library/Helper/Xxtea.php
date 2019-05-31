<?php

/**
 * Channel
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

class Helper_Xxtea{
    
    /**
     * encrypt 
     * 
     * @param unknown $s s
     * @param unknown $key key
     * 
     * @return string
     */
    static public function encrypt($s, $key) {
        return str_replace(array ('+','/','=' ), array ('~','-','_' ), base64_encode(self::xxteaEncrypt($s, $key)));
        // return str_replace(array('+','/','='), array('-','_','.'), base64_encode(self::xxtea_encrypt($s, $key)));
    }
    
    /**
     * decrypt 
     * 
     * @param unknown $e   e
     * @param unknown $key key
     * 
     * @return string|boolean
     */
    static public function decrypt($e, $key) {
        // $c = str_replace(array('-','_','.'), array('+','/','='), $e);
        $c = str_replace(array ('~','*','-','_' ), array ('+','+','/','=' ), $e);
        return self::xxteaDecrypt(base64_decode($c), $key);
    }
    
    /**
     * long 2 str
     * 
     * @param unknown $v v
     * @param unknown $w w
     * 
     * @return boolean
     */
    protected static function long2str($v, $w) {
        $len = count($v);
        $n = ($len - 1) << 2;
        if ($w) {
            $m = $v[$len - 1];
            if (($m < $n - 3) || ($m > $n))
                return false;
            $n = $m;
        }
        $s = array ();
        for ($i = 0; $i < $len; $i ++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $n);
        } else {
            return join('', $s);
        }
    }
    
    /**
     * str 2 long
     * 
     * @param unknown $s string
     * @param unknown $w w
     * 
     * @return void|unknown
     */
    protected static function str2long($s, $w) {
        // xxgg
        if (! is_string($s)) {
            return;
        }
        $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if (empty($v)) {
            return $v;
        }
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }
    
    /**
     * int 32
     * 
     * @param unknown $n int
     * 
     * @return number
     */
    protected static function int32($n) {
        while ( $n >= 2147483648 )
            $n -= 4294967296;
        while ( $n <= - 2147483649 )
            $n += 4294967296;
        return ( int ) $n;
    }
    
    /**
     * encrypt
     * 
     * @param unknown $str string
     * @param unknown $key key
     * 
     * @return string|boolean
     */
    static public function xxteaEncrypt($str, $key) {
        // xxgg
        if (! is_string($str) || is_object($str)) {
            return "";
        }
        if ($str == "") {
            return "";
        }
        $v = self::str2long($str, true);
        $k = self::str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i ++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        
        while ( 0 < $q -- ) {
            $sum = self::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p ++) {
                $y = $v[$p + 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = self::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = self::int32($v[$n] + $mx);
        }
        return self::long2str($v, false);
    }
    
    /**
     * decrypt 
     * 
     * @param unknown $str string
     * @param unknown $key key
     * 
     * @return string|boolean
     */
    static public function xxteaDecrypt($str, $key) {
        // xxgg
        if (! is_string($str) || is_object($str)) {
            return "";
        }
        if ($str == "") {
            return "";
        }
        $v = self::str2long($str, false);
        $k = self::str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i ++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;
        
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = self::int32($q * $delta);
        
        while ( $sum != 0 ) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p --) {
                $z = $v[$p - 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = self::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = self::int32($v[0] - $mx);
            $sum = self::int32($sum - $delta);
        }
        return self::long2str($v, true);
    }
}
