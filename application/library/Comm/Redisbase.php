<?php
/**
 * Redis底层操作
 *
 * @package Comm
 * @author  Baojun <baojun4545@sina.com>
 */

class Comm_Redisbase{

    /**
     * redis key 前缀
     * @var string
     */
    public $pre = '';
    
    /**
     * 主库连接对象
     * @var Redis
     */
    protected $redis_master;

    /**
     * 从库连接对象
     * @var Redis
     */
    protected $redis_slave;

    /**
     * 数据库ID（主）
     * @var int
     */
    const CONN_MASTER = 1;

    /**
     * 数据库ID（从）
     * @var int
     */
    const CONN_SLAVE = 2;

    /**
     * 操作方式（set）
     * @var string
     */
    const TYPE_SET = 'set';
    
    /**
     * 操作方式（get）
     * @var string
     */
    const TYPE_GET = 'get';
    
    /**
     * 读锁
     * @var string
     */
    const LOCK_READ = 'READ';

    /**
     * 写锁
     * @var string
     */
    const LOCK_WRITE = 'WRITE';

    //当前语言是否失败后再次执行
    protected $agian = false;

    protected $timeout = 5;
    /**
     * 最后一个操作方法
     * @var string
     */
    public $method = '';

    /**
     * 数据库配置（格式参数classes/db.php  function:add）
     * @var array
     */
    public $conf = array();
    
    protected $server_name = '';

    protected static $redis_instance = array();
    
    /**
     * 是否长连接
     * @var bool
     */
    public $persistent = false;
    
    /**
     * 构造方法
     * 
     * @param array  $conf 数据库连接配置
     * @param string $id   数据库资源标识
     * 
     * @return void
     */
    public function __construct($conf, $id = null, $persistent = false) {
        $this->conf = $conf;
        $this->server_name = $id;
        $this->persistent = $persistent;
    }

    /**
     * 锁表
     *
     * <code>
     * $db->lock(array(
     * 	'table1'	=> Dbbase::LOCK_READ,
     * 	'table2'	=> Dbbase::LOCK_WRITE,
     * 	'table3'	=> Dbbase::LOCK_WRITE,
     * ));
     * </code>
     * 
     * @param array $tables 表名及锁定类型
     * 
     * @return void
     */
    public function lock(array $tables) {
        $lock = array();
        foreach ($tables as $table => $lock_type) {
            $lock[] = "`{$table}` {$lock_type}";
        }
        $sql = 'LOCK TABLES ' . implode(',', $lock);
        $this->execute($sql);
    }

    /**
     * 解除锁表
     */
    public function unlock() {
        $this->execute('UNLOCK TABLES');
    }

    /**
     * 通过method语句，确定使用的服务器
     * 
     * @param string $method 方法名称
     * 
     * @return int
     */
    protected function getServer($method) {
        return !self::isSetMethod($method) ? self::CONN_SLAVE : self::CONN_MASTER;
    }

    /**
     * 取得数据库帐号信息
     * 
     * @param int $server 指定哪台服务器
     * 
     * @return array
     */
    protected function getConf($server) {
        return isset($this->conf[$server]) ? $this->conf[$server] : array();
    }

    /**
     * 连接redis
     * 
     * @param string $host 主机地址
     * @param string $port 数据库端口
     * @param string $type 主从类型
     * @param string $pass 密码
     * 
     * @return mixed
     */
    public function connect($host, $port, $type, $pass = null) {
        $key = $this->server_name.'-'.$type;
        if (isset(self::$redis_instance[$key])) {
            return self::$redis_instance[$key];
        }
        
        $redis = null;
        try {
            $redis = new Redis();
            if ($this->persistent) {
                $redis->pconnect($host, $port, $this->timeout);
            } else {
                $redis->connect($host, $port, $this->timeout);
            }
            if (!empty($pass)) {
            	$redis->auth($pass);
            }
            self::$redis_instance[$key] = $redis;
            
            return $redis;
        } catch (RedisException $e) {
            throw new Exception_System(200101, "Redis连接失败", array('redis_code' => $e->getCode(), 'redis_msg' => $e->getMessage()));
        }
        
        return false;
    }

