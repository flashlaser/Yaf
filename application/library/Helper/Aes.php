<?php

/**
 * 加密、解密
 * 调用样例：
 * $input = 'zhangbaojun';
 * $rs = Helper_Aes::encrypt($input);
 * var_dump($rs);
 * $rs = Helper_Aes::decrypt($rs);
 * var_dump($rs);
 * 
 * 参考文档：
 * * http://www.php.net/manual/en/function.openssl-encrypt.php
 * * https://blog.csdn.net/huangxiaoguo1/article/details/78043169
 * * http://hf.php.tedu.cn/news/269357.html
 *
 * @package helper
 * @author baojun <baojun@sina.com>
 */
class Helper_Aes{

    const KEY = 'cSbL9Ikrdn6Z3eAoVTtfENP+sPl2wIDYD1pytuWMk8I1EHB3r9K2DL0/kDtpY47jgryhoiCNrMM1F03jHBFLpg==';
    //const CIPHER = 'AES-128-ECB';
    const CIPHER = 'AES-128-CBC';
    
    /**
     * [encrypt aes加密]
     *
     * @param [type] $input [要加密的数据]
     * @param [type] $key [加密key]
     * @return [type] [加密后的数据]
     */
    public static function encrypt($input, $key= self::KEY)
    {
        //make safely
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $options= OPENSSL_RAW_DATA;
        $ciphertext_raw = openssl_encrypt($input, self::CIPHER, $key, $options, $iv);
        
        //make safely
        $as_binary = true;
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary);
        
        //$data = base64_encode($data);
        $ciphertext = base64_encode( $iv . $hmac . $ciphertext_raw );
        
        return $ciphertext;
    }

    /**
     * [decrypt aes解密]
     *
     * @param [type] $sStr [要解密的数据]
     * @param [type] $sKey [加密key]
     * @return [type] [解密后的数据]
     */
    public static function decrypt($input, $key = self::KEY)
    {
        //make safely
        $c = base64_decode($input);
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($c, 0, $ivlen);
        $sha2len = 32;
        $hmac = substr($c, $ivlen, $sha2len);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        
        $options= OPENSSL_RAW_DATA;
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER, $key, $options, $iv);
        //$decrypted = openssl_decrypt(base64_decode($input), self::CIPHER, $key, OPENSSL_RAW_DATA);
        $as_binary = true;
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary);
        if (hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
        {
            return $original_plaintext;
        }
        
        return $calcmac;
    }
}
