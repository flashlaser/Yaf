<?php

/**
 * Proxy Controller抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */
class Abstract_Controller_Proxy extends Yaf_Controller_Abstract {

    // Action路径
    const ACTION_DIR = 'modules/Proxy/actions/';

    /**
     * 是否允许未签证签名
     * @var boolean
     */
    protected $allow_no_sign = false;

    /**
     * 是否允许未加密返回
     * @var boolean
     */
    protected $allow_no_encrypt = false;

    protected $need_encrypt = false;

    /**
     * header info
     * @var array
     */
    public $headers = array();

    /**
     * conf
     * @var array
     */
    public $conf = array();

    /**
     * appid
     * @var string
     */
    public $appid = 422;


    //系统类型
    public $os      = '';
    //系统版本
    public $os_ver  = '';

    /**
     * 初始化操作
     * @throws Exception_System
     */
    public function init() {
        $dispatcher = Yaf_Dispatcher::getInstance();
        $dispatcher->autoRender(false);
        $dispatcher->disableView();

        $this->headers['sign'] = '';
        $this->headers['appid'] = '';
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $this->headers[strtolower($name)] = $value;
            }
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $this->headers[strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($name, 5))))] = $value;
                }
            }
        }
        $upgrade_whitlist = array();
        $r   = $this->getRequest();
        $api = $r->getServer('SCRIPT_URL');
        $uri = $r->getParam('uri', null);
        if (!empty($this->headers ['appid'])) {
            $arr_proxy = Comm_Config::get('proxy.app');
            $arr_proxy_app = array_keys($arr_proxy);
            if (!in_array($this->headers ['appid'], $arr_proxy_app)) {
                throw new Exception_Msg('304005');
            }
            $this->conf = Comm_Config::get('proxy.app.'.$this->headers ['appid']);
            if (isset($this->conf['upgrade']['whitelist']) && isset($this->conf['upgrade']['use_appid'])) {
                $upgrade_whitlist = $this->conf['upgrade']['whitelist'];
                $use_api = $this->conf['upgrade']['use_appid'];
            }
        } else {
            $this->headers ['appid'] = $this->appid;
            $this->conf = Comm_Config::get('proxy.app.'.$this->headers ['appid']);
        }
        $use_cache = Comm_Config::get('proxy.basic.use_cache');
        if ($r->getServer('SRV_REMOTE_SERVER') || $use_cache) {
            if (Yaf_Registry::get ( 'api' )) {
                $this->allow_no_sign = true;
            }
            $r->setControllerName('Index');
            $r->setActionName('Proxy_Remote');
        } elseif (!empty($uri) && in_array($api, $upgrade_whitlist)) {
            if ($use_api) {
                $this->headers ['appid'] = $use_api;//@todo
                $r->setActionName('Proxy_Local');
            } else {
                $arr = explode('/', $uri);
                $r->setControllerName($arr[1]);
                $r->setActionName($arr[2]);
            }
        } elseif (!empty($uri)) {
            $r->setControllerName('Index');
            $r->setActionName('Proxy_Local');
        }
        //检测接口是否允许访问
        if (isset($this->conf['api']['allow_preg']) || isset($this->conf['api']['disallow_preg'])) {
            ProxyModel::isAllow($this->conf['api'],$api);
        }

        //检测配置文件必选参数
        ProxyModel::checkProxy($this->conf);

        //是否需要验证签名
        if (is_array($this->allow_no_sign)) {
            if (!in_array($r->getActionName(), $this->allow_no_sign)) {
                $this->checkSign();
            }
        } elseif ($r->getQuery('debug') == 1 && Helper_Debug::currentEnv() == 3) {
            $this->allow_no_sign = true;
        } elseif (!$this->allow_no_sign) {
            $this->checkSign();
        }

        //是否需要加密
        if (is_array($this->allow_no_encrypt)) {
            if (in_array($r->getActionName(), $this->allow_no_encrypt)) {
                $this->need_encrypt = true;
            }
        } elseif (Yaf_Registry::get ( 'api' )) {
            $this->need_encrypt = false;
        } elseif (empty($uri)) {
            $this->need_encrypt = false;
        } elseif (!$this->allow_no_encrypt) {
            $this->need_encrypt = true;
        }
    }

    /**
     * 输出结果
     * @param array $result result
     */
    public function result($result) {
        header("Content-type: application/json; charset=utf-8");
        if ($this->need_encrypt) {
            $r = $this->getRequest();
            if ($r->getQuery('debug') == 1 && Helper_Debug::currentEnv() == 3) {
                echo  $result;
            } else {
                $version  = $r->get('version');
                $url      = $r->getServer('SCRIPT_URL');
                $result = ProxyModel::encrypt($result,$url,$version);
                echo  $result;
            }
        } else {
            echo  $result;
        }
    }

    /**
     * 验证签名
     *
     * @return void
     */
    final public function checkSign() {
        $r = $this->getRequest();
        $url = $r->getServer ( 'SCRIPT_URL' );
        $uuid = $r->get ( "uuid" );
        $unique_id = '';
        if (empty($uuid)) {
            $unique_id = $r->get ('unique_id');
        }
        $version = $r->get ( "version" );
        $timestamp = $r->get ( "timestamp" );
        $sign = isset($this->headers ['sign']) ? $this->headers ['sign'] : '';
        $appid = isset ( $this->headers ['appid'] ) ? $this->headers ['appid'] : "";
        ProxyModel::sign ( $url, $unique_id, $version, $timestamp, $sign,$uuid);
    }

}
