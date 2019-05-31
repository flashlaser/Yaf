<?php
/**
 * 清理redis
 * 把key写入redis队列，脚本做清除操作
 *
 * 执行命令：
 * /usr/local/bin/php cli.php request_uri='/cli/tools/credis' 
 * 
 * @package Action
 * @author  baojun <baojun4545@sina.com>
 */

class CredisAction extends Yaf_Action_Abstract{

    /**
     * limit 
     * 
     * @var integer
     */
    private $_limit = 1000;
    
    /**
     * 执行Action
     * 
     * {@inheritDoc}
     * @see Yaf_Action_Abstract::execute()
     */
    public function execute() {
        $microtime = microtime(true);
        $this->printf('Start at:%s', date('Y-m-d H:i:s'));
        
        $s_redis = Comm_Redis::r(Comm_Redis::R8167);
        $q_redis = Comm_Redis::r(Comm_Redis::RDQ, true);
        
        $max_num = 5000;
        for ($i = 0; $i < $max_num; $i++) {
            $c_key   = $q_redis->lpopData('clear8167', array());
            if ($c_key === false) {
                break;
            }
            if ($c_key) {
                $ret = $s_redis->clearKey($c_key);  
            }
        }
        
        $microtime = sprintf('%.3f', microtime(true) - $microtime);
        $this->printf('Finished at:%s', date('Y-m-d H:i:s'));
        $this->printf('Deal result:%s, Use time:%s', $ret, $microtime);
    }
    
    /**
     * 类型于printf方式输出并记录日志
     *
     * @return void
     *
     * @author chengxuan
     */
    public function printf() {
        $args_array = func_get_args ();
        $text = call_user_func_array ( 'sprintf', $args_array );
        $text .= "\n";
        echo $text;
    }
}
