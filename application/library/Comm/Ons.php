<?php
/**
 * ons mq 操作类
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Ons{

    const CHAR_ALT = 18; //ALT 键的ascii码用于标记时间信息

    /**
     * 连接mcq
     * 
     * @param string $mcq_name mcq name
     * 
     * @return Memcached
     */

    static public  function connect($mcq_name) {
        $conf = self::_getMqConfig($mcq_name);
        $mq = new Apilib_Ons($conf['Topic'], $conf['ProducerId'], $conf['ConsumerId']);

        return $mq;
    }

    /**
     * 获取mcq的连接参数 host 和 port
     * 
     * @param string $mcq_name mcq name
     * 
     * @return array
     */
    static private function _getMqConfig($mcq_name) {
        //检查mq配置
        //$topic = Comm_Config::get("ons.mq_list.{$mcq_name}.Topic");

        //检查环境配置
        /*if (!isset($_SERVER[$topic])) {
            throw new Exception_System(200303, "MQ配置错误", "mcq($mcq_name)对应的 \$_SERVER['{$srv_key}'] 不存在");
        }*/

        $topic 		= Comm_Config::get("ons.mq_list.{$mcq_name}.Topic");
        $producerId = Comm_Config::get("ons.mq_list.{$mcq_name}.ProducerId");
        $consumerId = Comm_Config::get("ons.mq_list.{$mcq_name}.ConsumerId");
        
        return array('Topic' => $topic, 'ProducerId' => $producerId, 'ConsumerId' => $consumerId);
    }

    /**
     * 写入mcq
     * 
     * @param string $mcq_name    MCQ名称，必须是 self::MCQ_XX
     * @param mixed  $value       要写入mcq的数据
     * @param bool   $json_encode 是否自动json编码，默认为是，读取是会默认做json解码
     * 
     * @return mixed
     */
    static public function write($mcq_name, $value, $json_encode = true) {
        //写入数据处理
        $str = $json_encode ? json_encode($value) : $value;
        //$str = self::makeInputString($mcq_name, $str);

        //写入
        return self::connect($mcq_name)->send($mcq_name, $str);
    }
    
    /**
     * 批量读取mq（每次调用此方法是会重新链接mcq）
     * 
     * @param string $mcq_name    MCQ名称，必须是 self::MCQ_XX
     * @param bool   $json_decode 是否自动json解码，默认是
     * @param int    $max_num     批量读取的最多个数，默认读取
     * 
     * @return mixed
     */
    static public function read($mcq_name, $json_decode = true, $max_num = 100) {
        //读取
        $arr_out = array();
        $mq = self::connect($mcq_name);
        $rs = $mq->request();
        if (!$rs) {
         	return false;
        }
		foreach ($rs as $k => $v) {
            //数据处理
            $rs[$k]['body'] && $json_decode && $rs[$k]['body'] = json_decode($rs[$k]['body'], true);
		}

        //数据处理
        return $rs;
    }

    /**
     * delete mq
     * 
     * @param unknown $mcq_name mcq name
     * @param unknown $handle   handle
     * 
     * @return bool
     */
    static public function delete($mcq_name, $handle) {
    	$mq = self::connect($mcq_name);
    	$ret = $mq->delete($handle);
    	
    	return $rs;
    }
    
    /**
     * 处理写入mcq的字符串（处理为加入时间信息）
     * 
     * @param string $mcq_name mcq名称，结合配置信息用于区分是否处理写入字符串
     * @param string $str      即将写入mcq的字符串
     * 
     * @return string          处理过后的字符串
     */
    private static function _makeInputString($mcq_name, $str) {
        if (Comm_Config::get("mcq.mcq_list.{$mcq_name}.max_delay")) {
            $str = chr(self::CHAR_ALT) . time() . $str;
        }

        return $str;
    }
}
