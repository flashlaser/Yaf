<?php
/**
 * ip处理类
 *
 * @package Helper
 * @author  baojun <zhangbaojun@yixia.com>
 */

class Helper_Ip{
	
    /**
     * IP地址转为长地址
     * 
     * @param string $ip IP地址
     * 
     * @return string
     */
    public static function ip2long($ip){
    	$ip_chunks = explode('.', $ip, 4);
    	foreach ($ip_chunks as $i => $v) {
    		$ip_chunks[$i] = abs(intval($v));
    	}
    	return sprintf('%u', ip2long(implode('.', $ip_chunks)));
    }
    
    /**
     * 判断是否私有IP
     * 
     * @param string $ip IP地址
     * 
     * @return boolean
     */
    public static function isPrivateIp($ip){
    	$ip_value = self::ip2long($ip);
    	return ($ip_value & 0xFF000000) === 0x0A000000 //10.0.0.0-10.255.255.255
    	|| ($ip_value & 0xFFF00000) === 0xAC100000 //172.16.0.0-172.31.255.255
    	|| ($ip_value & 0xFFFF0000) === 0xC0A80000 //192.168.0.0-192.168.255.255
    	;
    }
    
    /**
     * 获取server变量
     * 
     * @param string $name 服务变量名称
     * 
     * @return Ambigous <NULL, unknown>
     */
    public static function getServer($name){
    	return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
    }

    /**
     * 获取客户端的IP
     *
     * @param bool $to_long 是否用long ip格式
     * 
     * @return mixed
     */
    public static function getClientIP($to_long = false){
        if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
            $clientip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ($_SERVER['HTTP_CLIENT_IP']) {
            $clientip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $clientip = $_SERVER['REMOTE_ADDR'];
        }
        $clientip = substr(str_replace("\n", '', $clientip), 0, 16);
        
        return $to_long ? self::ip2long($clientip) : $clientip;
    }
}
