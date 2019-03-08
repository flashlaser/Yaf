<?php
/**
 * SSO管理
 *
 * @package base
 * @author  baojun <baojun4545@sina.com>
 */
//require_once 'SSOWeiboCookie.php';
//require_once 'SSOWeiboClient.php';

//SSO配置
/*class SSOConfig {

    const SERVICE = 'weibo';
    const ENTRY = 'miniblog';
    const PIN = '4d20acf932c985c56337c887d820ff1c';
    const COOKIE_DOMAIN = 'weibo.com'; //domain of cookie
    const USE_SERVICE_TICKET = false;
    const USE_RSA_SIGN = false; //开启非对称加密识别

}*/

class Comm_Sso {

    /**
     * 获取用户数据
     * 
     * @param bool $noRedirect no redirect 
     * 
     * return array()
     */
    static public function user($noRedirect = true) {
        static $user = null;

        if ($user === null) {
            $user = array();
            if ($_COOKIE) {
                //验证sina cookie登录状态
                $ssoclient = new Comm_Sso_Client();
                $ssoclient->setConfig('use_vf', true);
                if ($ssoclient->isLogined($noRedirect)) {
                    $user = $ssoclient->getUserInfo();
                    $user['uid'] = $user['uniqueid'];
//已下线                     $user['username'] = mb_convert_encoding($arr_user_info['userid'], 'UTF-8', 'GBK');
//已下线                     $user['nick'] = mb_convert_encoding($arr_user_info['nick'], 'UTF-8', 'GBK');
                }
            }
        }
        return $user;
    }

    /**
     * 判断SSO是否登录
     * 
     * @param bool $noRedirect no redirect 
     * 
     * @return boolean
     */
    static public function isLogined($noRedirect = false) {
        static $result = null;
        if ($result === null) {
            if ($_COOKIE) {
                $ssoclient = new Comm_Sso_Client();
                $ssoclient->setConfig('use_vf', true);
                $result = $ssoclient->isLogined($noRedirect);
            } else {
                $result = false;
            }
        }
        return $result;
    }

}