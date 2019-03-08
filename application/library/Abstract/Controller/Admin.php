<?php

/**
 * Admin Controller抽象
 *
 * @package   abstract
 * @author    baojun <baojun@staff.sina.com.cn>
 * @copyright 2016 weibo.com all rights reserved
 */

class Abstract_Controller_Admin extends Yaf_Controller_Abstract{
    
    // Action路径
    const ACTION_DIR = 'modules/Admin/actions/';
    
    /**
     * 是否允许未登录用户访问页面
     * 如果部分Action需要不登录就可以访问，请写一维数组
     * 
     * @var boolean
     */
    protected $allow_no_login = false;
    
    /**
     * 是否获取当前用户信息
     * 
     * @var boolean
     */
    protected $fetch_current_user = true;
    
    // 当前用户信息
    public $user = array ();
    protected $page_css = array ();
    protected $header_js = array ();
    protected $page_js = array ();
    protected $title;
    protected $controller;
    protected $action;
    
    /**
     * 公共入口
     */
    public function init() {
        /*
         * header('Cache-Control: no-cache, must-revalidate');
         * header('Expires: '.date(DATE_RFC822, NOW-3600));
         * Comm_Response::contentType('html');
         */
        
        /*
         * //通过SSO获取当前用户信息
         * $this->fetch_current_user && $user = Comm_Sso::user();
         */
        // 获取用户的微博信息
        $this->fetch_current_user && @session_start () && $this->user = array (); // ModelAdmin::currentUser();
                                                                                // 临时获取用户
        $this->user = Admin_UserModel::get ();
        if (! empty ( $this->user )) {
            Yaf_Registry::set ( 'current_uid', $this->user['id'] );
        }
        
        // 必需登录才可以访问
        if (is_array ( $this->allow_no_login )) {
            if (! in_array ( $this->getRequest ()->getActionName (), $this->allow_no_login )) {
                $this->checkLogin ();
            }
        } elseif (! $this->allow_no_login) {
            $this->checkLogin ();
        }
        
        $q = $this->getRequest ();
        // controller 处理
        // $ctrl_name = $q->getControllerName();
        // if (in_array($ctrl_name, array('Login')) && !empty($this->user)) {
        // header('Location:'.Comm_Config::get('app.site.admin'));
        // exit;
        // }
        
        /*
         * $mid = Comm_Argchecker::string($q->getQuery('mid'), 'basic', 2, 2, '');
         * if (empty($mid)) {
         * header('Location:'.Comm_Config::get('app.site.admin') . "?mid=" . Comm_Config::get('admin.default.mid'));
         * exit;
         * }
         */
        $this->action = $q->getActionName ();
        $this->controller = strtolower ( $q->getControllerName () );
        
        $is_login = 0;
        $js_config['bigpipe'] = "true";
        if (! empty ( $this->user ) && $this->user['id']) {
            $is_login = 1;
            $js_config['uid'] = $this->user['id'];
            // $js_config['nick'] = $this->user['email'];
        }
        
        $this->getView ()->assign ( 'js_config', $js_config );
        // 模板覆值
        $this->getView ()->assign ( array (
                'page_css' => $this->page_css,
                'header_js' => $this->header_js,
                'page_js' => $this->page_js,
                'title' => $this->title,
                'cuser' => $this->user,
                'is_login' => $is_login,
                'js_config' => $js_config,
                'request' => $q,
                'controller' => $this->controller,
                'action' => $this->action 
        ) );
    }
    
    /**
     * 检查用户是否已经登陆，若未登陆，将抛异常
     * 
     * @param bool $check_power check power
     * 
     * @return void
     */
    final public function checkLogin($check_power = false) {
        // 临时使用新逻辑
        $uid = Yaf_Registry::get ( 'current_uid' );
        if (empty ( $uid )) {
            throw new Exception_Nologin ();
        }
        if ($check_power) {
            $menuId = Admin_MenuModel::getMenuId ( $this->getRequest () );
            $isPower = Admin_PowerModel::check ( $uid, $menuId );
            if (! $isPower) {
                // @todo 权限不足处理
                throw new Exception_Nologin ();
            }
        }
        return;
        // if( !$this->user) {
        // throw new Exception_Nologin();
        // }
    }
}
