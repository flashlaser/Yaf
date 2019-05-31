<?php
/**
 * 监控控制器
 *
 * @package cli_controller
 * @author  baojun <baojun4545@sina.com>
 */
class McqController extends Abstract_Controller_Cli {

    //PHP bin所在路径
    protected $php = '/usr/local/bin/php';

    /**
     * 初始化方法
     *
     * @return void
     */
    public function init() {
    	parent::init();
    	
    	//具体的Action映射
    	$action_name = $this->getRequest()->getActionName();
    	
    	$this->actions = array(
    			$action_name => self::ACTION_DIR . 'Mcq/' . ucfirst($action_name) . '.php',
    	);

        //检查自身进程是否存在
        $this->check_self_proc();
    }

    /**
     * 获取当前服务器IP
     *
     * @return string
     */
    public function get_ip() {
        static $ip = null;

        if (!$ip) {
            $str = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
            $ip  = exec($str);
        }
        return $ip;
    }

    /**
     * 检测自身进程，不允许多个进程同时运行
     *
     * @return void
     * @throws Exception_System
     */
    public function check_self_proc() {
        $_cmd = "ps -ef | grep -v 'grep'| grep -v 'sudo' |grep php| grep '{$GLOBALS['argv'][0]}'|grep request_uri=\"/" . $this->getRequest()->getRequestUri() . "\" |grep -v \"/bin/sh \\-c\" | wc -l";
        $_num = Comm_Util::execute($_cmd);
        if ($_num > 1) {
            throw new Exception_System('200304', null, array($this->getRequest()->getRequestUri()));
        }
    }

}
