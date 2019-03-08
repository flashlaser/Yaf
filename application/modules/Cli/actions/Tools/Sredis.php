<?php
/**
 * 将语言包由简体转换为繁体
 *
 * 执行命令：
 * /usr/local/bin/php cli.php request_uri='/cli/tools/sredis' 
 * 
 * @package Action
 * @author  baojun <baojun4545@sina.com>
 */

class SredisAction extends Yaf_Action_Abstract{

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
        ini_set('memory_limit','1024M');
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
        $result = array_fill_keys($patterns, 0);
        $arr_keys = array();
        $hkey = 'hkey';
        $arr_keys = $redis->HKEYS($hkey);
        $lcs = new Helper_Lcs();
        //while ($keys = $redis->scan($it, $match = '*', $count = 100)) {
        $keys = $redis->scan($it, $match = '*', $count = $this->_limit);
        foreach ($keys as $key) {
            $arr_keys = $redis->HKEYS($hkey);
            if (!empty($arr_keys)) {
                $this->_deal($arr_keys, $key);
            } else {
                $redis->hSet($hkey, $key, 1);
            }
        }
    }
    
    /**
     * deal 
     * 
     * @param array  $arr_keys 数组keys
     * @param string $key      键值
     * 
     * @return boolean
     */
    private function _deal($arr_keys, $key) {
        $lcs = new Helper_Lcs();
        foreach ($arr_keys as $kv) {
            $pos = $lcs->getSimilar($key, $kv);
            $flag = $redis->HEXISTS($hkey, $key);
            if ($pos < 0.2 && !$flag) {
                $redis->hSet($hkey, $key, 1);
                $arr = $redis->HKEYS($hkey);
                $this->_deal($arr, $key);
            }
        }
        
        return true;
    }
}
