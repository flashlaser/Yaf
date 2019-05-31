<?php
/**
 * Redis对象
 * 
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Redis{

    /**
     * 数据操作对象
     * @var Cls_db_Base
     */
    
    static $redis = array();
    
    /**
     * 是否快速的断开连接
     * @var bool
     */
    public static $quick_break_off = false;
    
    /**
     * 是否长连接
     * @var bool
     */
    public static $persistent = false;
    
    const BASIC   = '_basic_';  //基本
    const RDQ     = '_rdq_';    //redis queue
	const CNT = '_cnt_';//新版 计数器
	
    const REDIS_CONNECT_TIME_OUT = 1;

    protected $server_name;

    /**
     * 取得数据操作对象（Redis）
     * 
     * @param string $id       唯一数据标识
     * @param int    $hash_key hash key 
     * 
     * @return Comm_Redis
     */
    static public function r($id = self::BASIC, $peristent = false, $hash_key = 0) {
        //若为设置快速断开，尝试取以前的dbbase对象
        if (!self::$quick_break_off && isset(self::$redis[$id])) {
            return self::$redis[$id];
        }
        self::$persistent = $peristent;
        
        $cenv = Helper_Debug::currentEnv();
        $env  = 'product';
        switch ($cenv) {
            case Helper_Debug::EVN_DEBUG:
                $env = 'debug';
                break;
            case Helper_Debug::EVN_TEST:
                $env = 'test';
                break;
            default:
                $env = 'product';
        }
        $_SERVER = array_merge($_SERVER, Comm_Config::get("commredis.{$env}"));
        $redis = self::add(self::getServerConfig($id, $hash_key), $id);
        //若未设置快速断开数据库，保存数据库连接对象
        if (!self::$quick_break_off) {
            self::$redis[$id] = $redis;
        }
    
        return $redis;
    }
    
    /**
	 * 新增一数据连接标识
	 * 
	 * @param array $conf 数据配置信息
	 * @param int   $id   id
	 * 
	 * @return Comm_Dbbase
	 */
	static public function add($conf, $id = null) {
	    return new Comm_Redisbase($conf, $id, self::$persistent);
	}
    
    /**
     * 获取Redis的配置
     * 
     * @param string $server_name server name 
     * @param int    $hash_key    id key 
     * 
     * @return array
     */
    public static function getServerConfig($server_name, $hash_key = 0){
        if ($hash_key) {
            $config = array(
                $server_name => array(
                    '00'=>array(
                            Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS5_HOST'], 'port'=>$_SERVER['SRV_REDIS5_PORT']) ,
                            Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS5_HOST_R'], 'port'=>$_SERVER['SRV_REDIS5_PORT_R'])
                    ),
                    '01'=>array(
                            Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS6_HOST'], 'port'=>$_SERVER['SRV_REDIS6_PORT']) ,
                            Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS6_HOST_R'], 'port'=>$_SERVER['SRV_REDIS6_PORT_R'])
                    ),
                    '02'=>array(
                            Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS7_HOST'], 'port'=>$_SERVER['SRV_REDIS7_PORT']) ,
                            Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS7_HOST_R'], 'port'=>$_SERVER['SRV_REDIS7_PORT_R'])
                    ),
                    '03'=>array(
                            Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS8_HOST'], 'port'=>$_SERVER['SRV_REDIS8_PORT']) ,
                            Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS8_HOST_R'], 'port'=>$_SERVER['SRV_REDIS8_PORT_R'])
                    ),
                ),
            );

            $hash_num = isset($config[$server_name]) ? count($config[$server_name]) : 0;
            $key = self::rSubHash($hash_key, $hash_num);
            
            return isset($config[$server_name][$key]) ? $config[$server_name][$key] : false;
        } else {
            $config = array(
                Comm_Redis::BASIC=>array(
                    Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS1_HOST'], 'port'=>$_SERVER['SRV_REDIS1_PORT'], 'pass' =>$_SERVER['SRV_REDIS1_PASS']) ,
            		Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS1_HOST_R'], 'port'=>$_SERVER['SRV_REDIS1_PORT_R'], 'pass' =>$_SERVER['SRV_REDIS1_PASS'])
                ),
                Comm_Redis::RDQ=>array(
                    Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS_RDQ_HOST'], 'port'=>$_SERVER['SRV_REDIS_RDQ_PORT'], 'pass' =>$_SERVER['SRV_REDIS_RDQ_PASS']) ,
            		Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS_RDQ_HOST_R'], 'port'=>$_SERVER['SRV_REDIS_RDQ_PORT_R'], 'pass' =>$_SERVER['SRV_REDIS_RDQ_PASS'])
                ),
                Comm_Redis::CNT=>array(
                        Comm_Dbbase::CONN_MASTER => array('host'=>$_SERVER['SRV_REDIS_CNT_HOST'], 'port'=>$_SERVER['SRV_REDIS_CNT_PORT'], 'pass' =>$_SERVER['SRV_REDIS_CNT_PASS']) ,
                        Comm_Dbbase::CONN_SLAVE  => array('host'=>$_SERVER['SRV_REDIS_CNT_HOST_R'], 'port'=>$_SERVER['SRV_REDIS_CNT_PORT_R'], 'pass' =>$_SERVER['SRV_REDIS_CNT_PASS'])
                ),
            );
            
            return isset($config[$server_name]) ? $config[$server_name] : false;
        }
    }
    
    /**
     * 获取redis 哈希，得分组名称
     * 
     * @param int $n       hash维度的数值，如uid
     * @param int $redis_n 总数量
     * 
     * @return string 完整表名称
     */
    static public function rSubHash($n, $redis_n = 4) {
        $key = '00';
	    if ($n > 0 && $redis_n > 0) {
	        $idx = dechex($n % $redis_n);
	        !isset($idx{1}) && $idx = '0' . $idx;
	        $key = $idx;
	    }
	
	    return $key;
    }
} 
