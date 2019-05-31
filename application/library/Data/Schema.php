<?php

/**
 * 数据库Schema相关操作
 *
 * @package Data
 * @author  chengxuan <chengxuan@staff.sina.com.cn>
 */
//INNODB、unsigened、NOT NULL、主键
class Data_Schema extends Abstract_Data {
    
    /**
     * 数据库ID
     * 
     * @var string
     */
    private $_db_id;
    
    /**
     * 数据库名称
     * 
     * @var string
     */
    private $_dbname = '';
    
    /**
     * 构造方法
     * 
     * @param string $db_id 数据库连接ID(Comm_Db::DB_*)
     * 
     * @return void
     *
     * @author chengxuan
     */
    public function __construct($db_id) {
        $this->_db_id = $db_id;
        $this->setDatabase();
    }
    
    /**
     * 设置数据库名至当前对象属性中
     * 
     * @return void
     *
     * @author chengxuan
     */
    protected function setDatabase() {
        $this->_dbname = Comm_Db::d($this->_db_id)->fetchOne('SELECT DATABASE()');
    }
    

    /**
     * 获取所有数据表
     * 
     * @return array
     *
     * @author chengxuan
     */
    public function fetchTables() {
        $sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY LENGTH(TABLE_NAME) ASC, TABLE_NAME ASC';
        return Comm_Db::d($this->_db_id)->fetchAll($sql, array($this->_dbname));
    }
    
    /**
     * 获取一张数据表信息
     * 
     * @param string $table 表名
     * 
     * @return array
     *
     * @author chengxuan
     */
    public function fetchTable($table) {
        $sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
        return Comm_Db::d($this->_db_id)->fetchRow($sql, array($this->_dbname, $table));
    }
    
    /**
     * 获取某一表的全部索引
     * 
     * @param string $table 表名
     * 
     * @return array
     */
    public function fetchIndexs($table) {
        $sql = 'SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
        return Comm_Db::d($this->_db_id)->fetchAll($sql, array($this->_dbname, $table));
    }
    
    /**
     * 获取一个表的所有字段
     * 
     * @param string $table 表名
     * 
     * @return mixed
     */
    public function fetchColumns($table) {
        $sql = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
        return Comm_Db::d($this->_db_id)->fetchAll($sql, array($this->_dbname, $table));
    }
    
    /**
     * 根据指定的规则，获取表名
     * 
     * @param string $table_match 表匹配写法（数据库的LIKE写法）
     * 
     * @return array
     *
     * @author chengxuan
     */
    public function matchTables($table_match) {
        return Comm_Db::d($this->_db_id)->fetchCol('SHOW TABLES LIKE ?', $table_match);
    }
    

    
}