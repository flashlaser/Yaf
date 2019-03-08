<?php
/**
 * 系统级别异常（错误类异常）
 * 此级别的异常属于系统本身的错误，如系统服务不可用、程序代码配置错误等
 * 此类异常会记录文件日志
 * 判断标准为：若系统一切正常，任意操作都不可能出现的异常
 *
 * @package Exception
 * @author  baojun <baojun4545@sina.com>
 */

class Exception_System extends Exception_Abstract{

    /**
     * 出现异常时生产环境发现异常是否写入日志
     * @var type
     */
    protected $write_log = true;
    
    /**
     * 出现异常时生产环境发现异常是否发邮件
     * @var string
     */
    protected $send_mail = true;

    /**
     * 构造方法
     * @param int    $code	   错误码
     * @param string $message  错误信息，值为null时将使用$code对应的默认文案
     * @param array  $metadata 可以是数组，字符串等，用于记录出错是更详细的一些信息
     * 
     * @return void
     */
    public function __construct($code, $message, $metadata) {
        parent::__construct($code, $message, $metadata);
    }

}
