<?php

/**
 * 加密、解密
 *
 * @package helper
 * @author  baojun <baojun@sina.com>
 */
class Helper_Mcrypt {

    /**
     * 加密
     *
     * @param string $str 要加密的明文字符串
     * @param string $key 密钥
     *
     * @return string
     */
    static public function encode($str, $key) {
        $cipher = MCRYPT_DES; //密码类型
        $modes = MCRYPT_MODE_ECB; //密码模式
        $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher,$modes),MCRYPT_RAND);//初始化向量
        $result = mcrypt_encrypt($cipher,$key,$str,$modes,$iv); //加密函数
        /*
        $size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
        $result = mcrypt_ecb(MCRYPT_BLOWFISH, $key, $str, MCRYPT_DECRYPT, $iv);
        $result = bin2hex($result);*/
        
        $result = bin2hex($result);
        //$result = hexdec($result);
        
        return $result;
    }

    /**
     * 解密
     *
     * @param string $str 要解密的密文字符串
     * @param string $key 密钥
     *
     * @return string
     */
    static public function decode($str, $key) {
        $str = pack('H*', $str);
        $cipher = MCRYPT_DES; //密码类型
        $modes = MCRYPT_MODE_ECB; //密码模式
        $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher,$modes),MCRYPT_RAND);//初始化向量
        $result = mcrypt_decrypt($cipher,$key,$str,$modes,$iv); //解密函数
        /*
        $str = pack('H*', $str);
        $size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
        $result = mcrypt_ecb(MCRYPT_BLOWFISH, $key, $str, MCRYPT_ENCRYPT, $iv);
        $result = rtrim($result, "\000");
        */
        $result = rtrim($result, "\000");
        
        return $result;
    }
}
