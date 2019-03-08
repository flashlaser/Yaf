<?php

/**
 * Schema
 *
 * @package Model
 * @author  baojun <zhangbaojun@yixia.com>
 */

class SchemaModel extends Abstract_M{

    /**
     * 没有添加COMMENT_ERROR
     *
     * @var string
     */
    const COLUMN_NO_UNSIGNED = 'NO_UNSIGNED';

    /**
     * 含有NULL
     *
     * @var string
     */
    const COLUMN_HAS_NULL = 'HAS_NULL';

    /**
     * 注释乱码
     *
     * @var string
     */
    const COLUMN_COMMENT_ERROR = 'COMMENT_ERROR';

    /**
     * 获取所有的数据库ID值
     *
     * @return array
     *
     * @author chengxuan
     */
    static public function showDbIds() {
        $reflection = new ReflectionClass('Comm_Db');
        $result = array ();
        foreach ( $reflection->getConstants() as $key => $value ) {
            strpos($key, 'DB_') === 0 && $result[] = $value;
        }
        return $result;
    }

    /**
     * 根据指定的规则，获取表名
     *
     * @param string $db_id       数据库连接标识
     * @param string $table_match 表匹配写法（数据库的LIKE写法）
     *       
     * @return array
     *
     * @author chengxuan
     */
    static public function matchTables($db_id, $table_match) {
        $data_schema = new Data_Schema($db_id);
        return $data_schema->matchTables($table_match);
    }

    /**
     * 验证一个数据库的准确性
     *
     * @param string $db_id 数据库ID
     *       
     * @return array
     *
     * @author chengxuan
     */
    static public function validateDatabase($db_id) {
        $data_schema = new Data_Schema($db_id);
        $result = array ();
        
        $tables = $data_schema->fetchTables();
        $tables_count = count($tables);
        for ($i = 0; $i < $tables_count; ++ $i) {
            $table_data = $tables[$i];
            
            if (substr($table_data['TABLE_NAME'], - 3) === '_00' && isset($tables[$i + 255]) && substr($tables[$i + 255]['TABLE_NAME'], - 3) === '_ff') {
                // 16进制256分表
                $i += 255;
                $table_name = substr($table_data['TABLE_NAME'], 0, - 2) . '**';
            } elseif (preg_match('/^_\d{4}$/', substr($table_data['TABLE_NAME'], - 5))) {
                // 按日期分表
                $table_base = substr($table_data['TABLE_NAME'], 0, - 4);
                $table_name = $table_base . '@@@@';
                for ($j = $i + 1; $j < $tables_count; ++ $j) {
                    if ($table_base != substr($tables[$j]['TABLE_NAME'], 0, - 4)) {
                        break;
                    }
                }
                $i = $j - 1;
            } else {
                $table_name = $table_data['TABLE_NAME'];
            }
            
            $columns = $data_schema->fetchColumns($table_data['TABLE_NAME']);
            $index = $data_schema->fetchIndexs($table_data['TABLE_NAME']);
            $check_result = self::_checkTable($db_id, $table_data, $columns, $index, $table_name);
            $check_result && $result[$table_name] = $check_result;
        }
        return $result;
    }

    /**
     * 检查一个表是否合适
     *
     * @param string $db_id db id 
     * @param string $table table name
     * 
     * @author chengxuan
     */
    static public function validateTable($db_id, $table) {
        $data_schema = new Data_Schema($db_id);
        $table_data = $data_schema->fetchTable($table);
        $columns = $data_schema->fetchColumns($table);
        $index = $data_schema->fetchIndexs($table);
        
        return self::_checkTable($db_id, $table_data, $columns, $index, $table_data['TABLE_NAME']);
    }