    /**
     * 测试连接是否存在，如果不存在，重新连接
     * 
     * @param string/int $server 根据SQL语句自动选择或强制指定数据库
     * 
     * @return PDO
     */
    public function getRedis($server) {
        !is_int($server) && $server = $this->getServer($server);
        if ($server == self::CONN_MASTER) {
            $redis = & $this->redis_master;
        } else {
            $redis = & $this->redis_slave;
        }

        if (!$redis || $this->agian) {
            $conf = $this->getConf($server);
            $redis = $this->connect($conf['host'],  $conf['port'], $server,  $conf['pass']);
        }

        return $redis;
    }

    /**
     * 动态调用Redis方法
     * 
     * @param string $method 操作方法
     * @param array  $param  变量参数
     * 
     * @return PDOStatement
     */
    public function __call($method, $param = null) {
        $this->method = $method;
        $is_master = false;
        $server = ($is_master || $this->getServer($this->method) == self::CONN_MASTER) ? self::CONN_MASTER : self::CONN_SLAVE;
        
        try {
            $redis = $this->getRedis($server);
            if (!$redis instanceof Redis) {
                return false;
            }
            $param && !is_array($param) && $param = (array)$param;
            $result = call_user_func_array(array($redis, $method), $param);
    
            $this->agian = false;
        } catch (Exception $e) {
            $content = sprintf('Redis Server连接失败：server name:%s [%s] method:%s;code:%s;msg:%s', $server, self::CONN_MASTER ? 'Master' : 'Slave', $method, $e->getCode(), $e->getMessage());
            throw new Exception_System(200101, $content, $e->getTrace());
        }
        
        return $result;
    }
    
    /**
     * 判断一个方法是否为set方法
     * 
     * @param string $method_name 方法名称
     * 
     * @return bool
     */
    protected static function isSetMethod($method_name) {
        $set_methods = 'set,setex,setnx,del,delete,incr,incrByFloat,incrBy,decr,decrBy,lPush,rPush,lPushx,rPushx,lPop,rPop,blPop,brPop,lSet,lTrim,listTrim,lRem,lRemove,lInsert,sAdd,sRem,sRemove,sMove,sPop,sInterStore,sDiffStore,rename,renameKey,renameNx,expire,pExpire,expireAt,pExpireAt,setRange,setBit,persist,mset,msetnx,rpoplpush,brpoplpush,zAdd,zRem,zDelete,zRevRange,zRemRangeByScore,zDeleteRangeByScore,zRemRangeByRank,zDeleteRangeByRank,zIncrBy,zUnion,zInter,hSet,hSetNx,hDel,hIncrBy,hIncrByFloat,hMset,hMGet,evaluate,evalSha,evaluateSha,restore,migrate';
        return strpos($set_methods, $method_name) !== false;
    }

    /**
     * 获取某一缓存的配置
     * 
     * @param string $config 配置
     * @param array  $args   参数
     * 
     * @return	array
     */
    public function getConfig($config, array $args) {
        $config = Comm_Config::getUseStatic('redis.' . $config);
    
        $param_arr = array($config['key']);
        $param_arr = array_merge($param_arr, $args);
        $key = call_user_func_array('sprintf', $param_arr);
    
        $config['key'] = $this->pre.$key;
        
        return $config;
    }
    
    /**
     * make key 
     * 
     * @param string $config 配置
     * @param array  $args   参数
     * 
     * @return Ambigous <>
     */
    public function makeKey($config, array $args) {
        $conf = $this->getConfig($config, $args);
        
        return $conf['key'];
    }
    
