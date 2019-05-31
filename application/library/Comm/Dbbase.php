<?php
/**
 * 数据库底层操作
 *
 * @package base
 * @author  Chengxuan <chengxuan@staff.sina.com.cn>
 */
class Comm_Dbbase {

    /**
     * 主库连接对象
     * @var PDO
     */
    protected $pdo_master;

    /**
     * 从库连接对象
     * @var PDO
     */
    protected $pdo_slave;

    /**
     * 返回类型（所有）
     * @var int
     */

    const FETCH_BOTH = 0;

    /**
     * 返回类型（数字索引）
     * @var int
     */
    const FETCH_NUM = 1;

    /**
     * 返回类型（字段索引）
     * @var int
     */
    const FETCH_ASSOC = 2;

    /**
     * 返回类型（类）
     * @var int
     */
    const FETCH_CLASS = 8;
    
    /**
     * 操作方式（插入）
     * @var string
     */
    const TYPE_INSERT = 'INSERT INTO';

    /**
     * 操作方式（忽略插入）
     * @var string
     */
    const TYPE_INSERT_IGNORE = 'INSERT IGNORE INTO';

    /**
     * 操作方式（更新）
     * @var string
     */
    const TYPE_UPDATE = 'UPDATE';

    /**
     * 操作方式（替换插入）
     * @var string
     */
    const TYPE_REPLACE = 'REPLACE INTO';

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
    
    /**
     * 超时时间
     * @var int
     */
    protected $timeout = 5;

    /**
     * 最后一条SQL语句
     * @var string
     */
    public $sql = '';

    /**
     * 数据库配置（格式参数classes/db.php  function:add）
     * @var array
     */
    public $conf = array();
    
    /**
     * 是否长连接
     * @var bool
     */
    public $persistent = false;

    /**
     * 构造方法
     * 
     * @param array   $conf       数据库连接配置 
     * @param boolean $persistent 是否长链接
     * 
     * @return void
     */
    public function __construct($conf, $persistent = false) {
        $this->conf = $conf;
        $this->persistent = $persistent;
    }

    /**
     * 取得插入的最后一条的ID
     */
    public function lastId() {
        return $this->fetchOne('SELECT LAST_INSERT_ID()', null, null, true);
    }

    /**
     * 取得指定数据表的字段信息
     * 
     * @param string $table 数据表名
     * 
     * @return array
     */
    public function columns($table) {
        $table = str_replace('`', '\\`', $table);
        $sql = "SHOW COLUMNS FROM `{$table}`";
        return $this->fetchAll($sql);
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
     * @return mixed
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
     * 自动执行操作(针对Insert/Update/Replace操作)
     * 
     * @param string $type  Dbbase::TYPE_*
     * @param string $table table 
     * @param array  $data  data 
     * @param string $where where 
     * @param array  $param param 
     * @param string $limit limit 
     * 
     * @return PDOStatement
     */
    public function autoExecute($type, $table, $data, $where = '', $param = null, $limit = null) {
        if (!$data || !in_array($type, array(self::TYPE_INSERT, self::TYPE_INSERT_IGNORE, self::TYPE_UPDATE, self::TYPE_REPLACE))) {
            return false;
        }

        //整理新数据
        $set = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value[0] = strtr($value[0], "?", addslashes($value[1]));
                $set[] = "`{$key}` = {$value[0]}";
            } else {
                if ($value === null) {
                    $set[] = "`{$key}` = NULL";
                } else {
                    $value = addslashes($value);
                    $set[] = "`{$key}` = '{$value}'";
                }
            }
        }
        $set = implode(',', $set);
        unset($data);

        $sql = "{$type} `{$table}` SET {$set}";
        $where && $sql .= " WHERE {$where}";

        if ($limit) {
            if (!Helper_Validator::limit($limit)) {
                $this->throwError('LIMIT 字段格式错误');
                return false;
            }

            $sql .= " LIMIT {$limit} ";
        }

