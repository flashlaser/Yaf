<?php
/**
 * OPEN API 异常
 * 新增理由：为了格式化opne api的code（5位）和转化一些提示文案,以及记录日志
 *
 * @package Exception
 * @author  baojun <baojun4545@sina.com>
 */

class Exception_Openapi extends Exception_Msg{

    protected $write_log = true;

    /**
     * 构造方法
     * 
     * @param int	 $code     code 
     * @param string $message  message
     * @param mixed  $metadata meta data 
     * 
     * @return void
     */
    public function __construct($code, $message = null, $metadata = null) {
        try {
            $error_msg = Comm_I18n::text('wbapi.' . $code);
            if ($error_msg == 'wbapi.' . $code) {
                $error_msg = "{$message} ({$code})";
            }
        } catch (Exception $e) {
            $error_msg = $message;
        }

        parent::__construct($code, $error_msg, $metadata);
    }

}