    /**
     * 写入一条数据
     * 
     * @param string $config 配置
     * @param array	 $args   参数
     * @param mixed  $value  值
     * @param mixed  $expire 过期时间
     * 
     * @return	array
     */
    public function setData($config, array $args, $value, $expire = false) {
        $conf = $this->getConfig($config, $args);
        if (!$expire) {
        	$expire = isset($conf['expire']) ? $conf['expire'] : null;
        }
        
        if ($expire) {
            return $this->setex($conf['key'], $expire, $value);
        } else {
            return $this->set($conf['key'], $value);
        }
        
    }
    
    /**
     * 获取一条数据
     * 
     * @param string $config 配置
     * @param array	 $args   参数
     * 
     * @return	mixed
     */
    public function getData($config, array $args = array()) {
        $conf = $this->getConfig($config, $args);
        return $this->get($conf['key']);
    }

    /**
     * 删除一条数据
     * 
     * @param string $config config key
     * @param array	 $args   args 
     * 
     * @return	string
     */
    public function deleteData($config, array $args = array()) {
        $conf = $this->getConfig($config, $args);
        return $this->delete($conf['key']);
    }
    
    /**
     * 清理key
     *
     * @param string $key key 
     *
     * @return	string
     */
    public function clearKey($key) {
        return $this->delete($key);
    }
    
    /**
     * 批量获取数据
     * 
     * @param unknown $config 配置
     * @param array   $args   批量参数（二维数组；或一维数据，里面每组arg用逗号隔开）
     * 
     * @return mixed
     */
    public function getMultiData($config, array $args) {
        $keys = array();
        foreach ($args as $arg) {
            is_scalar($arg) && $arg = explode(',', $arg);
    
            $conf = $this->getConfig($config, $arg);
            $keys[] = $conf['key'];
        }

        return $this->getMultiple($keys);
    }
    
    /**
     * 保持key映射的批量取数据
     *
     * user_bar_conf.key = user_bar_conf_%s_%s; $uid,$bid
     * $mc->mapGetMultiData('user_bar_conf', array($uid,$bids, 1);//uid不变按bids批量取
     * $mc->mapGetMultiData('user_bar_conf', array($uids,$bid, 0);//bid不变按uids批量取
     *
     * @param string $config    cache.ini里的缓存名称
     * @param array  $args      数字索引，其单元依次对应$cache_ini的变量，其中有且仅有一个单元是数组,用于批量
     * @param int    $multi_key 数组单元的key，值是返回值数组的key
     *
     * @return array 保持和$args的对应关系，若没有则那个单元不存在，不会赋值为falsh
     * @author baojun
     *
     */
    public function mapGetMultiData($config, array $args, $multi_key) {
        //初始化数据
        $map = $keys = $out = array();
    
        //生成key列表以及key的映射表
        $c_config = Comm_Config::getUseStatic('redis.' . $config);
        $tmp_args = $args;

        foreach ($args[$multi_key] as $k => $v) {
            $tmp_args[$multi_key] = $v;
            $tmp       = $this->getConfig($config, $tmp_args, $c_config);var_dump($tmp);echo 22;
            $key       = $tmp['key'];
            echo $key;
            $map[$key] = $v;
            $keys[]    = $key;
        }

        //批量获取数据
        $list = $this->getMulti($keys);
    
        //转换key
        foreach ($list as $k => $v) {
            $new_k = $map[$k];
            $out[$new_k] = $v;
        }
    
        return $out;
    }
    
    /**
     * incr数据
     * 
     * @param string $config config key
     * @param array	 $args   args
     * 
     * @return	bool
     */
    public function incrData($config, array $args) {
        $conf = $this->getConfig($config, $args);
        return $this->incr($conf['key']);
    }
    
    /**
     * decr数据
     * 
     * @param string $config config key 
     * @param array	 $args   args 
     * 
     * @return	array
     */
    public function decrData($config, array $args) {
        $conf = $this->getConfig($config, $args);
        return $this->decr($conf['key']);
    }
    
    /**
     * incrBy数据
     * 
     * @param string $config config key
     * @param array	 $args   args
     * @param mixed	 $value  value
     * 
     * @return	array
     */
    public function incrByData($config, array $args, $value) {
        $conf = $this->getConfig($config, $args);
        return $this->incrBy($conf['key'], $value);
    }
    
