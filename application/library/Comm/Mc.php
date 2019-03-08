<?php
/**
 * 缓存控制类(使用动态平台分布式缓存的中间层)
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */

class Comm_Mc{
    
    /**
     * Memcached对象 (如果没有Memcached，使用伪Memcached)
     * 
     * @var Memcached
     */
    protected $mc;
    
    /**
     * MC 前缀
     * 
     * @var string
     */
    public $pre = '';
    const BASIC           = 'SRV_MC_BASIC_SERVERS'; //基本信息
    const COUNTER         = 'SRV_MC_BASIC_SERVERS';//计数器
    
    /**
     * 构造方法，连接Memcache
     * 
     * @param string $params   CONST
     * @param bool   $use_sasl use sasl 
     * 
     * @return object
     */
    protected function __construct($params = self::BASIC, $use_sasl = false) {
        if (! class_exists ( 'Memcached' )) {
            $this->mc = false; // new Comm_Memcached();
            return $this->mc;
        }
        $this->mc = new Memcached ();
        
        // 一致性哈希
        $this->mc->setOption ( Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT );
        $this->mc->setOption ( Memcached::OPT_BINARY_PROTOCOL, true );
        $this->mc->setOption ( Memcached::OPT_LIBKETAMA_COMPATIBLE, true );
        $this->mc->setOption ( Memcached::OPT_TCP_NODELAY, true );
        
        // 自动failover配置
        $this->mc->setOption ( Memcached::OPT_SERVER_FAILURE_LIMIT, 1 );
        $this->mc->setOption ( Memcached::OPT_RETRY_TIMEOUT, 30 ); // 等待失败的连接重试的时间，单位秒
        $this->mc->setOption ( Memcached::OPT_AUTO_EJECT_HOSTS, true );
        
        // 超时设置
        $this->mc->setOption ( Memcached::OPT_CONNECT_TIMEOUT, 1000 );
        $this->mc->setOption ( Memcached::OPT_POLL_TIMEOUT, 1000 );
        $this->mc->setOption ( Memcached::OPT_SEND_TIMEOUT, 0 );
        $this->mc->setOption ( Memcached::OPT_RECV_TIMEOUT, 0 );
        
        // 认证
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
        $_SERVER = array_merge($_SERVER, Comm_Config::get("commmc.{$env}"));
        if ($use_sasl && isset ( $_SERVER[$params . '_USER'] ) && isset ( $_SERVER[$params . '_PASS'] ) && ! Helper_Debug::isDebug ()) {
            $this->mc->setSaslAuthData ( $_SERVER[$params . '_USER'], $_SERVER[$params . '_PASS'] );
        }
        
        try {
            // 连接服务器
            $servers = explode ( ' ', $_SERVER[$params] );
            $mc_servers = array ();
            foreach ( $servers as $val ) {
                $v = explode ( ':', $val );
                $mc_servers[] = array ($v[0],$v[1] );
            }
            $this->mc->addServers ( $mc_servers );
        } catch ( Exception $e ) {
            $content = sprintf ( 'MC Server连接失败：server name:%s,code:%s;msg:%s', $params, $e->getCode (), $e->getMessage () );
            throw new Exception_System ( 200101, $content );
        }
            
        return $this->mc;
    }
    
    /**
     * 初始化对象
     * 
     * @param string $params CONST 
     * @param bool   $flag   flag
     * 
     * @return Comm_Mc
     */
    static public function init($params = self::BASIC, $flag = true) {
        return new self ( $params, $flag );
    }
    
