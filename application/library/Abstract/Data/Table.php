<?php

/**
 * 通用标操作
 *
 * @package Abstract
 * @author  张宝军 <baojun4545@sina.com>
 */
class Abstract_Data_Table extends Abstract_Data {
    protected $table = '';
    protected $db = '';

    /**
     * 构造函数
     *
     * @param string $table 表名
     * @param string $db    库名 Comm_Db::DB_xxx
     *
     * @return void
     */
    protected function __construct($table, $db=Comm_Db::DB_BASIC) {
        $this->table = Comm_Db::t($table);
        $this->db = $db;
    }

    /**
     * 获取数据
     *
     * @param string $where    条件
     * @param array  $param    参数
     * @param bool   $is_cache 是否缓存结果
     *
     * @return type
     */
    public function fetchAll($where, $param=array(), $is_cache=true) {

        $sql = "SELECT * FROM ".$this->table.' WHERE '.$where;
        $list = $this->getDb()->fetchAll($sql, $param);


        return $list;
    }

    /**
     * 插入数据
     * 
     * @param array $data 数据，字段名对应数据
     *
     * @return int id
     */
    public function insert($data) {
        $db = $this->getDb();
        $ret = $db->insert($this->table, $data, true);
        $id = $ret ? $db->lastId():0;

        return $id;
    }

    /**
     * 修改
     *
     * @param array  $data  数据
     * @param string $where 条件
     * @param array  $param 参数
     *
     * @return int
     */
    public function update($data, $where='', $param=array()) {
        return $this->getDb()->update($this->table, $data, $where, $param);
    }

    /**
     * 删除
     *
     * @param string $where 条件
     * @param array  $param 参数
     *
     * @return int
     */
    public function delete($where='', $param=array()) {
        $sql = "DELETE FROM ".$this->table." WHERE ".$where;
        return $this->getDb()->execute($sql, $param);
    }


    /**
     * 获取数据库操作对象
     *
     * @return Comm_Dbbase
     */
    protected function getDb() {
        return Comm_Db::d($this->db);
    }

}

