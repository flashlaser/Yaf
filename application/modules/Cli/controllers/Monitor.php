<?php

/**
 * 监控控制器
 *
 * @package    controller
 * @author     baojun <baojun4545@sina.com>
 */
class MonitorController extends Abstract_Controller_Cli {

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
        $this->actions = array(
            'crontab' => self::ACTION_DIR . 'Monitor/Crontab.php',
            'mcq'     => self::ACTION_DIR . 'Monitor/Mcq.php',
        	'ons'     => self::ACTION_DIR . 'Monitor/Ons.php',
        	'rdq'     => self::ACTION_DIR . 'Monitor/Rdq.php',
        );

        //输出日期
        printf("time:%s\r\n", date('Y-m-d H:i:s'));
        
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
     * 执行Shell命令
     *
     * @param	string	$request_uri
     */
    public function shell_cmd($request_uri, $write_log = false) {
    	$out = "/dev/null";
    	if ($write_log || Helper_Debug::isDebug()) {
	    	$r = $this->getRequest();
	    	if (!isset($_SERVER['SRV_APPLOGS_DIR'])) {
	    	    $_SERVER['SRV_APPLOGS_DIR'] = '/tmp/';
	    	}
	    	$filename = sprintf('%s/%s/%s/%s_out.log', $_SERVER['SRV_APPLOGS_DIR'], $r->getModuleName(), $r->getControllerName(), $r->getActionName());
	    	$filename_dir = dirname($filename);
	    	if(!is_dir($filename_dir)) {
	    		mkdir($filename_dir, 0775, true);
	    	}
	    	$out = $filename;
    	}
        $cmd = $this->shell($request_uri) . " > {$out} &";
        if (function_exists('popen')) {
	        $pp  = @popen($cmd, 'r');
			@pclose($pp);
        } elseif (function_exists('shell_exec')) {
        	Comm_Util::execute($cmd);
        } else {
        	throw new Exception_Msg(100001, 'The shell cmd is not allowed.');
        }
    }

    /**
     * 获取Shell执行命令
     *
     * @param	string	$request_uri
     * @return	string
     */
    public function shell($request_uri) {
        return $this->php . ' ' . APP_PATH . "cli.php request_uri=\"{$request_uri}\"";
    }

    /**
     * 检查指定shell命令进程数
     *
     * @param	string	$shell	shell命令
     * @return	int
     */
    public function shell_proc_num($shell) {
        $shell = str_replace(array('-', '"'), array('\-', ''), $shell);
        $shell = preg_quote($shell);

        $cmd = "ps -ef | grep -v 'grep' |grep \"{$shell}\"| wc -l";
        $pp  = @popen($cmd, 'r');
        $num = trim(@fread($pp, 512)) + 0;
        @pclose($pp);

        return $num;
    }

    public function shell_proc_num_new($shell) {
        $shell = str_replace(array('-', '"'), array('\-', ''), $shell);
        $shell = preg_quote($shell);

        $cmd = "ps -ef | grep -v 'grep' |grep \"{$shell}$\"| wc -l";
        $pp  = @popen($cmd, 'r');
        $num = trim(@fread($pp, 512)) + 0;
        @pclose($pp);

        return $num;
    }

    /**
     * 检测自身进程，不允许多个进程同时运行
     *
     * @return void
     */
    public function check_self_proc() {
        $_cmd = "ps -ef | grep -v 'grep' |grep php| grep '{$GLOBALS['argv'][0]}'|grep request_uri=\"/" . $this->getRequest()->getRequestUri() . "$\" |grep -v \"/bin/sh \\-c\" | wc -l";
        
        $_pp  = @popen($_cmd, 'r');
        $_num = trim(@fread($_pp, 512)) + 0;
        //$this->printf('check_self_proc! %s %s %s', $_cmd, $_num, date('Y-m-d H:i:s'));
        @pclose($_pp);
        if ($_num > 2) {
            throw new Exception_Msg(100001, 'The process has runing.');
        }
    }
    
    /**
     * 发送让进程停止的通知（通过mc传递）
     *
     * @param string $type          类别
     * @param string $action_name	mcq 的 action name
     * @param string $idx			进程编号
     * @return bool
     */
    public function sendStop($type, $action_name, $idx) {
        $hostname = $this->hostname();
        return Comm_Mc::init()->setData(
            'shell_stop',
            array($type, $action_name, $idx, $hostname),
            'stop'
        );
    }
    
    /**
     * 让进程停止
     *
     * @param string $type          类别
     * @param string $action_name	mcq 的 action name
     * @param string $idx			进程编号
     * 
     * @return bool
     */
    public function setStop($type, $action_name, $idx) {
        $type = ucwords($type);
        $request_uri = "/Cli/{$type}/{$action_name}/proc_num/{$idx}";
        $shell       = $this->shell($request_uri);
        $proc_num    = $this->shell_proc_num_new($shell);
        $cmd = 'ps -ef |grep "' . $shell . '"|grep -v "grep" |awk \'{ print  $2}\'|head -n 1';
        $id  = exec($cmd);
        if (is_numeric($id)) {
            $cmd = "kill -9 $id";
            $ret = exec($cmd);
        }
        return true;
    }
}
