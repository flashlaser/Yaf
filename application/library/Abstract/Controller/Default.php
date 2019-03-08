<?php
/**
 * 对页面请求controller层抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */
class Abstract_Controller_Default extends Yaf_Controller_Abstract {
    //Action路径

    const ACTION_DIR = 'actions/';

    /**
     * 是否允许未登录用户访问页面
     * 如果部分Action需要不登录就可以访问，请写一维数组，如 array('hot');
     * @var boolean
     */
    protected $allow_no_login = false;

    /**
     * 是否允许不带openid访问
     * 针对于微信平台openid，需要认证获取用户openid
     * @var boolean
     */
    protected $allow_no_openid = true;

    /**
     * 是否获取当前用户信息
     * @var boolean
     */
    protected $fetch_current_user = true;

    /**
     * X-Frame-Options 是否允许放到iframe下(慎用)
     */
    protected $x_frame_options = false;

    //当前用户信息
    public $user = array();

    protected $title     = "";

    protected $page_css  = array();

    protected $header_js = array();

    protected $page_js   = array();

    //是否设置超时时间
    protected $set_time_limit = false;
    protected $limit_time = 5;

    /**
     * 公共入口
     *
     * @return void
     */
    public function init() {
    	ob_start();
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: ' . gmdate(DATE_RFC822, NOW - 3600));
        
        if ($this->x_frame_options) {
            header('X-Frame-Options:Sameorigin');
        } else {
            header('X-Frame-Options:Deny');
        }
        if ($this->set_time_limit) {
        	set_time_limit($this->limit_time);
        }
        Comm_Response::contentType('html');
        
        //通过SSO获取当前用户信息
        $this->fetch_current_user && $user = Comm_Sso::user();
        //获取用户的微博信息
        if (!empty($user)) {
            Yaf_Registry::set('current_uid', $user['uid']);
            $this->user = array();//ModelUser::currentUser();

            //新浪用户没有开通微博
            if (!$this->user) {
                echo("go to login page!");
                //header('Location:' . Comm_Config::get('app.site.weibo'));
                //exit();
            }
        }

        //必需登录才可以访问
        if (is_array($this->allow_no_login)) {
            if (!in_array($this->getRequest()->getActionName(), $this->allow_no_login)) {
                $this->checkLogin();
            }
        } elseif (!$this->allow_no_login) {
            $this->checkLogin();
        }

        $is_login = 0;
        $js_config['bigpipe'] = "true";
        if (!empty($this->user) && $this->user['uid']) {
            $is_login = 1;
            $js_config['uid']  = $this->user['uid'];
            $js_config['nick'] = $this->user['email'];
        }

        $this->getView()->assign('js_config', $js_config);
        //模板覆值
        $this->getView()->assign(array(
                'title'    => $this->title,
                'page_css' => $this->page_css,
                'header_js'=> $this->header_js,
                'page_js'  => $this->page_js,
                'cuser'	   => $this->user,
                'is_login' => $is_login,
                'js_config'=> $js_config,
        ));

        //模板覆值
        //$this->getView()->assign(array('current_user' => $this->user));
    }

    /**
     * 检查用户是否已经登陆，若未登陆，将抛异常
     *
     * @return void
     */
    final public function checkLogin() {
        if (!$this->user) {
            throw new Exception_Nologin();
        }
    }

    /**
     * 获取当前登录用户的uid， 未登录时返回0
     * 
     * @return int
     */
    public function currentUid() {
        return Yaf_Registry::get('current_uid');
    }

}
