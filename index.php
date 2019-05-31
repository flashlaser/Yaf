<?php

/**
 * index入口
 *
 * @package   Index
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia all rights reserved
 */

define('APP_PATH', dirname(__FILE__) . '/');

/**
 * ****
 * xhprof在121上使用的方法
 * 1.执行 ：url?is_xhprof=1
 * 2.hosts ：101.201.197.10 xhprof.miaopai.com
 * 3.访问 ：http://xhprof.miaopai.com/list.php
 */
if (isset($_GET['is_xhprof']) 
    && $_GET['is_xhprof'] == '1' 
    && extension_loaded('xhprof') === true
) {
    define('IS_DEBUG_XHPROF', true);
    xhprof_enable(XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_NO_BUILTINS);
} else {
    if (getenv('SRV_DEVELOP_LEVEL') == 5) {
        define('IS_DEBUG_XHPROF', true);
    } else {
        define('IS_DEBUG_XHPROF', false);
    }
}

try {
    $app = new Yaf_Application(APP_PATH . 'conf/app.ini');
    $app->bootstrap()->run();
} catch (Exception $e) {
echo '<pre>';
var_dump($e);
    die('Ufo comes up, please call for help or contact us!');
}
