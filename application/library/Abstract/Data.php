<?php
/**
 * 对data层的抽象
 *
 * @package abstract
 * @author  baojun <baojun4545@sina.com>
 */
abstract class Abstract_Data{
    
    protected $blank_mc = "\002";
    
    /**
     * 根据指定字段检查数据字段的合法性
     * 
     * @param array $data        数据
     * @param array $allow_field 指定字段
     * 
     * @return array 新数据
     */
    static public function checkDataField($data, array $allow_field) {
        if (! is_array ( $data ) && ! is_object ( $data )) {
            throw new Exception_System ( 200111, null, array ('data' => $data ) );
        }
        $result = array ();
        foreach ( $allow_field as $value ) {
            isset ( $data [$value] ) && $result [$value] = $data [$value];
        }
        return $result;
    }
    
    /**
     * 先从缓存中获取，如果获取失败，再从数据库中获取，获取后再缓存
     * 
     * @param string  $mc_config  config
     * @param array   $mc_param   param 
     * @param int     $db_id      db
     * @param string  $sql        sql 
     * @param array   $sql_param  param
     * @param string  $fetch_type type
     * @param boolean $identical  ide
     * @param float   $cas_token  token
     *
     * @return array
     */
    static public function getDataCache($mc_config, array $mc_param, $db_id, $sql, array $sql_param, $fetch_type = 'fetchRow', $identical = true, $cas_token = null) {
        $mc = Comm_Mc::init ();
        $result = $mc->getData ( $mc_config, $mc_param, $cas_token );
        if ($result === false || (! $identical && ! $result)) {
            $result = Comm_Db::d ( $db_id )->$fetch_type ( $sql, $sql_param );
            $mc->setData ( $mc_config, $mc_param, $result );
        }
        return $result;
    }
    
    /**
     * 批量获取缓存值,并按回调自动更新数据
     * 
     * @param unknown $mc_config 单条数据的缓存配置
     * @param array   $mc_param  缓存key的参数
     * @param unknown $multi_key mutil key 
     * @param unknown $func_name 回调取数据的方法名,必须是本对象的方法.其中的数组参数必须放最后
     * @param unknown $func_args 回调方法的参数列表,不包括最后的数组参数,回调时会自动附加
     * 
     * @return mixed
     */
    protected function _getListMcOrCallback($mc_config, array $mc_param, $multi_key, $func_name, array $func_args) {
        $mc = Comm_Mc::init ();
        
        // 从mc批量读取
        $list = $mc->mapGetMultiData ( $mc_config, $mc_param, $multi_key );
        
        // 未中缓存的id列表
        $miss_ids = array_diff ( $mc_param [$multi_key], array_keys ( $list ) );
        
        // 过滤掉空白数据
        foreach ( $list as $id => $v ) {
            if ($v === $this->blank_mc) {
                unset ( $list [$id] );
            }
        }
        
        // 非缓存中读取数据
        if ($miss_ids) {
            // 读取数据
            $func_args [] = array_values ( $miss_ids ); // 最后一个是数组参数
            $miss_list = call_user_func_array ( array ($this,$func_name ), $func_args );
            
            // 合并数据用于返回
            $list = $list + $miss_list;
            
            // 处理数据创建缓存
            $blank_ids = array_diff ( $miss_ids, array_keys ( $miss_list ) );
            $miss_list = $miss_list + array_fill_keys ( $blank_ids, $this->blank_mc );
            $mc->mapSetMultiData ( $mc_config, $mc_param, $multi_key, $miss_list );
        }
        
        return $list;
    }
    
