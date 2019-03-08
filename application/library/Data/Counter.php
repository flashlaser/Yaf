<?php

/**
 * 计数器数据层
 *
 * @package   Data
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2016 Yixia.com all rights reserved
 */

class Data_Counter extends Abstract_Data{
    
    /**
     * 配置
     * 
     * @var array
     */
    public $conf = array ();
    
    /**
     * 参数
     * 
     * @var mix
     */
    public $args;
    
    /**
     * 参数值
     * 
     * @var array
     */
    public $args_data = array ();
    
    /**
     * 计数值
     * 
     * @var int
     */
    protected $value;
    protected $db;
    protected $redis;

    /**
     * 构造方法
     * 
     * @param array $conf 数据库连接配置
     * 
     * @return void
     */
    public function __construct($conf) {
        $this->conf = $conf;
    }

    /**
     * gets
     *
     * @param string $key  参数
     * @param array  $args 参数值
     * 
     * @return Ambigous <number, multitype:mixed Ambigous <number, mixed> >
     */
    public function gets($key, $args) {
        $this->args = $args;
        $result = array ();
        if (empty($this->conf['args'])) {
            $this->conf['args'] = array ();
        }
        $where_keys = array_keys($this->conf['args']);
        foreach ( $where_keys as $kc => $col ) {
            $this->args_data["{$col}"] = $this->args[$kc];
        }
        $diff_ids = $exist_id = $params = $other_data = array ();
        $batch = false;
        $batch_key = null;
        $batch_index = 0;
        $i = 0;
        foreach ( $this->conf['args'] as $k => $v ) {
            if ($v == 'int_or_array') {
                $batch = true;
                $batch_key = $k;
                $batch_index = $i;
            } else {
                if (is_array($this->args_data["{$k}"])) {
                    $other_data = $this->args_data["{$k}"];
                } else {
                    $other_data[] = $this->args_data["{$k}"];
                }
            }
            $i ++;
        }
        $other_value = implode(',', $other_data);
        $other_value = rtrim($other_value, ',');
        $other_value = ltrim($other_value, ',');
        $batch_ids = array ();
        $mc_param = array ();
        $batch_ids =  $this->args ;
        $params = $this->args;

        if (isset($this->conf['use_db']) && $this->conf['use_db']) {
            $mc_param = array ();
            if (count($this->conf['args']) > 1 ) {
               $mc_param[] = $key . implode(':', $params);
               $params = array($params[0]);
            } else {
                foreach ($params as $p) {
                    $mc_param[] = $key . $p;
                }
            }
            $mc_result = $this->getMc()->getMultiData($this->getMcKey(), $mc_param);
            $exist_id = array ();
            if ($mc_result && ! empty($mc_result)) {
                foreach ( $batch_ids as $id ) {
                    $mc_key = $this->getMc()->getCacheConfig($this->getMcKey(), array($key . $id));
                    if (isset($mc_result[$mc_key['cache_key']])) {
                        $result[( string ) $id] = intval($mc_result[$mc_key['cache_key']]);
                        $result[( string ) $id] < 0 && $result[( string ) $id] = 0;
                        $exist_id[] = $id;
                    }
                }
            }
            $diff_ids = array_diff($params, $exist_id);
            if ($diff_ids) {
                $db = $this->getDb();
                // 获取表名称
                $table_name = $this->getTableName();
                
                // 获取查询条件信息
                $where_info = $this->getWhere();
                
                // 查询数据
                $select_key = $this->conf['key'];
                $sql = "SELECT {$select_key},num FROM {$table_name} WHERE " . $where_info['where'];
                $db_data = $db->fetchAll($sql, $where_info['param']);
                $db_data = Helper_Array::hashmap($db_data, $select_key);
                foreach ( $diff_ids as $k_id ) {
                    $db_result[$k_id] = isset($db_data[$k_id]['num']) ? intval($db_data[$k_id]['num']) : 0;
                    $db_result[$k_id] < 0 && $db_result[$k_id] = 0;
                }
                
                $result += $db_result;
                $mc_data = array ();
                $mc_param = array ();
                foreach ( $diff_ids as $id ) {
                    if (count($this->conf['args']) > 1 ) {
                        $mp = $key  . implode(':', $this->args);
                    } else {
                        $mp = $key  . $id;
                    }
                    $mc_data[$mp] = isset($db_data[$id]['num']) ? intval($db_data[$id]['num']) : 0;
                }
                $mc_data && $this->getMc()->setMultiData($this->getMcKey(), $mc_data);
            }
        } elseif ($this->conf['use_redis']) {
            if ($this->getRedis() === false || ! is_object($this->getRedis())) {
                return false;
            }
            /*
             * $redis_result = $this->getRedis()->getMultiData($this->conf['redis_alias'], $params);
             * foreach ($batch_ids as $k=>$id) {
             * $result[$id] = isset($redis_result[$k]) ? intval($redis_result[$k]) : 0 ;
             * }
             */
            $result = $this->_getMultiData($this->conf['redis_alias'], $params);
        }
        
        return $result;
    }

