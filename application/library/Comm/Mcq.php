<?php
/**
 * mcq 操作类
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Mcq{

    const CHAR_ALT = 18; //ALT 键的ascii码用于标记时间信息

    /**
     * 连接mcq
     * 
     * @param string $mcq_name mcq name 
     * 
     * @return Memcached
     */

    static public  function connect($mcq_name) {
        $conf = self::_getMcqConfig($mcq_name);

        $mcq = new Memcached();
        
        //一致性哈希
        $mcq->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        $mcq->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $mcq->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $mcq->setOption(Memcached::OPT_TCP_NODELAY, true);
        
        //自动failover配置
        $mcq->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 1);
        $mcq->setOption(Memcached::OPT_RETRY_TIMEOUT, 30);//等待失败的连接重试的时间，单位秒
        $mcq->setOption(Memcached::OPT_AUTO_EJECT_HOSTS, true);
        
        //超时设置
        $mcq->setOption(Memcached::OPT_CONNECT_TIMEOUT, 1000);
        $mcq->setOption(Memcached::OPT_POLL_TIMEOUT, 1000);
        $mcq->setOption(Memcached::OPT_SEND_TIMEOUT, 0);
        $mcq->setOption(Memcached::OPT_RECV_TIMEOUT, 0);
        
        $mcq->addServer($conf['host'], $conf['port']);

        return $mcq;
    }

    /**
     * 获取mcq的连接参数 host 和 port
     * 
     * @param string $mcq_name mcq name
     * 
     * @return array
     */
    static private function _getMcqConfig($mcq_name) {
        //检查mcq配置
        $srv_key = Comm_Config::get("mcq.mcq_list.{$mcq_name}.srv_key");
        $_SERVER = array_merge($_SERVER, Comm_config::get('commmcq'));

        //检查环境配置
        if (!isset($_SERVER[$srv_key])) {
            throw new Exception_System(200303, "MCQ配置错误", "mcq($mcq_name)对应的 \$_SERVER['{$srv_key}'] 不存在");
        }

        //检查连接参数
        list ($host, $port) = explode(':', $_SERVER[$srv_key]);
        if (empty($host) || empty($port)) {
            throw new Exception_System(200303, "MCQ配置错误", "mcq($mcq_name)的连接参数错误 \$_SERVER['{$srv_key}']={$_SERVER[$srv_key]}");
        }

        return array('host' => $host, 'port' => $port);
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
        $str = self::_makeInputString($mcq_name, $str);

        //写入
        return self::connect($mcq_name)->set($mcq_name, $str);
    }
    
    /**
     * 推送MCQ数据（速度快，不管返回值）
     *
     * @param string  $mcq_name    MCQ配置名称
     * @param mixed   $value       MCQ值
     * @param boolean $json_encode 是否自动json_encode
     *
     * @return boolean 成功/失败
     *
     * @author baojun
     */
    static public function push($mcq_name, $value, $json_encode=true) {
        //写入数据处理
        $str = $json_encode ? json_encode($value) : $value;
        $str = self::_makeInputString($mcq_name, $str);
    
        //写入数据
        $conf = self::_getMcqConfig($mcq_name);
        $fp = fsockopen($conf['host'], $conf['port'], $errno, $errmsg, 1);
        if (is_resource($fp)) {
            stream_set_timeout($fp, 1);
            Comm_Util::netWrite($fp, sprintf("set %s 0 0 %u\r\n%s\r\n", $mcq_name, strlen($str), $str));
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * 批量读取mcq（每次调用此方法是会重新链接mcq）
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
        $mcq = self::connect($mcq_name);
        for ($i = 0; $i < $max_num; $i++) {
            $str = $mcq->get($mcq_name);
            if (!$str) {
                break;
            }

            //数据处理
            $str = self::_parseOutputString($mcq_name, $str);
            $str && $json_decode && $str = json_decode($str, true);
            $arr_out[] = $str;
        }

        //数据处理
        return $arr_out;
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

    /**
     * 解析mcq返回的数据,去除附加信息。并检查时间，考虑记录日志
     * 
     * @param string $mcq_name mcq别名
     * @param string $str      读取的mcq内容
     * 
     * @return string       去除附加信息后的数据
     */
    private static function _parseOutputString($mcq_name, $str) {
        if (substr($str, 0, 1) == chr(self::CHAR_ALT)) { //加入了时间信息
            $in_time = substr($str, 1, 10); //提取写入时间
            if (trim($in_time, '0123456789') == '') { //时间戳通过基本检测
                //去除附加的时间信息
                $str = substr($str, 11);

                //报警检测
                $max_delay = Comm_Config::get("mcq.mcq_list.{$mcq_name}.max_delay");
                if ($max_delay > 0) {
                    $out_time = time(); //读取时间
                    if ($out_time - $in_time > $max_delay) {
                        //此处写报警代码
                        $data = array('mcq_name' => $mcq_name, 'max_delay' => $max_delay, 'real_delay' => $out_time - $in_time, 'mcq_content' => $str);
                        Helper_Log::writeApplog('mcq_out_time', json_encode($data));
                    }
                }
            }
        }

        return $str;
    }

    /**
     * 检查mcq一段时间的写入是否有变化
     * 
     * @param String $mcq_name mcq别名
     * 
     * @return bool
     */
    public static function checkMcqWriteException($mcq_name) {
    	//从conf表中获取之前的写入量
    	$conf_key = 'mcq_'. $mcq_name;
    	$num_conf = ModelKv::get($conf_key);
    	$conf = self::_getMcqConfig($mcq_name);
    	//获取写队列数
    	$num_real = ModelMcqmonitor::getMcqWrite($mcq_name, $conf['port']);
    	//方便异常排查
    	$text = $mcq_name .'\t'. $num_real .'\t'. $num_conf;
    	Helper_Log::writeDailyLog('firehouscheck', $text);
    	if ($num_real > $num_conf || !$num_conf) {
    		ModelKv::set($conf_key, $num_real);
    		return false;
    	} else {
    		ModelKv::set($conf_key, $num_real);
    		return true;
    	}    	
    }
}
