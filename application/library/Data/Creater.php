<?php

/**
 * 发号器数据层
 *
 * @package   data
 * @author    张宝军 <baojun4545@sina.com>
 * @copyright 2016 Yixia.com all rights reserved
 */

class Data_Creater extends Abstract_Data{

    const TYPE_FOLLOW = '1';//关注
    const TYPE_MARK   = '2';//赞
    const TYPE_USER   = '3';//用户
    const TYPE_FEED   = '4';//FEED
    const TYPE_CHANNEL= '5';//视频
    const TYPE_MSG    = '6';//消息系统

    /**
     * create table tpl
     * 
     * @var string
     */
    protected static $CRTSQL = "CREATE TABLE IF NOT EXISTS `%s` (
  		`%s` int(10) unsigned NOT NULL AUTO_INCREMENT,
  		PRIMARY KEY (`%s`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8"; // 数据表建表语句
    
    /**
     * config
     * 
     * @var array
     */
    protected static $conf = array (
        self::TYPE_FOLLOW => array ('table_base' => 'follow', 'column' => 'id', 'limit' => 10000), 
        self::TYPE_MARK   => array ('table_base' => 'mark', 'column'   => 'id', 'limit' => 10000),
        self::TYPE_USER   => array ('table_base' => 'user', 'column'   => 'id', 'limit' => 10000),
        self::TYPE_FEED   => array ('table_base' => 'feed', 'column'   => 'id', 'limit' => 10000),
        self::TYPE_CHANNEL=> array ('table_base' => 'channel', 'column'   => 'id', 'limit' => 10000),
        self::TYPE_MSG    => array ('table_base' => 'msg', 'column'   => 'id', 'limit' => 10000),
    );

    /**
     * 重置计数
     * 
     * @param integer $bid   业务号
     * @param integer $count 初始计数
     * 
     * @return boolean
     */
    public function init($bid, $count) {
        if (empty(self::$conf[$bid])) {
            return false;
        }
        $conf = self::$conf[$bid];
        $table_name = Comm_Db::t($conf['table_base']);
        $db = Comm_Db::d(Comm_Db::DB_CREATER);
        $db->beginTransaction();
        $sql = "TRUNCATE TABLE `{$table_name}`";
        try {
            $ret = $db->execute($sql);
        } catch ( Exception $e ) {
            $meta_data = $e->getMetadata();
            $db_code = $meta_data['db_code'];
            if ($db_code != 1146)
                return false;
            $ret = self::_createTable($db, $table_name, $conf['column']);
            if ($ret === false)
                return false;
        }
        $sql = "ALTER TABLE `{$table_name}` SET AUTO_INCREMENT={$count}";
        $ret = $db->execute($sql);
        if ($ret === false)
            return false;
        $db->commit();
        unset($db);
        return true;
    }

    /**
     * 自加计数
     * 
     * @param integer $bid 业务号
     * 
     * @return integer
     */
    public function increment($bid) {
        if (empty(self::$conf[$bid])) {
            return false;
        }
        $conf = self::$conf[$bid];
        $table_name = Comm_Db::t($conf['table_base']);
        $db = Comm_Db::d(Comm_Db::DB_CREATER);
        $sql = "INSERT INTO `{$table_name}` SET `{$conf['column']}`=0";
        try {
            $ret = $db->execute($sql);
        } catch ( Exception $e ) {
            $meta_data = $e->getMetadata();
            $db_code = $meta_data['db_code'];
            if ($db_code == 1062) { // 超过了int的最大值
                self::init($bid, 1);
                $ret = $db->execute($sql);
                if ($ret === false)
                    return false;
            } else {
                if ($db_code != 1146) {
                    return false;
                }
                $ret = self::_createTable($db, $table_name, $conf['column']);
                if ($ret === false)
                    return false;
                $ret = $db->execute($sql);
                if ($ret === false)
                    return false;
            }
        }
        $last_id = $db->lastId();
        if ($last_id % $conf['limit'] == 0) { // 只存x条发号数据
            $sql = "DELETE FROM `{$table_name}`";
            $db->execute($sql);
        }
        $count = $last_id;
        
        return $count;
    }

    /**
     * 创建表
     * 
     * @param object $db     数据库实例化对象
     * @param string $table  表名
     * @param string $column 字段
     * 
     * @return boolean
     */
    private function _createTable($db, $table, $column) {
        $sql = sprintf(self::$CRTSQL, $table, $column, $column);
        $ret = $db->execute($sql);
        
        return $ret;
    }
}
