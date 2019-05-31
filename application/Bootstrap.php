<?php

/**
 * 引导文件
 * 可以通过在配置文件中修改application.bootstrap来变更Bootstrap类的位置.
 * 
 * @package   Application
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia all rights reserved
 */

class Bootstrap extends Yaf_Bootstrap_Abstract{
    
    /**
     * 初始配置
     *
     * @param Yaf_Dispatcher $dispatcher 路由对象
     * 
     * @throws Yaf_Exception_DispatchFailed 异常
     * @return void
     */
    public function _initConfig(Yaf_Dispatcher $dispatcher) {
        
        // 默认MB编码为utf8
        mb_internal_encoding ( 'utf-8' );
        
        // 配置本地类库前缀
        $loader = Yaf_Loader::getInstance ();
        $arr_register = array (
                'Abstract',
                'Apilib',
                'Comm',
                'Data',
                'Dc',
                'Exception',
                'Helper',
                'PEAR',
                'Api',
        );
        $loader->registerLocalNamespace ( $arr_register );
        
        // CLI模式
        if ($dispatcher->getRequest ()->isCli ()) {
            $ip = self::serverIp();
            $conf          = new Yaf_Config_Simple(include APP_PATH . '/conf/allowip.php');
            $test_ip_list = $conf['test'];
            $is_test_env = false;
            foreach ($test_ip_list as $v) {
                $v = str_replace(array('.', '+'), array('\.', '.+'), $v);
                $v = '/' . $v . '/';
                if (preg_match($v, $ip)) {
                    $is_test_env = true;
                    break;
                }
            }
            
            // 配置SRV_CONFIG
            if ($is_test_env) {
                $ini_file = '/usr/local/mpsrv/conf/fpm.d/i.miaopai.com.inc';
            } else {
                $ini_file = '/usr/local/mpsrv/conf/fpm.d/i.miaopai.com.conf';
            }
            if (is_file ( $ini_file )) {
                $ini_info = parse_ini_file ( $ini_file );
                isset($ini_info['env']) && $_SERVER = array_merge ( $_SERVER, $ini_info['env'] );
            } else {
                $ini_file = APP_PATH . '/system/SRV_CONFIG';
                if (! is_file ( $ini_file )) {
                    throw new Yaf_Exception_DispatchFailed ( 'Can\'t find the SRV_CONFIG.', 500 );
                }
                $_SERVER = array_merge ( $_SERVER, parse_ini_file ( $ini_file ) );
            }
            // 配置前缀模式（HTTP请在APACHE中使用php_admin_value改）
            ini_set('yaf.name_suffix', '1');
        }
        
        $this->define ();
        
        // 注册插件
        $plugin_main = new MainPlugin ();
        $dispatcher->registerPlugin ( $plugin_main );

        // 调试时显示完整错误
        if (Helper_Debug::isDebug ()) {
            DEVELOP_LEVEL == '1' && error_reporting ( E_ALL & ~ E_STRICT );
            DEVELOP_LEVEL == '1' && ini_set('display_errors', '1');
            set_error_handler ( array ('Bootstrap', 'errorHandler' ) );
        } else {
            set_error_handler ( array ('Bootstrap','errorHandlerProduct') );
        }
    }
    
    /**
     * 定义相关常量
     *
     * @return void
     */
    protected function define() {
        // 脚本启动时间
        define ( 'NOW', isset ( $_SERVER ['REQUEST_TIME'] ) ? $_SERVER ['REQUEST_TIME'] : time () );
        
        // 模式(0.未知、1.开发、2.内网测试、3.仿真、4.生产)
        define ( 'DEVELOP_LEVEL', isset ( $_SERVER ['SRV_DEVELOP_LEVEL'] ) ? $_SERVER ['SRV_DEVELOP_LEVEL'] : 0 );
        
        // 将配置文件放入全局变量
        $config = Yaf_Application::app ()->getConfig ();
        Yaf_Registry::set ( 'config', $config );
        
        // 配置全局就能量默认值
        Yaf_Registry::set ( 'source', '0' );
    }
    
    /**
     * 处理错误（将错误变为异常）
     *
     * @param int    $code  错误代码
     * @param string $error 错误信息
     * @param string $file  文件名称
     * @param string $line  文件行数
     *            
     * @throws ErrorException
     *
     * @return void
     */
    public static function errorHandler($code, $error, $file = null, $line = null) {
        $need_ignore_errors = self::getIgnoreErrorTypes ();
        if ((error_reporting () & $code & ~ $need_ignore_errors) === $code) {
            throw new ErrorException ( $error, $code, 0, $file, $line );
        } elseif (error_reporting () & $code) {
            $error_msg = "[{$code}]:{$error} @{$file}[{$line}]";
            // 框架本身的代码出错
            if (strpos ( $file, APP_PATH ) === 0 && DEVELOP_LEVEL == '1') {
                echo 'NOTICE' . $error_msg . "<br />\n";
            } else {
                Helper_Debug::error ( $error_msg, 'NOTICE', FirePHP::WARN );
            }
        }
    }
    
    /**
     * 生产环境的的错误处理（记录错误日志）
     *
     * @param int    $code  错误代码
     * @param string $error 错误信息
     * @param string $file  文件名称
     * @param int    $line  文件行数
     *            
     * @return void
     */
    public static function errorHandlerProduct($code, $error, $file = null, $line = null) {
        $need_ignore_errors = self::getIgnoreErrorTypes ();
        if (($code & ~ $need_ignore_errors) === $code) {
            $error_type = Helper_Error::showType ( $code );
            $content = "{$error_type}[{$code}]:{$error} @{$file}[{$line}]\n";
            Helper_Log::writeApplog ( strtolower ( $error_type ), $content , FILE_APPEND, 100000);
        }
    }
    
    /**
     * 需要忽略处理的错误类型
     *
     * 在PHP<5.3.0时，应该为 E_STRICT, E_NOTICE, E_USER_NOTICE；否则，应该再加上E_DEPRECATED和E_USER_DEPRECATED。
     *
     * @return void
     */
    final static protected function getIgnoreErrorTypes() {
        $need_ignore_errors = E_STRICT | E_NOTICE | E_USER_NOTICE;
        if (version_compare ( PHP_VERSION, '5.3.0', '>=' )) {
            $need_ignore_errors = $need_ignore_errors | E_DEPRECATED | E_USER_DEPRECATED;
        }
        return $need_ignore_errors;
    }
    
    /**
     * 获取当前服务器IP
     *
     * @return string
     */
    final static protected function serverIp() {
        $str = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
        $ip = exec ( $str );
        return $ip;
    }
}
