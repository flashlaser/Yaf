<?php

/**
 * HTTP操作类
 * 
 * @package helper
 * @author  baojun <baojun@sina.com>
 */

abstract class Helper_Http{

    public static $http_code = null;

    /**
	 * 初始化CURL
	 * @return resource
	 */
    protected static function curlInit() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        return $ch;
    }

    /**
	 * 取得CURL请求结果
	 * 
	 * @param resource $ch        curl资源
	 * @param string   $url       指定URL
	 * @param mixed    $data      提交的数据（数组或查询字符串，如果是传文件，必需用数组，文件名值前面加@）
	 * @param string   $cookie    COOKIE字符中
	 * @param string   $referer   指定来源
	 * @param string   $userAgent 指定用户标识（浏览器）
	 * @param int      $timeout   超时时间
	 * @param string   $host      域名HOST
	 * @param array    $headers   文件头信息
	 * 
	 * @return mixed
	 */
    protected static function curlResult($ch, $url, $data = null, $cookie = null, $referer = null, $userAgent = null, $timeout = 10, $host = null ,$headers = null) {
        $ret = strpos($url,'upload-icon.json');
        if (in_array($ret, array(27,28))) $timeout = 180;
        curl_setopt_array($ch, array(CURLOPT_URL => $url, CURLOPT_COOKIE => $cookie, CURLOPT_REFERER => $referer, CURLOPT_USERAGENT => $userAgent, CURLOPT_HTTPHEADER => array('API-RemoteIP:' . Comm_Util::getClientIp()), CURLOPT_TIMEOUT => $timeout));
        if (!empty($headers) && !empty($host)) {
            $headers[] = "Host: {$host}";
        } elseif (!empty($host)) {
            $headers = array("Host: {$host}");
        }
        $host && curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data && curl_setopt($ch, CURLOPT_POST, 1);
        $data && curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$http_code = $http_code;
        if ($result === false || $http_code != '200') { //出错
            $msg = curl_error($ch);
            $code = curl_errno($ch);

            //记录异常日志 baojun@
            throw new Exception_Http($code, $msg, array(
            		'http_code' => $http_code,
                    'url'       => $url,
                    'data'      => $data,
                    'result'    => $result,
            ));

            self::error($msg, $code);
            trigger_error("[{$code}] {$msg}", E_USER_WARNING);
        } else {
            self::error('', 0);
        }
        curl_close($ch);
        return $result;
    }

    /**
	 * 记录或读取错误信息
	 * 
	 * @param string $msg  错误消息
	 * @param string $code 错误码
	 * 
	 * @return array
	 */
    public static function error($msg = null, $code = null) {
        static $error_msg = '', $error_code = 0;
        if ($msg !== null && $code !== null) {
            $error_msg = $msg;
            $error_code = $code;
        } else {
            return array('errmsg' => $error_msg, 'errno' => $error_code);
        }
    }

    /**
	 * 通过GET取得一条数据
	 * 
	 * @param string $url       指定URL
	 * @param string $cookie    COOKIE字符中
	 * @param string $referer   指定来源
	 * @param string $userAgent 指定用户标识（浏览器）
	 * @param int    $time_out  超时时间
	 * @param string $host      域名HOST
	 * @param array  $headers   文件头信息
	 * 
	 * @return mixed
	 */
    static public function get($url, $cookie = null, $referer = null, $userAgent = null, $time_out = 10, $host = null ,$headers = null) {
        $ch = self::curlInit();
        return self::curlResult($ch, $url, null, $cookie, $referer, $userAgent, $time_out, $host ,$headers);
    }

    /**
	 * 通过POST提交一条数据(二进制)
	 * 
	 * @param string $url       指定URL
	 * @param mixed  $data      提交的数据（数组或查询字符串，如果是传文件，必需用数组，文件名值前面加@）
	 * @param string $cookie    COOKIE字符中
	 * @param string $referer   指定来源
	 * @param string $userAgent 指定用户标识（浏览器）
	 * @param int    $time_out  超时时间
	 * @param string $host      域名HOST
	 * @param array  $headers   文件头信息
	 * 
	 * @return mixed
	 */
    static public function postBin($url, $data, $cookie = null, $referer = null, $userAgent = null, $time_out = 10, $host = null ,$headers = null) {
        $ch = self::curlInit();
        curl_setopt($ch, CURLOPT_POST, 1);
        return self::curlResult($ch, $url, $data, $cookie, $referer, $userAgent, $time_out, $host ,$headers);
    }

    /**
	 * 通过POST提交一条数据(urlencode)
	 * 
	 * @param string $url       指定URL
	 * @param mixed  $data      提交的数据（数组或查询字符串）
	 * @param string $cookie    COOKIE字符中
	 * @param string $referer   指定来源
	 * @param string $userAgent 指定用户标识（浏览器）
	 * @param int    $time_out  超时时间
	 * @param string $host      域名HOST
	 * @param array  $headers   文件头信息
	 * 
	 * @return mixed
	 */
    static public function post($url, $data, $cookie = null, $referer = null, $userAgent = null, $time_out = 10, $host = null ,$headers = null) {
        is_array($data) && $data = http_build_query($data);
        return self::postBin($url, $data, $cookie, $referer, $userAgent, $time_out, $host ,$headers);
    }

    /**
	 * 通过DELETE提交一条数据
	 * 
	 * @param string $url       指定URL
	 * @param mixed  $data      提交的数据（数组或查询字符串）
	 * @param string $cookie    COOKIE字符中
	 * @param string $referer   指定来源
	 * @param string $userAgent 指定用户标识（浏览器）
	 * @param int    $time_out  超时设置
	 * 
	 * @return mixed
	 */
    static public function delete($url, $data, $cookie = null, $referer = null, $userAgent = null, $time_out = 10) {
        $ch = self::curlInit();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        is_array($data) && $data = http_build_query($data);
        return self::curlResult($ch, $url, $data, $cookie, $referer, $userAgent, $time_out);
    }

    /**
     * https
     *
     * @return string
     */
    static public function judgHttps(){
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return 'https';
        } else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return 'https';
        } else if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return 'https';
        }
        return 'http';

    }
}
