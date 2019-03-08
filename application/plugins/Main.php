<?php
/**
 * 公共插件
 * 
 * @package   Application
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia all rights reserved
 */

class MainPlugin extends Yaf_Plugin_Abstract {

    //当前域名
    protected $host   = '';
    //当前模块
    protected $module = '';

    /**
     * 路由开始之前，加载适合的路由规则
     * 
     * @param Yaf_Request_Abstract  $request  request
     * @param Yaf_Response_Abstract $response response
     * 
     * @return null
     */
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        //确定module
        if ($request->isCli()) {
            $this->module = 'Cli';
        } else {
            $this->host = $request->getServer('HTTP_HOST');
            $alias = str_replace('.', '_', $this->host);
            
            try {
                $modules = Comm_Config::get('app.modules');
            } catch (Exception $e) { 
            }
            $module_config = isset($modules[$alias]) ? $modules[$alias] : array('module' => 'Proxy');
            
            $this->module =  $module_config['module'];
            if (isset($module_config['chksrv']) && $module_config['chksrv']) {
                Yaf_Registry::set('api', true);
            }
            
            $uri = $request->getRequestUri();
            
            if (isset($module_config['uri2json']) && $module_config['uri2json']) {
                $uri = str_replace('.json','', $uri);
            }
            
            if (isset($module_config['api2ver']) && $module_config['api2ver']) {
                $request->setRequestUri(preg_replace('#^/(\d+)(/)(.+)#', '/v$1_$3', $uri));
            } else {
                $request->setRequestUri($uri);
            }
        }
        
        //加载相应模块的路由器
        try {
            $ini_file = APP_PATH . '/conf/routers/'. strtolower($this->module) . '.ini';
            if (is_file($ini_file)) {
                $routes_config = Comm_Config::get('routers/' . strtolower($this->module) . '.routers');
                $router        = Yaf_Dispatcher::getInstance()->getRouter();
                $router->addConfig($routes_config);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * 分发循环前
     * 
     * @param Yaf_Request_Abstract  $request  request
     * @param Yaf_Response_Abstract $response response
     * 
     * @return null
     */
    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {

        //判断解析的路由器模块是否和预计的一样
        $module = $request->getModuleName();

        //非法模块
        if ($module != 'Index') {
            if ($this->module == 'Index') {
                $request->setModuleName('Index');
                throw new Yaf_Exception_LoadFailed_Module('Illegal Modules');
            }
        }

        //将模块设置为理想模块
        $request->setModuleName($this->module);

        //模版路径定义
        if ($this->module == 'Index') {
            define('TPL_PATH', APP_PATH . 'application/views/');
        } else {
            define('TPL_PATH', APP_PATH . 'application/modules/' . $this->module . '/views/');
        }
    }

    /**
     * 分发循环结束
     *
     * @param Yaf_Request_Abstract  $request  request
     * @param Yaf_Response_Abstract $response response
     *
     * @return null
     */
    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        //xhprof
        if (defined('IS_DEBUG_XHPROF')) {
            if (IS_DEBUG_XHPROF) {
                $xhprofData  = xhprof_disable();
                include APP_PATH . "application/library/Thirdpart/xhprof/xhprof_lib/utils/xhprof_lib.php";
                include APP_PATH . "application/library/Thirdpart/xhprof/xhprof_lib/utils/xhprof_runs.php";
                $xhprofRuns  = new XHProfRuns_Default();
                $server_name = str_replace('.', '_', $_SERVER['SERVER_NAME']);
                $pid         = $server_name . "_" . $_SERVER['SCRIPT_URL'] . "_" . date('Y-m-d-H-i-s@') . mt_rand(100, 999);
                $pid         = str_replace('/', '_', $pid);
                $pid         = str_replace('.', '_', $pid);
                $run_id = $xhprofRuns->save_run($xhprofData, "xhprof", $pid);
                //echo "<a href='http://xhprof.weibo.com/xhprof_html/index.php?run=$run_id&source=xhprof'>查看统计信息</a>";
            }
        }
    }
}