    /**
     * 写入一条MC缓存数据
     * 
     * @param string $config config
     * @param array  $args   args
     * @param mixed  $value  value
     * @param bool   $double 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return array
     */
    public function setData($config, array $args, $value, $double = 0) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $this->mc->set ( $cache_config['cache_key'], $value, $cache_config['expire'] );
    }
    
    /**
     * 安全写入缓存
     * 
     * @param float  $cas_token 安全的值
     * @param string $config    配置KEY
     * @param array  $args      KEY参数
     * @param mixed  $value     写入的值
     * @param int    $double    双写
     * 
     * @return boolean
     */
    public function casData($cas_token, $config, array $args, $value, $double = 0) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $this->mc->cas ( $cas_token, $cache_config['cache_key'], $value, $cache_config['expire'] );
    }
    
    /**
     * 获取MC一条数据
     * 
     * @param string $config    配置
     * @param array  $args      参数
     * @param string $cas_token 是否
     * 
     * @return mixed
     */
    public function getData($config, array $args = array(), &$cas_token = null) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $this->mc->get ( $cache_config['cache_key'], $cas_token );
    }
    
    /**
     * 获取一条mc的key
     * 
     * @param string $config    配置
     * @param array  $args      参数
     * @param string $cas_token 是否
     * 
     * @return mixed
     */
    public function getKey($config, array $args = array(), $cas_token = null) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $cache_config;
    }
    
    /**
     * 删除一条MC
     * 
     * @param string $config 配置标识
     * @param array  $args   参数值数据
     * @param bool   $double 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return string
     */
    public function deleteData($config, array $args = array(), $double = 0) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $this->mc->delete ( $cache_config['cache_key'] );
    }
    
    /**
     * 批量获取数据
     * 
     * @param string $config 配置
     * @param array  $args   批量参数（二维数组；或一维数据，里面每组arg用逗号隔开）
     * 
     * @return mixed
     */
    public function getMultiData($config, array $args) {
        $keys = array ();
        foreach ( $args as $arg ) {
            is_scalar ( $arg ) && $arg = explode ( ',', $arg );
            
            $cache_config = $this->getCacheConfig ( $config, $arg );
            $keys[] = $cache_config['cache_key'];
        }
        return $this->mc->getMulti ( $keys );
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
        // 初始化数据
        $map = $keys = $out = array ();
        
        // 生成key列表以及key的映射表
        $c_config = Comm_Config::getUseStatic ( 'cache.' . $config );
        $tmp_args = $args;
        foreach ( $args[$multi_key] as $k => $v ) {
            $tmp_args[$multi_key] = $v;
            $tmp = $this->getCacheConfig ( $config, $tmp_args, $c_config );
            $key = $tmp['cache_key'];
            $map[$key] = $v;
            $keys[] = $key;
        }
        
        // 批量获取数据
        $list = $this->mc->getMulti ( $keys );
        
        // 转换key
        foreach ( $list as $k => $v ) {
            $new_k = $map[$k];
            $out[$new_k] = $v;
        }
        
        return $out;
    }
    
    /**
     * 批量设置缓存
     *
     * @param string $config    cache.ini里的缓存名称
     * @param array  $args      数字索引，其单元依次对应$cache_ini的变量，其中有且仅有一个单元是数组,用于批量
     * @param int    $multi_key 数组单元的key，值是返回值数组的key
     * @param array  $data      data 
     * @param int    $mode      mode
     *
     * @return bool
     * @author baojun
     */
    public function mapSetMultiData($config, array $args, $multi_key, $data, $mode = 0) {
        if (! $data) {
            return false;
        }
        
        $c_config = Comm_Config::getUseStatic ( 'cache.' . $config );
        $mc_data = array (); // 用于批量设置的数据
        foreach ( $data as $k => $v ) {
            $args[$multi_key] = $k;
            $tmp = $this->getCacheConfig ( $config, $args, $c_config );
            $mc_data[$tmp['cache_key']] = $v;
        }
        
        return $this->mc->setMulti ( $mc_data, $c_config['expire'], $mode );
    }
    
    /**
     * 简化的批量获取数据，只支持一维数组的$args,但返回值会保持其对应关系
     *
     * @param string $cache_ini cache.ini里的缓存名称
     * @param array  $args      必须是一维数组，如 array(id_1, id_2, id_3 ...)
     *       
     * @return array 保持和$args的对应关系，若没有则那个单元不存在，不会赋值为falsh
     *        
     *         array(id_1 => $val_1, id_2 => $val_2)
     *        
     */
    public function simpleMultiData($cache_ini, array $args) {
        // 初始化数据
        $map = $keys = $out = array ();
        
        // 生成key列表以及key的映射表
        foreach ( $args as $val ) {
            $tmp = $this->getCacheConfig ( $cache_ini, array ($val ) );
            $key = $tmp['cache_key'];
            $map[$key] = $val;
            $keys[] = $key;
        }
        
        // 批量获取数据
        $list = $this->mc->getMulti ( $keys );
        
        // 转换key
        foreach ( $list as $k => $v ) {
            $new_k = $map[$k];
            $out[$new_k] = $v;
        }
        
        return $out;
    }
    
    /**
     * 批量设置Data
     * 
     * @param config $config 配置
     * @param array  $data   二维数据，KEY为参数，多个用逗号隔开，value为要设置的值
     * @param bool   $double 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return boolean
     */
    public function setMultiData($config, array $data, $double = 0) {
        if (! $data) {
            return false;
        }
        
        $mc_data = array ();
        foreach ( $data as $arg => $value ) {
            $arg = explode ( ',', $arg );
            $cache_config = $this->getCacheConfig ( $config, $arg );
            $mc_data[$cache_config['cache_key']] = $value;
        }
        
        return $this->mc->setMulti ( $mc_data, $cache_config['expire'] );
    }
    
    /**
     * memcached 计数器
     * 
     * @param string $config   config 
     * @param array  $args     args 
     * @param int    $offset   offset 
     * @param bool   $auto_set 是否打开双写，默认打开，如果需要单写传值０即可
     */
    public function incre($config, $args, $offset, $auto_set = false) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        if ($auto_set && $this->mc->get ( $cache_config['cache_key'] ) === false) {
            $result = $this->mc->set ( $cache_config['cache_key'], 0, $cache_config['expire'] );
        } else {
            $result = $this->mc->increment ( $cache_config['cache_key'], $offset );
        }
        return $result;
    }
    
    /**
     * memcached 计数器
     * 
     * @param string $config   config 
     * @param array  $args     args 
     * @param int    $offset   offset 
     * @param bool   $auto_set 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return mixed
     */
    public function decre($config, $args, $offset, $auto_set = false) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        if ($auto_set && $this->mc->get ( $cache_config['cache_key'] ) === false) {
            $result = $this->mc->set ( $cache_config['cache_key'], 0, $cache_config['expire'] );
        } else {
            $result = $this->mc->decrement ( $cache_config['cache_key'], $offset );
        }
        return $result;
    }
    
    /**
     * mc 计数器,增加计数并返回新的计数
     * 
     * @param string $config 计数器
     * @param int    $args   计数增量,可为负数.0为不改变计数
     * @param int    $offset 时间
     * @param bool   $double 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return int/false 失败是返回false,成功时返回更新计数器后的计数
     */
    public function setCounter($config, $args, $offset, $double = 0) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        
        $val = $this->mc->get ( $cache_config['cache_key'] );
        if (! is_numeric ( $val ) || $val < 0) {
            $ret = $this->set ( $cache_config['cache_key'], 0, $cache_config['expire'] );
            $val = 0;
            if (! $ret) {
                return false;
            }
        }
        
        $offset = intval ( $offset );
        if ($offset > 0) {
            return $this->mc->increment ( $cache_config['cache_key'], $offset );
        } elseif ($offset < 0) {
            return $this->mc->decrement ( $cache_config['cache_key'], - $offset );
        }
        return $val;
    }
    
    /**
     * 替换缓存中的数据
     * 
     * @param string $config config
     * @param array  $args   args 
     * @param mixed  $value  value 
     * @param bool   $double 是否打开双写，默认打开，如果需要单写传值０即可
     * 
     * @return type
     */
    public function replaceData($config, array $args, $value, $double = 0) {
        $cache_config = $this->getCacheConfig ( $config, $args );
        return $this->mc->set ( $cache_config['cache_key'], $value, $cache_config['expire'] );
    }
    
    /**
     * 获取某一缓存的配置
     * 
     * @param string $config config
     * @param array  $args   args
     * 
     * @return array
     */
    public function getCacheConfig($config, array $args) {
        $cache_config = Comm_Config::getUseStatic ( 'cache.' . $config );
        $param_arr = array ($cache_config['key'] );
        $param_arr = array_merge ( $param_arr, $args );
        $cache_key = call_user_func_array ( 'sprintf', $param_arr );

        // TODO: 暂时不使用key前缀
//        if (isset ( $_SERVER['SRV_MC_KEY_PREFIX'] )) {
//            $this->pre = $_SERVER['SRV_MC_KEY_PREFIX'] . "_";
//        }
        
        $cache_config['cache_key'] = $this->pre . $cache_key;
        return $cache_config;
    }
    
    /**
     * 魔术方法，调用中间层的原生方法
     *
     * @param string $func  func 
     * @param array  $param param 
     * 
     * @return mixed
     */
    public function __call($func, $param) {
        return call_user_func_array ( array ($this->mc,$func ), $param );
    }
}
