<?php
/**
 * 用户未登录
 * 增加理由：可能需要捕获此类型的异常，然后跳转到未登陆页
 *
 * @package Exception
 * @author  baojun <baojun4545@sina.com>
 */

class Exception_Nologin extends Exception_Msg {

    /**
     * 构造方法
     *  
     * @param int	 $code     code
     * @param string $message  message 
     * @param mixed  $metadata meta data 
     * 
     * @return void
     */
    public function __construct($code = 100002, $message = null, $metadata = null) {
        parent::__construct($code, $message, $metadata);
    }

}