    /**
     * 根据数据，检查一张表是否有错误
     *
     * @param int    $db_id      数据库ID
     * @param array  $table_data 表数据
     * @param array  $columns    字段数据
     * @param array  $index      索引数据
     * @param string $table_name 处理后的表名称（主要是分表）
     *       
     * @return mixed
     *
     * @author chengxuan
     */
    static protected function _checkTable($db_id, array $table_data, array $columns, array $index, $table_name) {
        // 获取信息
        $data = array ('engine' => self::_checkEngine($table_data), 'collation' => self::_checkCollation($table_data), 'pk' => self::_checkHasPk($index), 'columns' => self::_checkColumns($columns, $table_name));
        
        // 分析表问题
        $result_table = array ();
        if ($data['engine'] !== true) {
            $result_table[] = "ENGINE错误({$data['engine']})";
        }
        if ($data['collation'] !== true) {
            $result_table[] = "编码错误({$data['collation']})";
        }
        if (! $data['pk']) {
            $result_table[] = "无主键";
        }
        
        // 分析字段问题
        $fix_sql = $data['columns'] ? sprintf('ALTER TABLE `%s`', $table_name) : '';
        $result_columns = array ();
        foreach ( $data['columns'] as $key => $value ) {
            $result_columns[$key] = array ();
            
            $fix_sql .= sprintf(' CHANGE `%s` `%s` %s', $key, $key, strtoupper($value['val']['COLUMN_TYPE']));
            $append_unsigned = false;
            $comment = $value['val']['COLUMN_COMMENT'];
            $has_null = $value['val']['IS_NULLABLE'] === 'YES';
            
            foreach ( $value['q'] as $q ) {
                switch ($q) {
                    case self::COLUMN_NO_UNSIGNED :
                        $append_unsigned = true;
                        $msg = '数字有符号';
                        break;
                    case self::COLUMN_HAS_NULL :
                        $msg = '允许NULL';
                        $has_null = false;
                        break;
                    case self::COLUMN_COMMENT_ERROR :
                        $msg = '注释乱码';
                        $comment = mb_convert_encoding($value['val']['COLUMN_COMMENT'], 'Windows-1252');
                        break;
                }
                $result_columns[$key][] = $msg;
            }
            
            $append_unsigned && $fix_sql .= ' UNSIGNED';
            $has_null || $fix_sql .= ' NOT NULL';
            if ($value['val']['COLUMN_DEFAULT'] === null) {
                $has_null && $fix_sql .= ' DEFAULT NULL';
            } else {
                $fix_sql .= sprintf(' DEFAULT \'%s\'', addslashes($value['val']['COLUMN_DEFAULT']));
            }
            
            $value['val']['EXTRA'] && $fix_sql .= ' ' . strtoupper($value['val']['EXTRA']);
            $fix_sql .= sprintf(" COMMENT '%s',", addslashes($comment));
        }
        unset($data);
        $fix_sql = rtrim($fix_sql, ',') . ';';
        
        if ($result_table || $result_columns) {
            $result = array ('table' => $result_table, 'columns' => $result_columns, 'fix_sql' => $fix_sql);
        } else {
            $result = false;
        }
        
        return $result;
    }

    /**
     * 检查数据表引擎是否正确
     *
     * @param array $table_data 表数据
     *
     * @return mixed 正确返回true，错误返回引擎名称
     *        
     * @author chengxuan
     */
    static protected function _checkEngine(array $table_data) {
        $result = $table_data['ENGINE'] === 'InnoDB' ? true : $table_data['ENGINE'];
        if ($result !== true) {
            try {
                $engine_conf = Comm_Config::get('schema.engine.' . $table_data['TABLE_NAME']);
                $result === $engine_conf && $result = true;
            } catch ( Exception_System $e ) {
                
            }
        }
        return $result;
    }

    /**
     * 检查数据表编码是否正确
     *
     * @param array $table_data 表数据
     *
     * @return mixed 正确返回true，错误返回编码名称
     *        
     * @author chengxuan
     */
    static protected function _checkCollation(array $table_data) {
        if (strpos($table_data['TABLE_COLLATION'], 'utf8') === false) {
            return $table_data['TABLE_COLLATION'];
        }
        return true;
    }

    /**
     * 检查是否有主键
     *
     * @param array $index 索引数组集
     *       
     * @return mixed 正确返回主键名称，错误返回false
     *        
     * @author chengxuan
     */
    static protected function _checkHasPk(array $index) {
        foreach ( $index as $value ) {
            if ($value['INDEX_NAME'] === 'PRIMARY') {
                return $value['COLUMN_NAME'];
            }
        }
        return false;
    }

    /**
     * 检查字段（unsigned/not null/注释乱码）
     *
     * @param array $columns    字段列表
     * @param array $table_name 处理后的表名称
     *       
     * @return array
     *
     * @author chengxuan
     */
    static protected function _checkColumns(array $columns, $table_name) {
        $result = array ();
        $schema_config = Comm_Config::get('schema');
        foreach ( $columns as $value ) {
            
            // 判断数字是否是无符号
            if (in_array($value['DATA_TYPE'], array ('smallint', 'mediumint', 'int', 'bigint')) && strpos($value['COLUMN_TYPE'], 'unsigned') === false && empty($schema_config['no_unsigned'][$table_name][$value['COLUMN_NAME']])) {
                $result[$value['COLUMN_NAME']]['q'][] = 'NO_UNSIGNED';
            }
            
            // 判断字段是否是NOT NULL
            if ($value['IS_NULLABLE'] === 'YES' && empty($schema_config['has_null'][$table_name][$value['COLUMN_NAME']])) {
                $result[$value['COLUMN_NAME']]['q'][] = 'HAS_NULL';
            }
            
            // 判断注释是否有乱码（仅允许半角所有，中文文字，全角中文符号，通用全角符号）
            if (! preg_match('/^[\x20-\x7e\x{4e00}-\x{9fa5}\x{3000}-\x{303f}\x{ff00}-\x{ffef}]*$/u', $value['COLUMN_COMMENT'])) {
                $result[$value['COLUMN_NAME']]['q'][] = 'COMMENT_ERROR';
            }
            
            // 把字段属性带上
            if (isset($result[$value['COLUMN_NAME']])) {
                $result[$value['COLUMN_NAME']]['val'] = $value;
            }
        }
        
        return $result;
    }
}