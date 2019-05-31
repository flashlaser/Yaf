<?php
/**
 * 清理mc
 * 把key写入redis队列，脚本做清除操作
 *
 * 执行命令：
 * /usr/local/bin/php cli.php request_uri='/cli/tools/cmc' 
 * 
 * @package Action
 * @author  baojun <baojun4545@sina.com>
 */

class CmcAction extends Yaf_Action_Abstract{

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
        
        $mc = Comm_Mc::init(COmm_MC::YIXIA);
        $q_redis = Comm_Redis::r(Comm_Redis::RDQ, true);
        
        $max_num = 5000;
        for ($i = 0; $i < $max_num; $i++) {
            $val   = $q_redis->lpopData('clear8167', array());
            if ($val === false) {
                break;
            }
            if ($val) {
                $ret = $mc->deleteData('user_info_old', array($val));
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
