<?php
/**
 * 数据库管理类
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 * @modify  gujun <gujun@yixia.com>
 */

class Comm_Db{

    const DB_BASIC = 'basic';//basic
	
    /**
     * 数据库操作对象
     * @var Cls_db_Base
     */

    static $db = array();

    /**
     * 是否快速的断开数据库连接
     * @var bool
     */
    public static $quick_break_off = false;
    
    /**
     * 是否长连接
     * @var bool
     */
    public static $persistent = false;

    /**
     * 取得数据库操作对象（Database）
     *   
     * @param string $id        唯一数据标识
     * @param string $peristent 是否长链接
     * @param int    $hash_key  哈希值
     * 
     * @return Cls_db_Base|boolean|Comm_Dbbase
     */
    static public function d( $id , $peristent = false, $hash_key = 0, $hash_mod = 0 ) {
		//若为设置快速断开，尝试取以前的dbbase对象
        if ( !self::$quick_break_off && isset(self::$db[$id]) && $hash_mod <= 0) {
            return self::$db[$id];
        }
        
        //长连接
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
        $_SERVER = array_merge($_SERVER, Comm_Config::get("commdb.{$env}"));$db = self::add(self::dbConfig($id, $hash_key, $hash_mod));
        
		//若未设置快速断开数据库，保存数据库连接对象
		if ( !self::$quick_break_off && $hash_mod <= 0) {
			self::$db[$id] = $db;
		}
		
        return $db;
    }

    /**
     * db common config
     *
     * @param string $id 数据库标识
     * @param int $hash_key hash key
     *
     * @return array
     * @throws Exception_Db
     */
	static protected function dbConfig($id, $hash_key = 0, $hash_mod = 0) {
	    if (!isset($_SERVER['SRV_DB_' . strtoupper($id) . '_HOST']) || !isset($_SERVER['SRV_DB_' . strtoupper($id) . '_HOST_R'])) {
	        throw new Exception_Db('200407', 'Db config error, pls check server config file.', array('id' => $id));
	    }
	    return array(
	            Comm_Dbbase::CONN_MASTER => array(
	                    'host'		=> $_SERVER['SRV_DB_' . strtoupper($id) . '_HOST'],
	                    'port'		=> $_SERVER['SRV_DB_' . strtoupper($id) . '_PORT'],
	                    'username'	=> $_SERVER['SRV_DB_' . strtoupper($id) . '_USER'],
	                    'password'	=> $_SERVER['SRV_DB_' . strtoupper($id) . '_PASS'],
	                    'database'	=> $hash_mod == 0 ? self::dSubHash($_SERVER['SRV_DB_' . strtoupper($id) . '_NAME'], $hash_key) : self::dSubMod($_SERVER['SRV_DB_' . strtoupper($id) . '_NAME'], $hash_key, $hash_mod),
	                    'charset'	=> 'utf8',
	                    'type'      => $_SERVER['SRV_DB_' . strtoupper($id) . '_TYPE'],
	            ),
	            Comm_Dbbase::CONN_SLAVE => array(
	                    'host'		=> $_SERVER['SRV_DB_' . strtoupper($id) . '_HOST_R'],
	                    'port'		=> $_SERVER['SRV_DB_' . strtoupper($id) . '_PORT_R'],
	                    'username'	=> $_SERVER['SRV_DB_' . strtoupper($id) . '_USER_R'],
	                    'password'	=> $_SERVER['SRV_DB_' . strtoupper($id) . '_PASS_R'],
	                    'database'	=> $hash_mod == 0 ? self::dSubHash($_SERVER['SRV_DB_' . strtoupper($id) . '_NAME_R'], $hash_key) : self::dSubMod($_SERVER['SRV_DB_' . strtoupper($id) . '_NAME'], $hash_key, $hash_mod),
	                    'charset'	=> 'utf8',
	                    'type'      => $_SERVER['SRV_DB_' . strtoupper($id) . '_TYPE'],
	            )
	    );
	}

	/**
	 * 新增一数据连接标识
	 *
	 * @param array $conf 数据库配置信息
	 *
	 * @return Comm_Dbbase
	 */
	static public function add($conf) {
	    return new Comm_Dbbase($conf,  self::$persistent);
	}
	
    /**
     * 获取表名，加上前缀
     * 
     * @param string $table_base table basic 
     * 
     * @return string
     */
    static public function t($table_base) {
        return Comm_Config::get('app.db.tb_prefix') . $table_base;
    }

