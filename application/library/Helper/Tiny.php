<?php

/**
 * 加密、解密
 *
 * @package helper
 * @author  baojun <baojun@sina.com>
 */

class Helper_Tiny{
    
    /**
     * 加密密钥
     * 
     * @var string
     */
    const KEY = 0xF42FBCA9;
    
    /**
     * encode
     * @param unknown $buf buf
     * @param string  $key key
     *
     * @return unknown
     */
    public static function encode($buf, $key = null) {
        $len = strlen ( $buf );
        $len_rnd = 4;
        if (function_exists('random_bytes')) {
            $out = random_bytes($len_rnd);
        } else if (function_exists('mcrypt_create_iv')) {
            $out = mcrypt_create_iv($len_rnd, MCRYPT_DEV_URANDOM);
        } else if (function_exists('openssl_random_pseudo_bytes')) {
            $out = openssl_random_pseudo_bytes($len_rnd);
        }
        
        $r = unpack ( 'N', $out );
        $r = $r [1];
        $r_key = !empty($key) ? $key : self::KEY;
        $r = $r ^ $r_key;
        $out .= pack ( 'N', $r );
        $alignlen = (( int ) (($len + 3) / 4)) * 4 - $len;
        $r = $r ^ $alignlen;
        $out .= pack ( 'N', $r );
        for ($i = 0; $i < $len; $i += 4) {
            // 确保最后数据能unpack
            $tmp = unpack ( 'N', substr ( $buf, $i, min ( $len - $i, 4 ) ) . '0000' );
            $r = $tmp [1] ^ $r;
            $out .= pack ( 'N', $r );
        }
        return $out;
    }
}

