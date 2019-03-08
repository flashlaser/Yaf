<?php
/**
 * Openapi Controller抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */

class Abstract_Controller_Openapi extends Yaf_Controller_Abstract{
    //Action路径

    const ACTION_DIR = 'modules/Openapi/actions/';

    //用户token
    public $token   = '';
    //当前用户ID
    public $cuid    = 0;
    //当前用户IP
    public $cip     = '0.0.0.0';
    //发起请求的appid
    public $appid   = 0;
    //发起请求的appkey
    public $appkey  = 0;
    //发起请求的网络来源，public/internel 分别对应公网和内网
    public $network = '';
    //系统类型
    public $os      = '';
    //系统版本
    public $os_ver  = '';

    //方法名
    public $action = '';
    /**
     * 是否允许没有用户token访问
     * 0.允许; 1.不允许但开发机允许; 2.坚决不允许
     * @var int
     */
    protected $need_token  = 1;
	
	/**
	* 接口签名验证的选项
	* 例：array('action_name' => array('参与排序的参数名1','参与排序的参数名2', ... ) )
	* @var array
	*/
	protected $need_sig = array();

    /**
     * 是否需要API版本控制
     */
    protected $need_version = 1;

    /**
     * 初始化操作
     */
    public function init() {
    	//parse json baojun@
    	$action_name = $this->getRequest()->getActionName();
    	$arr_action  = explode('.', $action_name);
    	if (count($arr_action > 1)) {
    		$this->getRequest()->setActionName($arr_action[0]);
    	}
    	
    	$dispatcher = Yaf_Dispatcher::getInstance();
    	$dispatcher->autoRender(false);
    	$dispatcher->disableView();
        $headers = array();
        if (function_exists('getallheaders')) {
            foreach ( getallheaders() as $name => $value ) {
                $headers[strtolower($name)] = $value;
            }
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($name, 5))))] = $value;
                }
            }
        }
        isset($headers['cuid']) && $this->cuid = $headers['cuid'];
        isset($headers['cip']) && $this->cip = $headers['cip'];
        isset($headers['network']) && $this->network = $headers['network'];
        
        $this->appid = isset($headers['appid']) ? $headers['appid'] : Comm_Config::get('gift.default.app_id');
        $this->token = isset($_REQUEST['token']) ? addslashes($_REQUEST['token']) : '';
        $this->os = isset($_REQUEST['os']) ? addslashes($_REQUEST['os']) : 'ios';
        $this->os_ver = isset($_REQUEST['version']) ? addslashes($_REQUEST['version']) : '';
		
		//check sig
		if ( is_array($this->need_sig) && ! empty($this->need_sig) ) {
		    self::checkSig( $this->getRequest(), $this->need_sig );	
		}
		
        //parse token info
        if ((is_numeric($this->need_token) && $this->need_token ) || (is_array($this->need_token) && in_array($this->getRequest()->getActionName(), $this->need_token))) {
        	$info = Helper_Miaopai_User::decodeToken($this->token);
        	$this->cuid = isset($info['id']) ? $info['id'] : 0;
        }
        
        //check user token
        if (!$this->cuid) {
            if (is_numeric($this->need_token) && ((DEVELOP_LEVEL != 1 && $this->need_token) || $this->need_token > 1)) {
                throw new Exception_Nologin(100002);
            } elseif (is_array($this->need_token) && in_array($this->getRequest()->getActionName(), $this->need_token)) {
                throw new Exception_Nologin(100002);
            }
        }

        //设置当前登录用户 
        if ($this->cuid && is_numeric($this->cuid)) {
            Yaf_Registry::set('current_uid', $this->cuid);
        }

        //设置os
        $this->os && Yaf_Registry::set('os', $this->os);
        $this->os_ver && Yaf_Registry::set('os_ver', $this->os_ver);

        //判断version
        if (is_array($this->need_version) && in_array($this->action = $this->getRequest()->getActionName(),$this->need_version) && $this->getRequest()->getQuery('version')) {
            $version = $this->getRequest()->getQuery('version');
            $rs = VersionModel::contrast($this->action,$version);
            if ($rs && !empty($rs)) {
                $this->result($rs);
            }
        }
    }

    /**
     * 检查是不是POST请求，不是就扔掉
     *
     * @return void
     */
    public function checkPost() {
        if (!$this->getRequest()->isPost()) {
            throw new Exception_Msg(200108);
        }
    }
	
	/**
     * 检查signature
     *
     * @param string $req req
     * @param array  $need_sig  need sign 
     * 
     * @return void
     */
    public function checkSig( $req, array $need_sig ) {
	    $action = $req->getActionName();
		if (array_key_exists($action, $need_sig)) {
            sort($need_sig[$action]);
			if (empty($need_sig[$action])) {
		        throw new Exception_Nologin(304000);
			}
			$encodeStr = $req->getMethod() . '&' . urlencode( $req->getServer('SCRIPT_URL') );
			$requests = $req->getRequest();
			$requests_arr = array();
			foreach ( $need_sig[$action] as $item) {
				$requests_arr[] = trim($requests[$item]);
			}
			$encodeStr .= '&' . urldecode(http_build_query(array_combine($need_sig[$action], $requests_arr))) . '&' . Comm_Config::get('openapi.sig.secret');
		    $correct_sig = md5($encodeStr);
			if ( isset($requests['sig']) && $correct_sig !== $requests['sig'] || isset($requests['sign']) && $correct_sig !== $requests['sign']) {
				throw new Exception_Nologin(304000); 
			}
		}
		return true;  
    }

    /**
     * 输出结果
     * 
     * @param array $result result
     * 
     * @return string
     */
    public function result($result) {
    	$return = array('status' => 200);
    	$return['result'] = $result;
        if (is_array($this->need_version) && in_array($this->action,$this->need_version)) {
            $ver        = VersionModel::result($this->action);
            $return['result']['version'] = $ver;
        }
            header("Content-type: application/json; charset=utf-8");
        echo json_encode($return);
    }
    
    /**
     * 输出JSONP结果
     * 
     * @param int    $code code
     * @param string $msg  msg 
     * @param mixed  $data data
     * 
     * @return mixed
     */
    public function jsonp($code, $msg = null, $data = null) {
    	Comm_Response::contentType(Comm_Response::TYPE_JSON);    //避免gzip压缩造成IE6解析出错
    	$this->getResponse()->setBody(Comm_Response::jsonp($code, $msg, $data));
    }

}
