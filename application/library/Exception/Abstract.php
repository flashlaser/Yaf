<?php
/**
 * 异常抽象类
 *
 * @package exception
 * @author  baojun <baojun4545@sina.com>
 */
abstract class Exception_Abstract extends Exception {

    /**
     * 出现异常时生产环境发现异常是否写入日志
     * @var type
     */
    protected $write_log = false;
    
    /**
     * 出现异常时生产环境发现异常是否发邮件
     * @var string
     */
    protected $send_mail = false;

    /**
     * 异常元数据
     * @var array
     */
    protected $metadata = array();
    public $msg='';
    
    /**
     * 构造方法
     * 
     * @param int	 $code     code
     * @param string $message  message  
     * @param mixed	 $metadata meta data
     * 
     * @return mixed
     */
    public function __construct($code, $message = null, $metadata = null) {
        $this->metadata = $metadata;
        if (empty($message)) {
            $message = Comm_I18n::text('errcode.' . $code);
        }
        
        if (!is_string($message) || !is_numeric($code)) {
            $error_data = "{$message}[{$code}]\n";
            foreach (debug_backtrace() as $value) {
                $file = isset($value['file']) ? $value['file'] . ' ' : '';
                $line = isset($value['line']) ? "[{$value['line']}] " : '';
                $class = isset($value['class']) ? "{$value['class']}::" : '';
                $function = isset($value['function']) ? $value['function'] : '';
                $error_data .= "{$file}{$line}{$class}{$function}\n";
            }
            $error_data .= "\n";
            @Helper_Log::writeApplog('exception_param', $error_data);
            
            //其实变为一个正常的异常数据类型
            if (!is_numeric($code)) {
                $code = 200111;
                $message = Comm_I18n::text('errcode.200111');
            }
            !is_string($message) && $message = (string)$message;
        }

        parent::__construct($message, $code);
        $this->msg=$message;
        if (php_sapi_name() === 'cli') {
            printf("============Error Trace Begin=======\n");
            echo Comm_Util::errorText(sprintf("#** Error Info [code=%s|msg=%s|meta=%s]\n", $code, $message, json_encode($metadata)));
            debug_print_backtrace();
            printf("============Error Trace End======= \n", $code, $message);
        }
    }

    /**
     * 析构方法
     */
    public function __destruct() {
        //根据情况写入日志
        if ($this->write_log) {
            Helper_Error::writeExceptionLog($this);
        }
        //根据情况发邮件
        if (Helper_Debug::isProduct() && $this->send_mail) {
            Helper_Error::sendMail($this);
        }
    }

    /**
     * 获取元数据
     * @return array
     */
    public function getMetadata() {
        return $this->metadata;
    }

}