        return $this->query($sql, $param);
    }

    /**
     * 通过SQL语句，确定使用的服务器
     * 
     * @param string $sql sql
     * 
     * @return int
     */
    protected function getServer($sql) {
        return substr(strtolower(trim($sql)), 0, 6) == 'select' ? self::CONN_SLAVE : self::CONN_MASTER;
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
     * 连接数据库
     * 
     * @param unknown $host     域名
     * @param unknown $username 用户名
     * @param unknown $password 密码
     * @param unknown $database 数据库名
     * @param number  $port     端口
     * @param string  $charset  编码
     * 
     * @throws Exception_System
     * @return PDO
     */
    public function connect($host, $username, $password, $database, $port = 3306, $charset = 'utf8', $type = 'mysql') {
        try {
            switch ($type) {
                case 'mysql':
                    $dsn = "mysql:dbname={$database};host={$host};port={$port}";
                    break;
                case 'oracle':
                    $dsn = "oci" . ':dbname=' . "{$host}:{$port}/{$database};charset=$charset";
                    break;
                default:
                    $dsn = "mysql:dbname={$database};host={$host};port={$port}";
                    break;
            }
            //print_r($dsn);
            $opts = array(
            		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //异常用try catch捕获，也可以手动抛出异常 PDOException
            		PDO::ATTR_TIMEOUT => $this->timeout, //设置超时时间
            		//PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'' ,
            );
            if ($this->persistent) {
                $opts[PDO::ATTR_PERSISTENT] = true;
            }
            $pdo = new PDO($dsn, $username, $password, $opts);
            if ($type == 'mysql') {
                $charset && $sql_init = "SET NAMES {$charset};";
                $pdo->query($sql_init);
            }
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception_System(200101, "DB连接失败", array('pdo_code' => $e->getCode(), 'pdo_msg' => $e->getMessage()));
        }
    }

    /**
     * 测试连接是否存在，如果不存在，重新连接
     * 
     * @param string/int $server 根据SQL语句自动选择或强制指定数据库
     * 
     * @return PDO
     */
    public function getPdo($server) {
        !is_int($server) && $server = $this->getServer($server);
        if ($server == self::CONN_MASTER) {
            $pdo = & $this->pdo_master;
        } else {
            $pdo = & $this->pdo_slave;
        }

        if (!$pdo || $this->agian) {
            $conf = $this->getConf($server);
            $pdo = $this->connect($conf['host'], $conf['username'], $conf['password'], $conf['database'], $conf['port'], $conf['charset'], $conf['type']);
        }

        return $pdo;
    }

    /**
     * 查询一句语句，反回查询句柄
     * 
     * @param string  $sql       SQL语句
     * @param array   $param     变量参数
     * @param boolean $is_master 是否强制使用主库
     * 
     * @return PDOStatement
     */
    public function query($sql, $param = null, $is_master = false) {
        $this->sql = $sql;
        $server = ($is_master || $this->getServer($this->sql) == self::CONN_MASTER) ? self::CONN_MASTER : self::CONN_SLAVE;

        $pdo = $this->getPdo($server);
        if (!$pdo instanceof PDO)
            return false;

        $param && !is_array($param) && $param = (array)$param;
        $statement = $pdo->prepare($this->sql);
        $query = $statement->execute($param);

        if (!$query) {
            if (!$this->agian) { //不是首次执行，重试
                $this->agian = true;
                list ($code, $sql_code, $info) = $statement->errorInfo();
                if ($sql_code == 2006) { //MySQL server has gone away
                    return $this->query($sql, $param, $is_master);
                }
            }
            $this->error($statement);
        }

        $this->agian = false;
        return $statement;
    }

    /**
     * 查询一条语句，返回影响的数据行数
     * 
     * @param string  $sql       SQL语句
     * @param array   $param     变量参数
     * @param boolean $is_master 是否强制使用主库
     * 
     * @return int
     */
    public function execute($sql, $param = null, $is_master = false) {
        $statement = $this->query($sql, $param, $is_master);
        return $statement->rowCount();
    }

    /**
     * 取得一个数据
     * 
     * @param string  $sql       SQL语句
     * @param array   $param     变量参数
     * @param string  $column    指定返回字段
     * @param boolean $is_master 是否强制使用主库
     * 
     * @return string/boolean
     */
    public function fetchOne($sql, $param = null, $column = null, $is_master = false) {
        if (!$column || is_numeric($column)) {
            $query = $this->query($sql, $param, $is_master);
            return $query ? $query->fetchColumn((int)$column) : $query;
        } else {
            $result = $this->fetchRow($sql, $param, $is_master, self::FETCH_ASSOC);
            return isset($result[$column]) ? $result[$column] : false;
        }
    }

    /**
     * 取得一行数据
     * 
     * @param string  $sql        SQL语句
     * @param array   $param      变量参数
     * @param boolean $is_master  是否强制使用主库
     * @param int     $fetch_type 指定返回数据格式(Dbbase::FETCH_*)
     * 
     * @return array/boolean
     */
    public function fetchRow($sql, $param = null, $is_master = false, $fetch_type = self::FETCH_ASSOC) {
        $query = $this->query($sql, $param, $is_master);
        if ($query instanceof PDOStatement) {
            $result = $query->fetch($this->f($fetch_type));
            !$result && $result = array();
            return $result;
        } else {
            return $query;
        }
    }

    /**
     * 取得一列数据
     * 
     * @param string      $sql       SQL语句
     * @param array       $param     变量参数
     * @param null/string $column    指定返回字段
     * @param boolean     $is_master 是否强制使用主库
     * 
     * @return array
     */
    public function fetchCol($sql, $param = null, $column = null, $is_master = false) {
        $query = $this->query($sql, $param, $is_master);
        if ($query instanceof PDOStatement) {
            $result = $query->fetchAll(PDO::FETCH_COLUMN, $column);
            !$result && $result = array();
            return $result;
        } else {
            return $query;
        }
        //		$result = $this->fetch_all($sql, $param, $is_master, $fetch_type);
        //		return $result ? Helper_Array::cols($result, $column) : $result;
    }

    /**
     * 取得所有数据
     * 
     * @param string  $sql        SQL语句
     * @param array   $param      变量参数
     * @param boolean $is_master  是否强制使用主库
     * @param int     $fetch_type 指定返回数据格式(Dbbase::FETCH_*)
     * 
     * @return array
     */
    public function fetchAll($sql, $param = null, $is_master = false, $fetch_type = self::FETCH_ASSOC) {
        $query = $this->query($sql, $param, $is_master);
        return $query ? $query->fetchAll($this->f($fetch_type)) : $query;
    }

    /**
     * 取得SELECT查询的数据行数
     * 
     * @param PDOStatement $statement statement 
     * 
     * @return int/boolean
     */
    public function numRows($statement) {
        if (!$statement instanceof PDOStatement) {
            return false;
        }

        return $statement->rowCount();
    }

    /**
     * 转换类型，将基类的数据类型转换为PDO的
     * 
     * @param int $self_fetch fetch 
     * 
     * @return int
     */
    protected function f($self_fetch) {
        static $conf = array(self::FETCH_ASSOC => PDO::FETCH_ASSOC, self::FETCH_BOTH => PDO::FETCH_BOTH, self::FETCH_NUM => PDO::FETCH_NUM, self::FETCH_CLASS => PDO::FETCH_OBJ);

        return isset($conf[$self_fetch]) ? $conf[$self_fetch] : PDO::FETCH_ASSOC;
    }

    /**
     * 处理PDO错误
     * 
     * @param PDO $pdo PDO对象
     */
    public function error($pdo) {
        $arr = $pdo->errorInfo();
        throw new Exception_Db('200407', null, array('pdo_code' => $arr[0], 'db_code' => $arr[1], 'db_error' => $arr[2], 'sql' => $this->sql));
    }

    /**
     * 插入数据
     * 
     * @param string $table  表名
     * @param array	 $data   要更新的数据Array($k1=>$v1, $k2=>$v2)
     * @param bool   $ignore 是否忽略插入
     * 
     * @return int	影响行数
     */
    public function insert($table, $data, $ignore = false) {
        $type = !$ignore ? self::TYPE_INSERT : self::TYPE_INSERT_IGNORE;
        $ret = $this->autoExecute($type, $table, $data);
        return $ret instanceof PDOStatement ? $ret->rowCount() : false;
    }

    /**
     * 覆盖插入数据
     *
     * @param string $table 表名
     * @param array  $data  要更新的数据Array($k1=>$v1, $k2=>$v2)
     *
     * @return int
     */
    public function replace($table, $data) {
        $ret = $this->autoExecute(self::TYPE_REPLACE, $table, $data);
        return $ret instanceof PDOStatement ? $ret->rowCount() : false;
    }

    /**
     * 更新数据
     * 
     * @param string $table 表名
     * @param array	 $data  要插入的数据Array($k1=>$v1, $k2=>$v2)
     * @param string $where sql语句的where部分(不包含where关键字)
     * @param array	 $param 参数
     * @param sting  $limit limit 
     * 
     * @return	int		影响行数
     */
    public function update($table, $data, $where = '', $param = '', $limit = null) {
        $ret = $this->autoExecute(self::TYPE_UPDATE, $table, $data, $where, $param, $limit);
        return $ret instanceof PDOStatement ? $ret->rowCount() : false;
    }

    /**
     * 批量插入
     *
     * @param unknown $sql       sql 
     * @param string  $param     param  
     * @param string  $is_master is master 
     * 
     * @return boolean|unknown
     */
    public function insertBatch($sql, $param = null, $is_master = false) {
    	$this->sql = $sql;
    	$server = ($is_master || $this->getServer($this->sql) == self::CONN_MASTER) ? self::CONN_MASTER : self::CONN_SLAVE;
    
    	$pdo = $this->getPdo($server);
    	if (!$pdo instanceof PDO)
    		return false;
    
		//$pdo->beginTransaction(); // also helps speed up your inserts.
    
		$param && !is_array($param) && $param = (array)$param;
		$statement = $pdo->prepare($this->sql);
		$query = $statement->execute($param);
		//$pdo->commit();
		
		if (!$query) {
			if (!$this->agian) { //不是首次执行，重试
				$this->agian = true;
				list ($code, $sql_code, $info) = $statement->errorInfo();
				if ($sql_code == 2006) { //MySQL server has gone away
					return $this->query($sql, $param, $is_master);
				}
			}
			$this->error($statement);
		}
    	
		return $statement instanceof PDOStatement ? $statement->rowCount() : false;
    }
}
