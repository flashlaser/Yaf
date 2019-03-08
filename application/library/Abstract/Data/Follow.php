<?php
/**
 * 对data层的抽象
 *
 * @package Absctract.Data
 * @author  huangxiran <huangxiran@yixia.com>
 */
abstract class Abstract_Data_Follow{
    
    protected $blank_mc = "\002";

    // 分库分表seed
    protected $hash_key = 0;

    // 数据库表数量
    protected   $table_number    = 32;

    // 数据库数量
    protected   $db_num          = 8;


    /**
     * 根据指定字段检查数据字段的合法性
     *
     * @param array $data        data
     * @param array $allow_field field
     *
     * @return array
     * @throws Exception_System
     */
    static public function checkDataField($data, array $allow_field) {
        if ( ! is_array ( $data ) && ! is_object ( $data ) ) {
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
     * @param string $mc_config  缓存配置
     * @param array  $mc_param   缓存key的参数
     * @param string $multi_key  key
     * @param string $func_name  回调取数据的方法名,必须是本对象的方法.其中的数组参数必须放最后
     * @param array  $func_args  回调方法的参数列表,不包括最后的数组参数,回调时会自动附加
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
     * @param array $ids    ids
     * @param bool  $use_mc use mc or not
     * 
     * @return array
     */
    public function baseInfos(array $ids, $use_mc = true) {
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
        // 取缓存
        if ($use_mc && $mc_result && ! empty ( $mc_result )) {
            foreach ( $ids as $id ) {
                $cache_key_info = $mc->getKey ( $this->mc_content_info, array ( $id ) );
                $cache_info = json_decode ( $mc_result [ $cache_key_info ['cache_key'] ], true );
                if (isset ( $cache_info ) && ! empty ( $cache_info )) {
                    $exist_id [] = $id;
                    $result [$id] = $cache_info;
                }
            }
        }

        // 找出不在缓存的id
        $inexist_ids = array_diff ( $ids, $exist_id );
        $where_query_array = [];
        if ( !empty( $inexist_ids) ) {
            foreach ( $inexist_ids as $id ) {
                $p_key_array = explode( ":", $id );
                $where_query_array[] = "(app_id=" . $p_key_array[0] . " AND user=" . $p_key_array[1] . " AND fans=" . $p_key_array[2] . ")";
            }
        }
        $where_query_str = implode( ' OR ', $where_query_array );

        // 读库
        if (! empty ( $where_query_str )) {
            $sql = 'SELECT * FROM ' . $this->_table ( $this->table_content ) . ' WHERE ' . $where_query_str;
            $db_result = $this->_db ( $this->db_content_conf )->fetchAll ( $sql );
            if ( $db_result !== false and !empty( $db_result ) ) {
                foreach ( $db_result as $row ) {
                    $mc_key = implode( ":", [ $row['app_id'], $row['user'], $row['fans'] ] );
                    $mc_data [$mc_key] = json_encode( $row );
                    $result[implode( ":", [ $row['app_id'], $row['user'], $row['fans'] ] ) ] = $row;
                }
                $ret = $mc->setMultiData ( $this->mc_content_info, $mc_data );
            }
            
        }
        return $result;
    }
    
    /**
     * add
     *
     * @param array $content_data    内容表内容
     * 
     * @return number
     */
    public function baseAdd( $content_data ) {
        $db = $this->_db ( $this->db_content_conf );
        $ret = $db->insert ( $this->_table ( $this->table_content ), $content_data, true );
        if ($ret != false) {
            $pre_mc_key = $content_data["app_id"] . ":" . $content_data["user"];
            $content_mc_key = $pre_mc_key . ":" . $content_data["fans"];
            $mc_ret = $this->baseAddCache(
                $this->mc_content_conf,
                $this->mc_content_info,
                [ $content_mc_key ],
                json_encode( $content_data )
            );
        }
        return $ret;
    }


    /**
     * 基础数据，更新MC缓存
     *
     * @param string    $mc_config     mc 资源配置
     * @param string    $mc_key_config mc key 配置
     * @param array     $keys          mc key 数组
     * @param array     $insert_data   缓存内容
     *
     * @return mixed
     */
    public function baseAddCache( $mc_config, $mc_key_config, array $keys, $insert_data ) {
        return $this->_getMc ( $mc_config )->setData ( $mc_key_config, $keys, $insert_data );
    }
    
    /**
     * update
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
     * @param array   $p_keys  key
     * @param array $mc_params params 
     * 
     * @return bool
     */
    public function baseDelete( array $p_keys , $mc_params = null ) {

        $fans_list = implode( ",", $p_keys['fans'] );
        $sql = "DELETE FROM " . $this->_table ( $this->table_content ) . " WHERE app_id = ? AND user = ? AND fans in ({$fans_list})";
        $params = [ $p_keys['app_id'], $p_keys['user'] ];
        $ret = $this->_db ( $this->db_content_conf )->execute ( $sql, $params );
        unset( $params );

        // update content cache
        if ($ret) {
            $mc_keys = [];
            foreach ( $p_keys['fans'] as $fans ) {
                $mc_keys[] = implode( ":", [ $p_keys['app_id'], $p_keys['user'], $fans ] );
            }

            $this->_getMc ( $this->mc_content_conf )->deleteData ( $this->mc_content_info, $mc_keys );
        }

        return $ret;
    }
    
    /**
     * get list
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
     * @return PDOStatement
     */
    public function baseGetCount() {
        $sql = 'SELECT count(*) as cnt FROM ' . $this->_table ( $this->table_index );
        $total = $this->_db ()->fetchOne ( $sql, array () );
        return $total;
    }
    
    /**
     * get mc
     * @param string $mc_conf conf
     * 
     * @return  Comm_Mc
     */
    protected function _getMc( $mc_conf ) {
        $mc = Comm_Mc::init ( $mc_conf );
        return $mc;
    }


    /**
     * get db
     * @param string $db_config   配置
     *
     * @return object
     */
    protected function _db( $db_config = null ) {
        Comm_DB::$quick_break_off = true;
        if ( !$db_config ) {
            $db_config = $this->db_content_conf;
        }
        return Comm_Db::d( $db_config, false, $this->hash_key );
    }

    /**
     * get table
     * @param string $table_content   配置
     *
     * @return object
     */
    protected function _table( $table_content = null ) {
        if ( !$table_content ) {
            $table_content = $this->table_content;
        }
        return $this->_tHash($table_content, $this->hash_key, $this->table_number);
    }


    /**
     * 分表
     *
     * @param string    $table_base 表
     * @param int       $n          hash
     * @param int       $table_n    表数量
     * @param int       $db_n       库数量
     *
     * @return string
     */
    private function _tHash ( $table_base, $n, $table_n, $db_n = 8 ) {
        $table = Comm_Db::t( $table_base );

        $idx = 0;
        if ($n > 0 && $table_n > 0) {
            $idx = $n % ( $db_n * $table_n );
            $idx = sprintf('%03s', $idx);
            $table .= '_' . $idx;
        }

        return $table;
    }


    /**
     * get Redis
     * @param int $hash_key hash key
     *
     * @return Comm_Redis
     */
    protected function _getRedis( $hash_key = 0 ) {
        return Comm_Redis::r($this->redis_content_conf, $hash_key);
    }
}
