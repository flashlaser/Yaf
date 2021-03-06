<?php
/**
 * Mcq的Action抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */

class Abstract_Action_Mcq extends Yaf_Action_Abstract{
    
    /**
     * 每次批量读取mcq的数量
     * 
     * @var int
     */
    protected $max_num = 100;
    
    /**
     * 默认获取不到数据睡多长时间
     * 
     * @var int
     */
    protected $sleep = 1;
    
    /**
     * 心跳最小间隔时间，秒
     * 
     * @var int
     */
    protected $min_beat_time = 60;
    
    /**
     * 增加钩子方法
     */
    public function doInit() {
    }
    
    /**
     * excute 
     * 
     */
    final public function execute() {
        $this->doInit ();
        
        // 参数
        $action_name = $this->getRequest ()->getActionName (); // 控制器名称
        $idx = $this->getRequest ()->getParam ( 'proc_num' ); // 进程编号
        if ($idx < 1) {
            throw new Exception_System ( 200301, "进程编号小于1", array ($this->getRequest ()->getRequestUri () ) );
        }
        $action_name = ucfirst ( strtolower ( $action_name ) ); // action首字母大写
                                                          // mcq 名称
        $mcq_name = $this->getMcqName ( $action_name, $idx );
        
        // 资源
        $controller = $this->getController ();
        $mc = Comm_Mc::init ( Comm_Mc::BASIC );
        
        // 检查uri是否合法
        if ("Cli/Mcq/{$action_name}/proc_num/{$idx}" != ltrim ( $controller->getRequest ()->getRequestUri (), '/' )) {
            $exception_data = array (
                    'right' => "Cli/Mcq/{$action_name}/proc_num/{$idx}",
                    'error' => $controller->getRequest ()->getRequestUri () 
            );
            throw new Exception_System ( 200305, null,  $exception_data);
        }
        
        // 心跳
        $last_beat_time = 0;
        $beat_file = $file = sprintf ( Comm_Config::get ( 'mcq.pub_conf.beat_file_path' ), $_SERVER['SRV_PRIVDATA_DIR'], $action_name, $idx );
        $dir = dirname ( $beat_file );
        if (! is_dir ( $dir )) {
            mkdir ( $dir, 0777, true );
        }
        
        // 开始循环读取mcq
        while ( true ) {
            // 检查是否有停止命令
            if ($this->getController ()->checkStop ( 'mcq', $action_name, $idx ) === 'stop') {
                break;
            }
            
            // 记录心跳时间
            $now_time = time ();
            if ($now_time - $last_beat_time >= $this->min_beat_time) {
                if (file_put_contents ( $beat_file, $now_time, LOCK_EX )) {
                    $mcq_name = $this->getMcqName ( $action_name, $idx ); // 检查配置
                    $last_beat_time = $now_time;
                    echo "\033[34mbeat_time：" . date ( 'Y-m-d H:i:s', $now_time ) . "\033[0m\r\n";
                }
            }
            
            // 批量从MCQ中获取数据，外面包了一层数组
            $data = Comm_Mcq::read ( $mcq_name, true, Helper_Debug::isDebug () ? 1 : $this->max_num );
            
            if (! $data) { // 一条数据也没有获取到
                echo "\033[34mRead data empty [{$mcq_name}] " . date ( 'Y-m-d H:i:s' ) . "\033[0m\r\n";
                if (is_int ( $this->sleep )) {
                    sleep ( $this->sleep );
                } else {
                    usleep ( $this->sleep * 1000000 );
                }
            } else { // 开始处理
                $this->process ( $data );
            }
        }
        
        $time = date ( 'Y-m-d H:i:s' );
        echo "\r\n\033[31mStoped at : {$time}\033[0m\r\n";
    }
    
    /**
     * 获取mcq_name
     *
     * @param string $action_name action name
     * @param int    $idx         idx
     *
     * @return string
     * @throws Exception_System
     */
    final protected function getMcqName($action_name, $idx) {
        $arr = Comm_Config::get ( 'mcq.mcq_list' );
        
        $proc_total = null;
        foreach ( $arr as $mcq_name => $mcq_info ) {
            if ($action_name == $mcq_info['action_name']) {
                $proc_total = $mcq_info['proc_total'];
                break;
            }
        }
        
        // 检查是否有配置
        if (is_null ( $proc_total )) {
            throw new Exception_System ( 200301, "mcq($action_name,$idx)未配置，禁止运行", array ('file' => __FILE__,'line' => __LINE__ ) );
        }
        
        // 检查进程编号
        if ($idx < 1 || $idx > $proc_total) {
            throw new Exception_System ( 200301, "mcq($action_name,$idx)在配置范围[1,{$arr[$action_name]['proc_total']}]外，禁止运行", array ('file' => __FILE__,'line' => __LINE__ ) );
        }
        
        return $mcq_name;
    }
    
    /**
     * 核心处理方法,由子类实现
     *
     * @param array $datas 数据集
     */
    protected function process($datas) {
    }
    
    /**
     * 异常处理
     * 
     * @param Exception $e    exception 
     * @param mixed     $data data
     * 
     * return void
     */
    protected function onException($e, $data) {
        throw $e;
    }
    
    /**
     * 输出内容
     *
     * @param string  $text  文本
     * @param boolean $crlf  是否换行
     * @param int     $color 颜色
     *       
     * @return void
     */
    protected function _output($text, $crlf = true, $color = '') {
        // 输出颜色
        $result = '';
        $color && $result .= "\003[{$color}m";
        
        // 输出文字
        $result .= $text;
        
        // 还原空色
        $color && $result .= "\033[0m";
        
        // 换行
        $crlf && $result .= "\r\n";
        
        echo $result;
    }
    
    /**
     * 记录队列失败日志，最多记录FAIL_LOG_MAX_COUNT条，可以防止磁盘被写满。
     * FAIL_WARING_DURATION秒内连续失败FAIL_LOG_MAX_COUNT次时，发送邮件报警
     *
     * @param string $task_type task_type中不能包含字符串'###'
     * @param string $msg       msg
     * 
     * @return void
     */
    public static function logProcessFailed($task_type = '', $msg = '') {
        $ret = Helper_Log::logFailed ( $task_type . '.mcq', $msg );
        if ($ret < 0) {
            Helper_Smtp::warning ( 'MCQ队列处理异常', sprintf ( "队列任务%s %ds内连续失败%d次!", $task_type, 6, 60 ) );
        }
    }
    
    /**
     * 解析#后的时间戳
     * 
     * @param string $line xxx failed at ###%s ###msg
     * 
     * @return false | int
     */
    protected static function parseTime($line) {
        $parts = explode ( '###', $line );
        if (count ( $parts ) < 2) {
            return false;
        }
        return $parts[1];
    }
}
