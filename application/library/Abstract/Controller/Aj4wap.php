<?php
/**
 * Ajax Controller抽象
 *
 * @package abstract
 * @author  baojun <zhangbaojun@yixia.com>
 */
class Abstract_Controller_Aj4wap extends Abstract_Controller_Admin{
    // Action路径
    const ACTION_DIR = 'modules/Wap/actions/Aj/';
    protected $set_time_limit = false;
    protected $limit_time = 5;
    
    // 禁用的Referer
    protected $deny_referer = array(
        /* 由于会影响搜索接口，先屏蔽掉
          '<',
          '>',
          'document\. ',
          '(.)?([a-zA-Z]+)?(Element)+(.*)?(\()+(.)*(\))+',
          '(<script)+[\s]?(.)*(>)+',
          'src[\s]?(=)+(.)*(>)+',
          '[\s]+on[a-zA-Z]+[\s]?(=)+(.)*',
          'new[\s]+XMLHttp[a-zA-Z]+',
          '\@import[\s]+(\")?(\')?(http\:\/\/)?(url)?(\()?(javascript:)?',
         */
    );
    // 是否必需是AJAX
    protected $check_ajax = true;
    
    /**
     * 初始化
     */
    public function init() {
        if ($this->set_time_limit) {
            set_time_limit ( $this->limit_time );
        }
        parent::init ();
        
        // 禁止自动渲染模板
        Yaf_Dispatcher::getInstance ()->autoRender ( false )->disableView ();
        
        // 检查AJAX
        $request = $this->getRequest ();
        if ($this->check_ajax && ! Helper_Debug::isDebug ()) {
            if (! $request->isXmlHttpRequest ()) {
                throw new Exception_Msg ( 303403 );
            }
        }
    }
    
    /**
     * 输出结果
     * 
     * @param int    $code code
     * @param string $msg  msg
     * @param mixed  $data data
     * 
     * @return mixed
     */
    public function result($code, $msg = '', $data = null) {
        Comm_Response::contentType ( Comm_Response::TYPE_JSON );
        $this->getResponse ()->setBody ( Comm_Response::json ( $code, $msg, $data ) );
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
    public function jsonp($code, $msg, $data = null) {
        Comm_Response::contentType ( Comm_Response::TYPE_JSON ); // 避免gzip压缩造成IE6解析出错
        $this->getResponse ()->setBody ( Comm_Response::jsonp ( $code, $msg, $data ) );
    }
    
    /**
     * cookie set
     *
     * @param string $key        key 
     * @param mixed  $val        val
     * @param int    $expire     expire 
     * @param string $domainName Domain
     * 
     * @return mixed
     */
    static public function setCookie($key, $val, $expire = 0, $domainName = '') {
        $cookie_obj = new Helper_Cookie ();
        return $cookie_obj->set ( $key, $val, $expire, $domainName );
    }
    
    /**
     * cookie get
     *
     * @param string $key key
     * 
     * @return mixed
     */
    static public function getCookie($key) {
        $cookie_obj = new Helper_Cookie ();
        $value = $cookie_obj->get ( $key );
        
        // 解决Firefox浏览器通过Flash进行文件上传时丢失cookie问题
        // 直接用GET方式获取加密串
        if ($key == '_sign' && $value == '') {
            $value = $this->getRequest ()->getQuery ( 'mySignString' );
        }
        
        return $value;
    }
}