    /**
     * get multi data
     *
     * @param string $key      key 
     * @param array  $params   para
     * @param bool   $use_hash use hash or not 
     * 
     * @return multitype:number
     */
    protected function _getMultiData($key, array $params, $use_hash = false) {
        $result = array ();
        // hash redis params仅支持一维数组,如：array(id1,...)，不支持array(id1,type)
        if (count($params) == count($params, 1) && $use_hash) {
            $arr_hash = array ();
            foreach ( $params as $pkey ) {
                $hash = Comm_Redis::rSubHash($pkey);
                $arr_hash[$hash][] = $pkey;
            }
            foreach ( $arr_hash as $each ) {
                $use_hash = isset($this->conf['use_hash']) ? $this->conf['use_hash'] : 0;
                $hash_key = $use_hash ? $each[0] : 0;
                $redis_result = $this->getRedis($hash_key)->getMultiData($this->conf['redis_alias'], $each);
                foreach ( $each as $k => $id ) {
                    $result[$id] = isset($redis_result[$k]) ? intval($redis_result[$k]) : 0;
                    $result[$id] < 0 && $result[$id] = 0;
                }
            }
        } else {
            $batch_index = 0;
            $batch_index_no = 0;
            $batch_ids = array();
            $batch = false;
            $j = 0;
            foreach ( $this->conf['args'] as $k1 => $v1 ) {
                if ($v1 == 'int_or_array') {
                    $batch = true;
                    $batch_index = $j;
                } else {
                    $batch_index_no = $j;
                }
                $j++;
            }
            if (!$batch) {
                $result = $this->getRedis()->getData($this->conf['redis_alias'], $params);
                $result = array ($result);
            } else {
                $batch_ids = is_array($params[$batch_index]) ? $params[$batch_index] : array($params[$batch_index]);
                $other_value = $params[$batch_index_no];
                $redis_params = array();
                foreach ($batch_ids as $id) {
                    $redis_params[] = $id . ',' . $other_value;
                }
                $redis_result = $this->getRedis()->getMultiData($this->conf['redis_alias'], $redis_params);
                foreach ( $redis_params as $k => $id ) {
                    if (strpos($id, ',') !== false)  {
                        $arg = explode ( ',', $id );
                        $id = $arg[$batch_index];
                    }
                    $result[$id] = isset($redis_result[$k]) ? intval($redis_result[$k]) : 0;
                }
            }
        }
        
        return $result;
    }