    /**
     * get mutiply
     *
     * @param array $ids    ids
     * @param bool  $use_mc use mc or not
     * 
     * @return array
     */
    public function baseInfos(array $ids, $use_mc = true) {
        sort ( $ids );
        $static_key = 'get_' . implode ( '_', $ids );
        $result = Comm_Sdata::get ( __CLASS__, $static_key );
        if ($result !== false) {
            return $result;
        }
        $result = array ();
        $mc_result = array ();
        // 是否强制读库
        if (! $use_mc) {
            $mc_result = false;
        } else {
            $mc = $this->_getMc ( $this->mc_content_conf );
            $mc_result = $mc->getMultiData ( $this->mc_content_info, $ids );
        }
        $exist_id = array ();
        $aid = '';
        // 取缓存
        if ($use_mc && $mc_result && ! empty ( $mc_result )) {
            foreach ( $ids as $id ) {
                $mc_key = $mc->getCacheConfig ( $this->mc_content_info, array ($aid ) );
                
                $cache_key_info = $mc->getKey ( $this->mc_content_info, array ($id) );
                $cache_info = $mc_result [$cache_key_info ['cache_key']];
                if (isset ( $info ) && ! empty ( $info )) {
                    $exist_id [] = $id;
                    $result [( string ) $id] = $info;
                }
            }
        }
        // 找出不在缓存的live_id
        $inexist_ids = array_diff ( $ids, $exist_id );
        // 读库
        if (! empty ( $inexist_ids )) {
            $sql = 'SELECT * FROM ' . $this->_table ( $this->table_content ) . ' WHERE id IN (' . rtrim ( str_repeat ( '?,', count ( $inexist_ids ) ), ',' ) . ')';
            $sql_param = array_values ( $inexist_ids );
            $db_result = $this->_db ( $this->db_content_conf )->fetchAll ( $sql, $sql_param );
            $db_result = Helper_Array::hashMap ( $db_result, 'id' );
            if ($db_result !== false) {
                
                foreach ( $inexist_ids as $inexist_id ) {
                    $mc_data [$inexist_id] = $db_result [$inexist_id];
                }
                $ret = $mc->setMultiData ( $this->mc_content_info, $mc_data );
            }
            
            $result += $db_result;
        }
        Comm_Sdata::set ( __CLASS__, $static_key, $result );
        return $result;
    }
    
    /**
     * add
     * 
     * @param array $content_data    内容表内容 
     * @param array $index_data      索引表内容 
     * @param array $mc_index_params 索引表缓存参数
     * 
     * @return number
     */
    public function baseAdd($content_data, $index_data = null, $mc_index_params = null) {
        $db = $this->_db ( $this->db_content_conf );
        $ret = $db->insert ( $this->_table ( $this->table_content ), $content_data, true );
        if ($ret != false) {
            $id = $db->lastId ();
            $insert_data ['id'] = $id;
            $re = $this->_getMc ( $this->mc_content_conf )->setData ( $this->mc_content_info, array ( $id ), $insert_data );
            if (! empty ( $index_data )) {
                if ($mc_index_params == "ID") {
                    $mc_index_params = $id;
                }
                $index_data [$this->primary_key_index] = $id;
                $db = $this->_db ( $this->db_index_conf );
                $ret = $db->insert ( $this->_table ( $this->table_index ), $index_data, true );
                if ($ret !== false) {
                    $this->_getMc ( $this->mc_index_conf )->deleteData ( $this->mc_index_info, array ($mc_index_params ) );
                }
            }
        }
        return $ret;
    }
    
    /**
     * update
     * 
     * @param number $id             id
     * @param array  $update_content content 
     * @param array  $update_index   update index 
     * @param array  $mc_params      param
     * 
     * @return bool
     */
    public function baseUpdate($id, array $update_content, array $update_index = null, $mc_params = null) {
        $ret = $this->_db ( $this->db_content_conf )->update ( $this->_table ( $this->table_content ), $update_content, $this->primary_key_content . ' = ?', array ($id ) );
        if ($ret !== false) {
            $this->_getMc ( $this->mc_content_conf )->setData ( $this->mc_content_info, array ($id ), $update_content );
            
            if (! empty ( $update_index )) {
                $ret = $this->_db ( $this->db_index_conf )->update ( $this->_table ( $this->table_index ), $update_index, $this->primary_key_index . ' = ?', array ($id ) );
                if ($ret !== false) {
                    $this->_getMc ( $this->mc_index_conf )->deleteData ( $this->mc_index_info, array ($mc_params ) );
                }
            }
        }
        return $ret;
    }
    