    /**
     * 获取库名,加前缀并哈希，得分库名称
     *
     * @param string $db_base 未加前缀和序号后缀的库名称
     * @param int $n 分库维度的数值，如uid
     * @param int $db_n 总分库数量
     * @param int $table_n 单库分表数量
     *
     * @return string
     */
    static public function dSubHash($db_base, $n, $db_n = 8, $table_n = 32) {
        $db = $db_base;
        if ($n > 0 && $db_n > 0) {
            $idx = $n % ( $db_n * $table_n );
            $idx = intval( $idx / $table_n, 10);
//            $idx = dechex($n % $db_n);
            $idx = sprintf('%04s', $idx);
            $db .= '_' . $idx;
        }
    
        return $db;
    }
    
    /**
     * 获取库名,加前缀并哈希，得分库名称
     *
     * @param string $db_base 未加前缀和序号后缀的库名称
     * @param int $n 分库维度的数值，如uid
     * @param int $db_n 总分库数量
     *
     * @return string
     */
    static public function dSubMod($db_base, $n, $db_n = 8) {
        $db = $db_base;
        $idx = 0;
        if ($n > 0 && $db_n > 0) {
            $idx += $n % $db_n;
            $len = strlen($db_n);
            $idx = sprintf("%0{$len}s", $idx);
            $db .= '_' . $idx;
        }
    
        return $db;
    }
    
    /**
     * 获取表名,加前缀并哈希，得分表名称
     * 
     * @param string $table_base 未加前缀和序号后缀的表名称
     * @param int    $n          分表维度的数值，如uid
     * @param int    $table_n    总分表数量
     * 
     * @return string
     */
    static public function tSubHash($table_base, $n, $table_n = 256) {
        $table = self::t($table_base);
        if ($n > 0 && $table_n > 0) {
            $idx = dechex($n % $table_n);
            !isset($idx{1}) && $idx = '0' . $idx;
            $table .= '_' . $idx;
        }

        return $table;
    }

    /**
     * 获取分表方法(按年月日hash)
     * 
     * @param unknown  $table_base table basic name
     * @param DateTime $date       date 
     * 
     * @return string
     */
    static public function tSubDate($table_base, DateTime $date, $is_drds = false) {
        $idx = 0;
        $y   = $date->format('Y');
        $m   = $date->format('m');
        if ($is_drds && $m == 12) {
            $idx = '0';
        } else {
            $idx = $m;
        }
        $idx = sprintf("%02s", $idx);
        //$idx = $y . '_' . $idx;
        
        //拼表名
        if ($is_drds) {
            $table = self::t($table_base) . '_'. $y . '_' . $idx;
        } else {
            $table = self::t($table_base) . '_' . $y . $idx;
        }
        
        return $table;
    }
    
    /**
     * 获取分表方法(按年月日hash)
     * 
     * @param unknown  $table_base table basic name
     * @param DateTime $date       date 
     * 
     * @return string
     */
    static public function tSubDay($table_base, DateTime $date) {
        //拼表名
        $table = self::t($table_base) . $date->format('_ymd');
        return $table;
    }
    
    /**
     * 获取分表方法(按年月hash)
     *
     * @param unknown  $table_base table basic name
     * @param DateTime $date       date
     *
     * @return string
     */
    static public function tSubMonth($table_base, DateTime $date) {
        //拼表名
        $table = self::t($table_base) . $date->format('_ym');
        return $table;
    }
    

    /**
     * 获取分表方法(按UUID hash)
     * 
     * @param unknown $table_base table basic name
     * @param unknown $uuid       uuid
     * 
     * @return string
     */
    static public function tSubUuid($table_base, $uuid) {
        $time = new DateTime('@' . Comm_Uuid::getTime($uuid));
        return self::tSubDate($table_base, $time);
    }

    /**
     * 获取表名,加前缀并取模，得分表名称
     *
     * @param string $table_base 未加前缀和序号后缀的表名称
     * @param int    $n          分表维度的数值，如uid
     * @param int    $table_n    总分表数量
     *
     * @return string
     */
    static public function tSubMod($table_base, $n, $table_n = 256) {
        $table = self::t($table_base);
        $idx = 0;
        if ($n > 0 && $table_n > 0) {
            $idx += $n % $table_n;
            $len = strlen($table_n);
            $idx = sprintf("%0{$len}s", $idx);
            $table .= '_' . $idx;
        }
    
        return $table;
    }
    
}
