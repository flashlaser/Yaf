<?php
/**
 * 参数检验错误
 * 增加理由：内部接口规范规定参数错误的code是-4，增加此异常类是为了开发内部接口时方便捕获此类异常，然后格式化输出
 *
 * @package Exception
 * @author  baojun <baojun4545@sina.com>
 */

class Exception_Arg extends Exception_Msg{

    /**
     * 构造方法
     * 
     * @param string $message  message 
     * @param mixed	 $metadata meta data
     * 
     * @return void
     */
    public function __construct($message = null, $metadata = null) {
        parent::__construct(302001, $message, $metadata);
    }

}
