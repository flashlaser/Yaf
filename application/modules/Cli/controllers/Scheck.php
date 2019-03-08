<?php

/**
 * 监控控制器
 *
 * 执行命令：
 * /usr/local/bin/php cli.php request_uri='/cli/scheck/run'
 * 
 * @package   Controller
 * @author    baojun <zhangbaojun@yixia.com>
 * @copyright copyright(2016) yixia.com all rights reserved
 */
class ScheckController extends Abstract_Controller_Cli {

    //PHP bin所在路径
    protected $php = '/usr/local/bin/php';
    
    protected $output_type = 2;
    
    const OUTPUT_MAIL = 1;
    const OUTPUT_FILE = 2;

    /**
     * index的controller
     */
    function runAction() {
    	$mailto = 'zhangbaojun@yixia.com';
    	$r = $this->getRequest();
    	$mail = Comm_Argchecker::string($r->getParam('mail'), 'basic', 2, 2, $mailto);
        $c = new Helper_Scheck();
        $c->is_show_right = 1;
        $uid = 4545;
        $title = 'scheck-'.$this->_getIp() .'-'. date('YmdHis');
        //$mailto = 'tanglijia@yixia.com';
        ScheckModel::setConfig($c, $uid);
        $html = $c->cliRun();

        switch ($this->output_type) {
        	case self::OUTPUT_MAIL:
        	    Helper_Smtp::sendMail($mailto, $title, $html);
        	    break;
        	case self::OUTPUT_FILE:
        	    $html = strip_tags($html);
        	    $this->output($html);
        	    break;
        }
    }

    /**
     * 获取当前服务器IP
     *
     * @return string
     */
    protected function _getIp() {
        static $ip = null;

        if (!$ip) {
            $str = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
            $ip  = exec($str);
        }
        return $ip;
    }


}
