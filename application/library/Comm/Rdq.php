<?php
/**
 * redis queue 操作类
 *
 * @package comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Rdq{

    const CHAR_ALT = 18; //ALT 键的ascii码用于标记时间信息

    const EXPIRE_TIME = 604800;//过期时间
    
    /**
     * connect
     * 
     * @param string $name name
     * 
     * @return Comm_Redis
     */
    static public  function connect($name) {
    	$config = self::_getConfig($name);
    	$id = isset($config['srv_key']) ? $config['srv_key'] : Comm_Redis::RDQ;
		
    	$rq = Comm_Redis::r($id, true);
		
        return $rq;
    }

    /**
     * get config
     * 
     * @param string $name name
     * 
     * @return array/string[]
     */
    static private function _getConfig($name) {
        //检查配置
        $config = Comm_Config::get("rdq.rq_list.{$name}");

        //检查环境配置
        if (!isset($config['action_name'])) {
            throw new Exception_System(200303, "配置错误", "rdq($name)对应的action name不存在");
        }

        return $config;
    }

    /**
     * 写入mcq
     * 
     * @param string $name        名称 
     * @param mixed  $value       要写入rq的数据
     * @param bool   $json_encode 是否自动json编码，默认为是，读取是会默认做json解码
     * 
     * @return mixed
     */
    static public function write($name, $value, $json_encode = true, $parse_string = false) {
        //写入数据处理
        $str = $json_encode ? json_encode($value) : $value;
        $parse_string && $str = self::_makeInputString($name, $str);

        $rq = self::connect($name);
        //write
        $ret = $rq->lpushData('rdq_todo', array($name), $value);
        if (!$ret) {
        	throw new Exception_System(200303, "Write to rdq todo list failure!");
        }
        
        return true;
    }
    
    /**
     * 批量读取mq（每次调用此方法是会重新链接mcq）
     * 
     * @param string $name        名称 
     * @param bool   $json_decode 是否自动json解码，默认是
     * @param int    $max_num     批量读取的最多个数，默认读取
     * 
     * @return mixed
     */
    static public function read($name, $json_decode = true, $max_num = 100, $parse_string = false) {
        //读取
        $arr_out = array();
        $config  = self::_getConfig($name);
        $max_num = isset($config['max_num']) ? $config['max_num'] : $max_num;
        $rq = self::connect($name);
        for ($i = 0; $i < $max_num; $i++) {
            $str = $rq->rpopData('rdq_todo', array($name));
			if (!$str) {
				break;
			}
			
            //数据处理
            $parse_string && $str = self::_parseOutputString($name, $str);
            $str && $json_decode && $str = json_decode($str, true);
            $arr_out[$id] = $str;
        }
        
        return $arr_out;
    }

    /**
     * delete mq
     *
     * @param string $name  name
     * @param string $id    id
     * @param string $value value 
     * 
     * @return bool
     */
    static public function delete($name, $id, $value = null, $json_encode = true) {
    	$rq = self::connect($name);
    	$ret = $rq->sremData('rdq_todo',  array($name), $id);
    	 
    	if ($value) {
    		//数据处理
    		$str = $json_encode ? json_encode($value) : $value;
    		$str = self::_makeInputString($name, $str);
    		//数据过期
    		$ret = $rq->setData('rqlist_value',  array($name,$id), $str, 604800);
    	}
    	
    	return $ret;
    }
    
    /**
     * 处理写入mcq的字符串（处理为加入时间信息）
     * 
     * @param string $mcq_name mcq名称，结合配置信息用于区分是否处理写入字符串
     * @param string $str      即将写入mcq的字符串
     * 
     * @return string           处理过后的字符串
     */
    private static function _makeInputString($mcq_name, $str) {
        if (Comm_Config::get("rdq.rq_list.{$mcq_name}.max_delay")) {
            $str = chr(self::CHAR_ALT) . time() . $str;
        }

        return $str;
    }
    
    /**
     * 解析mcq返回的数据,去除附加信息。并检查时间，考虑记录日志
     * 
     * @param string $name mcq别名，记录错误日志时需要
     * @param string $str  读取的mcq内容
     * 
     * @return string       去除附加信息后的数据
     */
    private static function _parseOutputString($name, $str) {
    	if (substr($str, 0, 1) == chr(self::CHAR_ALT)) { //加入了时间信息
    		$in_time = substr($str, 1, 10); //提取写入时间
    		if (trim($in_time, '0123456789') == '') { //时间戳通过基本检测
    			//去除附加的时间信息
    			$str = substr($str, 11);
    
    			//报警检测
    			$max_delay = Comm_Config::get("rdq.rq_list.{$name}.max_delay");
    			if ($max_delay > 0) {
    				$out_time = time(); //读取时间
    				if ($out_time - $in_time > $max_delay) {
    					//此处写报警代码
    					$data = array('rq_name' => $name, 'max_delay' => $max_delay, 'real_delay' => $out_time - $in_time, 'mcq_content' => $str);
    					Helper_Log::writeApplog('mcq_out_time', json_encode($data));
    				}
    			}
    		}
    	}
    
    	return $str;
    }
    
    /**
     * get redis q unique id
     * 
     * @param string $name name
     */
    private static function _getRdqId($name) {
    	$rq = self::connect($name);
    	$incr_num = rand(1,1); //增量为随机数
    	$id_by = $rq->incrByData('rqlist_creater', array(), $incr_num);
    	$id = time() . $id_by;
    	
    	return $id;
    }
}