    /**
     * delete
     *
     * @param int   $id        id
     * @param array $mc_params params 
     * 
     * @return bool
     */
    public function baseDelete($id, $mc_params = null) {
        $sql = 'DELETE FROM ' . $this->_table ( $this->table_content ) . ' WHERE ' . $this->primary_key_content . ' = ?';
        $ret = $this->_db ( $this->db_content_conf )->execute ( $sql, array ($id) );
        if ($ret) {
            $this->_getMc ( $this->mc_content_conf )->deleteData ( $this->mc_content_info, array ($id ) );
        }
        if ($this->table_index && ! empty ( $this->table_index )) {
            $sql = 'DELETE FROM ' . $this->_table ( $this->table_index ) . ' WHERE ' . $this->primary_key_index . '= ?';
            $ret = $this->_db ( $this->db_index_conf )->execute ( $sql, array ($id ) );
            if ($ret) {
                $mc = $this->_getMc ( $this->mc_index_conf );
                $mc->deleteData ( $this->mc_index_info, array ($mc_params ) );
            }
        }
        return $ret;
    }
    
    /**
     * get list
     *
     * @param int    $mc_index_params params
     * @param int    $page            page
     * @param int    $count           count
     * @param bool   $use_mc          use mc or not
     * @param string $fill            fille
     * 
     * @return array
     */
    public function baseGetList($mc_index_params = null, $page = 1, $count = 10, $use_mc = true, $fill = null) {
        $offset = ($page - 1) * $count;
        $get_limit = $offset + $count;
        if ($get_limit > $this->mc_limit || ! $use_mc) {
            $result = $this->baseGetListFromDb ( $page, $count );
        } else {
            $mc = $this->_getMc ( $this->mc_index_conf );
            $mc_param = array (
                    $mc_index_params 
            );
            $result = $mc->getData ( $this->mc_index_info, $mc_param );
            if ($result === false) {
                $result = self::baseGetListFromDb ( 1, $this->mc_limit, $fill );
                if ($result) {
                    $mc->setData ( $this->mc_index_info, $mc_param, $result );
                }
            }
            $result = array_slice ( $result, $offset, $count );
        }
        return $result;
    }
    
    /**
     * get list from db
     *
     * @param int $page  page
     * @param int $count count
     * 
     * @return array
     */
    public function baseGetListFromDb($page, $count) {
        $offset = ($page - 1) * $count;
        $sql = 'SELECT * FROM ' . $this->_table () . "  ORDER BY " . $this->primary_key_index . " DESC LIMIT {$offset},{$count}";
        $result = $this->_db ()->fetchAll ( $sql, array () );
        
        return $result;
    }
    
    /**
     * get count
     *
     * @return Ambigous <multitype:, PDOStatement>
     */
    public function baseGetCount() {
        $sql = 'SELECT count(*) as cnt FROM ' . $this->_table ( $this->table_index );
        $total = $this->_db ()->fetchOne ( $sql, array () );
        return $total;
    }
    
    /**
     * get mc
     *
     * @param string $mc_conf conf  
     * 
     * @return Ambigous <Comm_Mc, Comm_Mc>
     */
    private function _getMc($mc_conf) {
        return Comm_Mc::init ( $mc_conf );
    }
    /**
     * get db
     *
     * @param string $db_conf conf
     * 
     * @return string
     */
    private function _db($db_conf) {
        return Comm_Db::d ( $db_conf );
    }
    
    /**
     * get table
     *
     * @param int $table table
     *       
     * @return string
     */
    private function _table($table) {
        return Comm_Db::t ( $table );
    }
}