    /**
     * set
     * 
     * @param string $key   参数
     * @param array  $args  参数值
     * @param int    $value 计数值
     * 
     * @return Ambigous <boolean, number, multitype:>
     */
    public function set($key, $args, $value) {
        //处理参数
        $$value = intval($value);
        if ($value == 0) {
            return 0;
        }
        $this->args = $args;
        $this->value = intval($value);
        $result = array ();
        if (isset($this->conf['use_db']) && $this->conf['use_db']) {
            $mc_params = array ();
            $where_keys = array_keys($this->conf['args']);
            foreach ( $where_keys as $k => $col ) {
                $this->args_data["{$col}"] = $this->args[$k];
            }
            $mc_params = array ($key . implode('_', $this->args));
            if ($this->value > 0) {
                $ret = $this->getMc()->incre($this->getMcKey(), $mc_params, $this->value);
            } else {
                $ret = $this->getMc()->decre($this->getMcKey(), $mc_params, abs($this->value));
            }
            
            $db = $this->getDb();
            // 获取表名称
            $table_name = $this->getTableName();
            // 获取查询条件信息
            $where_info = $this->getWhere();
            //无缓存
            if ($ret === false) {
                // 查询数据
                $sql = "SELECT num FROM {$table_name} WHERE " . $where_info['where'];
                $num = $db->fetchOne($sql, $where_info['param']);
                $this->getMc()->setData($this->getMcKey(), $mc_params, intval($num));//初始化缓存
                
                if ($this->value > 0) {
                    $ret = $this->getMC()->incre($this->getMcKey(), $mc_params, $this->value);
                } else {
                    $ret = $this->getMC()->decre($this->getMcKey(), $mc_params, abs($this->value));
                }
            }
            
            // 执行计数修改
            $sql = "UPDATE {$table_name} SET num=num + {$this->value} WHERE " . $where_info['where'];
            $rowCount = $db->execute($sql, $where_info['param']);
            
            // 未初始化则初始化后再次修改(需避免并发时数据被覆盖)
            if ($this->value > 0 && $rowCount == 0) { // 计数减到负数时影响条数也为0
                $ret1 = $db->insert($table_name, $this->args_data + array ('num' => 0), true);
                $ret = $db->execute($sql, $where_info['param']);
            }
            
            if ($ret === false) {
                $rs = $this->gets($key, $args);
                $ret = array_pop($rs);
            }
        } elseif ($this->conf['use_redis']) {
            // hash redis params仅支持一维数组,如：array(id1,...)，不支持array(id1,type)
            if (count($this->args) == count($this->args, 1)) {
                $use_hash = isset($this->conf['use_hash']) ? $this->conf['use_hash'] : 0;
                $hash_key = $use_hash ? $this->args[0] : 0;
                $redis = $this->getRedis($hash_key);
            } else {
                $redis = $this->getRedis();
            }
            if ($this->value > 0) {
                $ret = $redis->incrByData($this->conf['redis_alias'], $this->args, $this->value);
            } else {
                $ret = $redis->decrByData($this->conf['redis_alias'], $this->args, abs($this->value));
            }
            if ($ret === false) {
                if ($this->value > 0) {
                    $ret = $redis->incrByData($this->conf['redis_alias'], $this->args, $this->value);
                } else {
                    $ret = $redis->decrByData($this->conf['redis_alias'], $this->args, abs($this->value));
                }
            }
            if ($ret < 0) {
                $ret = $redis->setData($this->conf['redis_alias'], $this->args, 0);
            }
            if ($ret === false) {
                throw new Exception_System(200101, "Redis计数器set异常", array ('key' => $key, 'args' => implode(',', $args), 'value' => $value));
            }
        }
        
        return $ret;
    }

    /**
     * repair count
     * 
     * @param string $key        key
     * @param minx   $args       args
     * @param int    $value      value
     * @param bool   $repair_log log
     * 
     * @return boolean|Ambigous <multitype:, number, boolean>
     */
    public function replace($key, $args, $value, $repair_log = false) {
        // 处理参数
        $value = intval($value);
        if ($value < 0) {
            return false;
        }
        $this->args = $args;
        $this->value = $value;
        $result = array ();
        if (isset($this->conf['use_db']) && $this->conf['use_db']) {
            $mc_params = array ();
            $where_keys = array_keys($this->conf['args']);
            foreach ( $where_keys as $k => $col ) {
                $this->args_data["{$col}"] = $this->args[$k];
            }
            $mc_params = array ($key . implode('_', $this->args));
            $ret = $this->getMc()->setData($this->getMcKey(), $mc_params, $value);
            if ($ret === false) {
                $ret = $this->getMc()->deleteData($this->getMcKey(), $mc_params);
            }
            
            // 获取表名称
            $table_name = $this->getTableName();
            
            // 获取查询条件信息
            $where_info = $this->getWhere();
            
            // --直接修改
            $db = $this->getDb();
            $ret = $db->update($table_name, array ('num' => $value), $where_info['where'], $where_info['param']);
            
            // --修改影响记录数为0，则初始化
            if ($ret == 0) {
                $db->insert($table_name, $this->args_data + array ('num' => 0), true);
                $ret = $db->update($table_name, array ('num' => $value), $where_info['where'], $where_info['param']);
            }
        } elseif ($this->conf['use_redis']) {
            // hash redis params仅支持一维数组,如：array(id1,...)，不支持array(id1,type)
            if (count($args) == count($args, 1)) {
                $use_hash = isset($this->conf['use_hash']) ? $this->conf['use_hash'] : 0;
                $hash_key = $use_hash ? $args[0] : 0;
                $redis = $this->getRedis($hash_key);
            } else {
                $redis = $this->getRedis();
            }
            $redis_result = $redis->getData($this->conf['redis_alias'], $args);
            if ($redis_result === $value) {
                return false;
            }
            $ret = $redis->setData($this->conf['redis_alias'], $args, $value);
            if ($ret === false) {
                // again
                $ret = $redis->setData($this->conf['redis_alias'], $args, $value);
            }
            if ($ret === false) {
                if ($ret === false) {
                    throw new Exception_System(200101, "Redis计数器replace异常", array ('key' => $key, 'args' => implode(',', $args), 'value' => $value));
                }
            }
        }
        
        // 记录修复日志
        /*
         * if ($ret && $repair_log) {
         * Helper_Log::writeApplog(
         * 'repair_counter', json_encode(
         * array(
         * 't' => $key,
         * 'n' => $ret,
         * 'key' => $args,
         * )
         * , JSON_UNESCAPED_UNICODE)
         * );
         * }
         */
        
        return $ret;
    }