    /**
     * decrBy数据
     * 
     * @param string $config config key
     * @param array  $args   args 
     * @param mixed  $value  value
     * 
     * @return	bool
     */
    public function decrByData($config, array $args, $value) {
        $conf = $this->getConfig($config, $args);
        return $this->decrBy($conf['key'], $value);
    }
 
    /**
     * lPush
     * 批量 返回未成功的记录
     * 
     * @param string $config config key 
     * @param array  $args   args
     * @param string $value  value
     * 
     * @return string
     */
    public function lpushData($config, array $args, $value) {
    	$conf = $this->getConfig($config, $args);
    	return $this->lPush($conf['key'], $value);
    }
    
    /**
     * lPush
     * 批量 返回未成功的记录
     * 
     * @param string $config config key
     * @param array  $args   args 
     * @param string $values values
     * 
     * @return mixed
     */
    public function lpushDatas($config, array $args, $values) {
    	$conf = $this->getConfig($config, $args);
    	if (!is_array($values)) {
    		$values = array($values);
    	}
    	$arr_fail = array();
    	foreach ($values as $value) {
    		if (!$this->lpushData($conf['key'], $value)) {
    			$arr_fail[] = $value;
    		}
    	}
    	return $arr_fail;
    }
    
    /**
     * rPush
     * 批量 返回未成功的记录
     * 
     * @param string $config config key
     * @param array  $args   args 
     * @param string $values values 
     * 
     * @return mixed
     */
    public function rpushData($config, array $args, $values) {
    	$conf = $this->getConfig($config, $args);
    	return $this->rPush($conf['key'], $value);
    }
    
    /**
     * rPush
     * 批量 返回未成功的记录
     * 
     * @param string $config config key
     * @param array  $args   args 
     * @param string $values values
     * 
     * @return mixed
     */
    public function rpushDatas($config, array $args, $values) {
    	$conf = $this->getConfig($config, $args);
    	if (!is_array($values)) {
    		$values = array($values);
    	}
    	$arr_fail = array();
    	foreach ($values as $value) {
    		if (!$this->rpushData($conf['key'], $value)) {
    			$arr_fail[] = $value;
    		}
    	}
    	return $arr_fail;
    }
    
    /**
     * lPop
     * 
     * @param string $config config key
     * @param array  $args   args
     * 
     * @return string
     */
    public function lpopData($config, array $args) {
    	$conf = $this->getConfig($config, $args);
    	return $this->lPop($conf['key']);
    }
    
    /**
     * rPop
     * 
     * @param string $config config key
     * @param array  $args   args 
     * 
     * @return bool
     */
    public function rpopData($config, array $args) {
    	$conf = $this->getConfig($config, $args);
    	return $this->rPop($conf['key']);
    }
    
    /**
     * exists
     * 
     * @param string $config config key
     * @param array  $args   args 
     * 
     * @return bool
     */
    public function existsData($config, array $args) {
    	$conf = $this->getConfig($config, $args);
    	return $this->exists($conf['key']);
    }
    
    /**
     * sadd
     * 
     * @param string $config config key 
     * @param array  $args   args 
     * @param string $value  value
     * 
     * @return bool
     */
    public function saddData($config, array $args, $value) {
    	$conf = $this->getConfig($config, $args);
    	return $this->sadd($conf['key'], $value);
    }
    
    /**
     * srem
     * 
     * @param string $config config
     * @param array  $args   args
     * @param int    $value  value
     * 
     * @return bool
     */
    public function sremData($config, array $args, $value) {
    	$conf = $this->getConfig($config, $args);
    	 
    	return $this->srem($conf['key'], $value);
    }
    
    /**
     * sismember
     * 
     * @param string $config config
     * @param array  $args   agrs
     * @param int    $value  value
     * 
     * @return mixed
     */
    public function sismemberData($config, array $args, $value) {
    	$conf = $this->getConfig($config, $args);
    	return $this->sismember($conf['key'], $value);
    }
    
}
