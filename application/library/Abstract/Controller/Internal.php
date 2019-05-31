<?php
/**
 * Internal Controller抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */
class Abstract_Controller_Internal extends Yaf_Controller_Abstract {
    //Action路径

    const ACTION_DIR = 'modules/Internal/actions/';

    //APPKEY
    public $appkey      = '';
    //是否检查ip
    public $no_ip_check = false;
    //当前用户UID
    public $uid;

    protected $check_appkey = false;
    protected $set_time_limit = false;
    protected $limit_time = 8;
    protected $headers = [];

    /**
     * 可以不用传cip
     * @var bool
     */
    protected $allow_no_cip = true;
    
    /**
     * 是否运营后台
     * @var bool
     */
    protected $is_pubadmin = false;

    /**
     * “构造方法” 检查权限
     */
    public function init() {
        //禁止自动渲染模板
        $dispatcher = Yaf_Dispatcher::getInstance();
        $dispatcher->autoRender(false);
        $dispatcher->disableView();

        $request       = $this->getRequest();
        $conf          = new Yaf_Config_Simple(include APP_PATH . '/conf/allowip.php');
        $allow_ip_list = $conf['intral'];

        if ($this->set_time_limit) {
        	set_time_limit($this->limit_time);
        }

        $this->_getHeaders();

        $ip    = Comm_Util::getRealClientIp();
        $allow_vist = false;
        /*
        foreach ($allow_ip_list as $v) {
            $v = str_replace(array('.', '+'), array('\.', '.+'), $v);
            $v = '/' . $v . '/';
            if (preg_match($v, $ip)) {
                $allow = true;
                break;
            }
        }
        */
        $ips = $conf['ip_configs'];
        //$this->is_pubadmin = false;
        if ($this->is_pubadmin && (WEIBO_ENV == 'pro' || DEVELOP_LEVEL >= 3)) {//来自后台且为线上
			foreach ($ips as $v) {
                $v = str_replace(array('.', '+'), array('\.', '+'), $v);
                $v = '/' . $v . '/';
                if (preg_match($v, $ip)) {
                    $allow_vist = true;
                    break;
                }
            }
            
        } else {
            $allow_vist = true;
        }
        if (!$allow_vist) {
        	$msg = 'IP访问受限，IP是：'.$ip;
            throw new Exception_Msg(200110, $msg);
        }
        
        $allow = true;
        //设置APPKEY
        $this->appkey = $request->getQuery('source');
        !$this->appkey && $this->appkey = $request->getPost('source');
        Yaf_Registry::set('source', $this->appkey);

        //设置当前用户UID，以用于TAuth认证
        $this->uid = $request->getQuery('uid');
        !$this->uid && $this->uid = $request->getPost('uid');
        $this->uid && Yaf_Registry::set('current_uid', $this->uid);

        //获取客户端IP
        $cip = $request->getQuery('cip');
        !$cip && $cip = $request->getPost('cip');

        //不检查IP
        if ($this->no_ip_check) {
            $allow = true;
        }

        if (!$this->allow_no_cip) {
            //必需要有CIP参数
            Comm_Assert::true($cip, 'PARAM ERROR (cip)');
        }

        if ($this->check_appkey) {
        	//对source进行判断
        	Comm_Assert::true($this->appkey, 'PARAM ERROR (source)');
        }

        //IP受限
        if (!$allow && DEVELOP_LEVEL != 1 && DEVELOP_LEVEL != 2) {
            $this->error('IP LIMIT', -1);
        }

        //设置内部接口类型
        Yaf_Registry::set('internal_api_type', 'default');
    }

    /**
     * 输出错误
     * 
     * @param string $errmsg 错误原因a
     * @param int    $errno	 错误代码d
     */
    public function error($errmsg, $errno = -5) {
        Comm_Response::contentType(Comm_Response::TYPE_JSON);
        $result = array('errno'  => $errno, 'errmsg' => $errmsg);
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * 输出程序结果
     * 
     * @param array $data	  数据内容a
     * @param int   $code     扩展代码a
     * @param array $response 追加数据a
     * 
     * @return mixed
     */
    public function result($data = null, $code = null, $response = null) {
        //$result = array('status'           => 1, 'errmsg'          => '成功');
        $result = array('status' => 200);
        is_numeric($code) && $result['code']   = $code;
        $data !== null && $result['result'] = $data;
        $response && $result           = array_merge($result, $response);

        if ($this->getRequest()->getQuery('dump')) {
            header("Content-type: text/html; charset=utf-8");
            echo "<pre>\r\n";
            print_r($result);
            echo "\r\n</pre>";
        } else {
            Comm_Response::contentType(Comm_Response::TYPE_JSON);
            $this->getResponse()->setBody(json_encode($result));
        }
    }

    /**
     * 输出程序结果
     * 
     * @param array  $data  数据内容
     * @param string $code  扩展代码
     * @param string $msg   消息内容
     * @param int    $cache 缓存时间
     * 
     * @return mixed
     */
    public function pageResult($data = null, $code = null, $msg = null, $cache = null) {
        $result = array('error_code' => 1000, 'error_msg' => '成功');
        is_numeric($code) && $result['error_code']   = $code;
        $msg !== null && $result['error_msg'] = $msg;
        if ($result['error_code'] == 1000) {
            $cache!== null && $result['cache'] = $cache;
            $data !== null && $result['data']  = $data;
        }

        if ($this->getRequest()->getQuery('dump')) {
            header("Content-type: text/html; charset=utf-8");
            echo "<pre>\r\n";
            print_r($result);
            echo "\r\n</pre>";
        } else {
            Comm_Response::contentType(Comm_Response::TYPE_JSON);
            $this->getResponse()->setBody(json_encode($result));
        }
    }

    /**
     * 获取Post参数
     * 
     * @param string $key 参数名
     * 
     * @return string 参数值
     */
    public function getPost($key) {
        return $this->getRequest()->getPost($key);
    }
    
    /**
     * 输出程序结果
     * 
     * @param mixed	 $result 数据内容
     */
    public function mobileResult($result) {
        Comm_Response::contentType(Comm_Response::TYPE_JSON);
        $this->getResponse()->setBody(json_encode($result));
    }


    /**
     * 获得header内容
     */
    private function _getHeaders() {
        if (function_exists('getallheaders')) {
            foreach ( getallheaders() as $name => $value ) {
                $this->headers[strtolower($name)] = $value;
            }
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $this->headers[strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($name, 5))))] = $value;
                }
            }
        }
    }
}