    /**
     * 获得表名
     *
     * @return string
     */
    protected function getTableName() {
        $table_hash_type = isset($this->conf['table_hash_type']) ? $this->conf['table_hash_type'] : 't';
        switch ($table_hash_type) {
            case 't' :
                $table_name = Comm_Db::t($this->conf['table_base']);
                break;
            case 'tSubHash' :
                $table_name = Comm_Db::tSubHash($this->conf['table_base'], $this->args_data[$this->conf['table_hash_key']]);
                break;
            case 'tSubUuid' :
                $table_name = Comm_Db::tSubUuid($this->conf['table_base'], $this->args_data[$this->conf['table_hash_key']]);
                break;
            case 'tSubDate' :
                $date  = new DateTime();
                $year  = substr($this->args_data[$this->conf['table_hash_key']], 0, 4);
                $month = substr($this->args_data[$this->conf['table_hash_key']], 4, 2);
                $date->setDate($year, $month, 1);
                $table_name = Comm_Db::tSubDate($this->conf['table_base'], $date);
                break;
            default :
                $table_name = Comm_Db::t($this->conf['table_base']);
                break;
            // 计数器配置错误，表名hash类型不存在
            /*
             * throw new Exception_System('200204', null, array(
             * 'f' => __FILE__,
             * 'l' => __LINE__,
             * 'type' => $type,
             * 'arr_key' => $arr_key,
             * 'conf' => $arr_conf,
             * ));
             */
        }
        
        return $table_name;
    }

    /**
     * 组装 where 条件语句
     *
     * @return array
     */
    protected function getWhere() {
        $arr_where = array ();
        $param = array ();
        foreach ( $this->args_data as $field => $v ) {
            if (! is_array($v)) {
                $arr_where[] = "$field=?";
                $param[] = $v;
            } else {
                $arr_where[] = "$field IN(" . rtrim(str_repeat('?,', count($v)), ',') . ")";
                $param = array_merge($param, $v);
            }
        }
        
        return array ('where' => implode(' AND ', $arr_where) . ($this->value < 0 ? ' AND num>0' : ''), 'param' => $param);
    }

    /**
     * get Db
     * 
     * @return Ambigous <Comm_Dbbase, boolean, Cls_db_Base>
     */
    protected function getDb() {
        $db = isset($this->conf['db']) ? $this->conf['db'] : Comm_Db::DB_COUNTER;

        return Comm_Db::d($db);
    }

    /**
     * get Redis
     *
     * @param int $hash_key hash key
     *       
     * @return Ambigous <Comm_Redis, Cls_db_Base, Comm_Dbbase, Comm_Redisbase>
     */
    protected function getRedis($hash_key = 0) {
        $redis = isset($this->conf['redis']) ? $this->conf['redis'] : Comm_Redis::COUNTER;
        
        return Comm_Redis::r($redis, $hash_key);
    }

    /**
     * get Mc
     * 
     * @return Ambigous <Comm_Mc, Comm_Mc>
     */
    protected function getMc() {
        $mc = isset($this->conf['mc']) ? $this->conf['mc'] : Comm_Mc::COUNTER;
        return Comm_Mc::init($mc);
    }

    /**
     * get mc key
     * 
     * @return Ambigous <string, multitype:>
     */
    protected function getMcKey() {
        $key = isset($this->conf['mc_alias']) ? $this->conf['mc_alias'] : 'counter';
        // $cache_config = $this->getMc()->getCacheConfig($key, $this->args);
        
        return $key;
    }
}
