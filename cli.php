<?php
/**
 * cli入口
 * 
 * @package   CLIfff
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia all rights reserved
 */

define('APP_PATH', dirname(__FILE__) . '/');

/**
 * xhprof在121上使用方法
 * 1.执行 ：url?is_xhprof=1
 * 2.hosts ：10.210.210.121 xhprof.hot.weibo.com
 * 3.访问 ：http://xhprof.hot.weibo.com/list.php
 */
if (isset($_GET['is_xhprof']) && $_GET['is_xhprof'] == '1' && extension_loaded('xhprof') === true) {
    define('IS_DEBUG_XHPROF', true);
    xhprof_enable(XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_NO_BUILTINS);
} else {
    define('IS_DEBUG_XHPROF', false);
}

$app = new Yaf_Application(APP_PATH . '/conf/app.ini');
$app->bootstrap()
    ->getDispatcher()
    ->dispatch(new Yaf_Request_Simple());
