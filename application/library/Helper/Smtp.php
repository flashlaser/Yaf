<?php
/**
 * 使用smtp发送邮件
 *
 * @package Helper
 * @author  baojun <zhangbaojun@yixia.com>
 */
require APP_PATH . 'application/library/Thirdpart/mailer/PHPMailer.php';
class Helper_Smtp {
    
    /**
     * 发送邮件
     *
     * @param string $to 接收方邮件地址
     * @param string $title 邮件标题
     * @param string $content 邮件正文内容
     * @param string $mail_type 邮件格式(HTML/TXT)
     *       
     * @return boolean
     */
    static public function sendMail($to, $title, $content, $mail_type = 'HTML') {
        $mail = new PHPMailer();
        $mail->isSmtp();
        $mail->SMTPAuth = true; // turn on SMTP authentication
        $mail->Port = 25;
        $mail->Timeout = 30;
        $mail->Priority = 3;
        $mail->CharSet = "utf-8";
        $mail->isHtml(true);
        $mail->clearAddresses();
        $mail->FromName = '监测系统';
        
        $mail->Host = 'smtp.yixia.com';
        $mail->Username = "monitor@yixia.com";
        $mail->Password = "mpkqNziW6qJUyiQb";
        $mail->From = "monitor@yixia.com";
        
        $arr_to = explode(',', $to);
        foreach ($arr_to as $to) {
            $mail->addAddress($to);
        }
        $mail->Subject = $title;
        $mail->Body = $content;
        
        $result = $mail->send();
        return $result;
    }
    
    /**
     * 发送警告邮件
     *
     * @param string $title 标题
     * @param string $content 内容
     *       
     * @return void
     */
    static public function warning($title, $content) {
        $arr_to   = Comm_Config::get('warning.mail');
        $arr_mail = array_values($arr_to);
        $to = implode(',', $arr_mail);
        $title .= ' [WARNING]';
        $content .= sprintf("<br /><br />\r\nip:%s<br />\r\n", self::_getServerIp());
        $content .= sprintf("<br /><br />\r\ndate:%s<br />\r\n", date('Y-m-d H:i:s'));
        self::sendMail($to, $title, $content);
    }
    
    /**
     * 发送废弃方法仍在使用的警告邮件
     *
     * @return void
     */
    static public function deprecated() {
        $ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
        $to = 'supertest3@sina.com';
        $title = "Deprecated call {$ip}";
        
        $content = '<pre>' . print_r(debug_backtrace(), true) . '</pre>';
        self::sendMail($to, $title, $content);
    }
    
    /**
     * 获取服务器端IP地址
     * @return string
     */
    static private function _getServerIp() {
        $server_ip = 'unknown';
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}