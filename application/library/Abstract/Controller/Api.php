<?php
/**
 * Api Controller抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */

class Abstract_Controller_Api extends Yaf_Controller_Abstract {
    
    /**
     * 是否通过OAuth获取当前用户信息(默认否)
     *
     * @var boolean
     */
    protected $fetch_oauth_current_user = false;
    
    /**
     * 是否必需要登录（默认否）
     *
     * @var boolean
     */
    protected $need_login = false;
    
    // Action路径
    const ACTION_DIR = 'modules/Api/actions/';
    
    /**
     * “构造方法” 检查权限
     */
    public function init() {
        header ( 'Cache-Control: no-cache, must-revalidate' );
        header ( 'Expires: ' . gmdate ( DATE_RFC822, NOW - 3600 ) );
        header ( 'X-Frame-Options:Deny' );
        // 禁止自动渲染模板
        $dispatcher = Yaf_Dispatcher::getInstance ();
        $dispatcher->autoRender ( false );
        $dispatcher->disableView ();
        
        // 获取AccessToken
        $request = $this->getRequest ();
        $access_token = $request->getServer ( 'HTTP_ACCESS_TOKEN', '' );
        
        // 开发模式尝试从GET URL中获取
        if (! $access_token && Helper_Debug::isDebug ()) {
            $access_token = $request->getQuery ( 'access_token' );
        }
        if ($access_token) {
            Yaf_Registry::set ( 'access_token', $access_token );
        }
        
        // 通过AccessToken获取当前用户信息
        if (is_array ( $this->fetch_oauth_current_user )) {
            $action = $request->getActionName ();
            $fetch_oauth_uid = in_array ( $action, $this->fetch_oauth_current_user );
        } else {
            $fetch_oauth_uid = $this->fetch_oauth_current_user;
        }
        if ($fetch_oauth_uid) {
            if ($access_token) {
                $apilib_wb = $this->_wb ();
                try {
                    $result_uid = $apilib_wb->accountGetuid ();
                } catch ( Exception_Openapi $e ) {
                }
                if (isset ( $result_uid['uid'] )) {
                    ; // Yaf_Registry::set('current_uid', $result_uid['uid']);
                }
            }
        }
        
        // 未登录
        if (is_array ( $this->need_login )) {
            $action = $request->getActionName ();
            $need_login = in_array ( $action, $this->need_login );
        } else {
            $need_login = $this->need_login;
        }
        if ($need_login && ! Yaf_Registry::get ( 'current_uid' )) {
            throw new Exception_Nologin ();
        }
    }
    
    /**
     * 输出程序结果
     * 
     * @param mixed $result 数据内容
     * 
     * @return mixed
     */
    public function result($result = null) {
        if ($this->getRequest ()->getQuery ( 'dump' )) {
            header ( "Content-type: text/html; charset=utf-8" );
            echo "<pre>\r\n";
            print_r ( $result );
            echo "\r\n</pre>";
        } else {
            Comm_Response::contentType ( Comm_Response::TYPE_JSON );
            $this->getResponse ()->setBody ( json_encode ( $result ) );
        }
    }
    
    /**
     * 获取Apilib_Wb操作对象（仅用于Access认证）
     *
     * @return Apilib_Wb
     */
    protected function _wb() {
        $apilib_wb = new Apilib_Wb ();
        $apilib_wb->setOauth ( Yaf_Registry::get ( 'access_token' ) );
        return $apilib_wb;
    }
}
