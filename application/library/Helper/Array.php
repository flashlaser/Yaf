<?php
/**
 * 简化数组操作的方法
 * 
 * @package Helper 
 * @author  baojun <baojun@sina.com>
 */

abstract class Helper_Array{

    /**
     * 从数组中移除空白的元素（其中包括只有空白字符的元素）
     *
     * 用法：
     * <code>
     * $arr = array('', 'test', '   ');
     * Helper_Array::remove_empty($arr);
     *
     * var_dump($arr); # 结果只有一个test
     * </code>
     *
     * @param array $arr 要处理的数组
     * @param boolean $trim 是否对数组元素调用 trim 函数
     */
    static function removeEmpty(& $arr, $trim = true) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                self::removeEmpty($arr[$key]);
            } else {
                $trim && $value = trim($value);
                if ($value == '') {
                    unset($arr[$key]);
                }
            }
        }
    }

    /**
     * 从一个二维数组中返回指定键的所有值
     *
     * 用法：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1'),
     *     array('id' => 2, 'value' => '2-1'),
     * );
     * $values = Helper_Array::cols($rows, 'value');
     *
     * print_r($values);
     *   // 输出结果为
     *   // array(
     *   //   '1-1',
     *   //   '2-1',
     *   // )
     * </code>
     *
     * @param array $arr 数据源
     * @param string $col 要查询的键
     *
     * @return array 包含指定键所有值的数组
     */
    static function cols($arr, $col) {
        $ret = array();
        foreach ($arr as $row) {
            if (isset($row[$col])) {
                $ret[] = $row[$col];
            }
        }
        return $ret;
    }

    /**
     * 将一个二维数组转换为 HashMap，并返回结果
     *
     * 用法1：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1'),
     *     array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = Helper_Array::hashmap($rows, 'id', 'value');
     *
     * print_r($hashmap);
     *   // 输出结果为
     *   // array(
     *   //   1 => '1-1',
     *   //   2 => '2-1',
     *   // )
     * </code>
     *
     * 如果省略 $value_field 参数，则转换结果每一项为包含该项所有数据的数组。
     *
     * 用法2：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1'),
     *     array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = Helper_Array::hashMap($rows, 'id');
     *
     * print_r($hashmap);
     *   // 输出结果为
     *   // array(
     *   //   1 => array('id' => 1, 'value' => '1-1'),
     *   //   2 => array('id' => 2, 'value' => '2-1'),
     *   // )
     * </code>
     *
     * @param array  $arr 数据源
     * @param string $key_field 按照什么键的值进行转换
     * @param string $value_field 对应的键值
     * @param boolean $force_string_key 强制使用字符串KEY
     *
     * @return array 转换后的 HashMap 样式数组
     */
    static function hashmap($arr, $key_field, $value_field = null, $force_string_key = false) {
        $ret = array();
        if ($value_field) {
            foreach ($arr as $row) {
                $key       = $force_string_key ? (string)$row[$key_field] : $row[$key_field];
                $ret[$key] = $row[$value_field];
            }
        } else {
            foreach ($arr as $row) {
                $key       = $force_string_key ? (string)$row[$key_field] : $row[$key_field];
                $ret[$key] = $row;
            }
        }
        return $ret;
    }

    /**
     * 将一个二维数组按照指定字段的值分组
     *
     * 用法：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1', 'parent' => 1),
     *     array('id' => 2, 'value' => '2-1', 'parent' => 1),
     *     array('id' => 3, 'value' => '3-1', 'parent' => 1),
     *     array('id' => 4, 'value' => '4-1', 'parent' => 2),
     *     array('id' => 5, 'value' => '5-1', 'parent' => 2),
     *     array('id' => 6, 'value' => '6-1', 'parent' => 3),
     * );
     * $values = Helper_Array::group_by($rows, 'parent');
     *
     * print_r($values);
     *   // 按照 parent 分组的输出结果为
     *   // array(
     *   //   1 => array(
     *   //        array('id' => 1, 'value' => '1-1', 'parent' => 1),
     *   //        array('id' => 2, 'value' => '2-1', 'parent' => 1),
     *   //        array('id' => 3, 'value' => '3-1', 'parent' => 1),
     *   //   ),
     *   //   2 => array(
     *   //        array('id' => 4, 'value' => '4-1', 'parent' => 2),
     *   //        array('id' => 5, 'value' => '5-1', 'parent' => 2),
     *   //   ),
     *   //   3 => array(
     *   //        array('id' => 6, 'value' => '6-1', 'parent' => 3),
     *   //   ),
     *   // )
     * </code>
     *
     * @param array $arr 数据源
     * @param string $key_field 作为分组依据的键名
     *
     * @return array 分组后的结果
     */
    static function groupBy($arr, $key_field) {
        $ret = array();
        foreach ($arr as $row) {
            $key         = $row[$key_field];
            $ret[$key][] = $row;
        }
        return $ret;
    }

    /**
     * 将一个平面的二维数组按照指定的字段转换为树状结构
     *
     * 用法：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1', 'parent' => 0),
     *     array('id' => 2, 'value' => '2-1', 'parent' => 0),
     *     array('id' => 3, 'value' => '3-1', 'parent' => 0),
     *
     *     array('id' => 7, 'value' => '2-1-1', 'parent' => 2),
     *     array('id' => 8, 'value' => '2-1-2', 'parent' => 2),
     *     array('id' => 9, 'value' => '3-1-1', 'parent' => 3),
     *     array('id' => 10, 'value' => '3-1-1-1', 'parent' => 9),
     * );
     *
     * $tree = Helper_Array::tree($rows, 'id', 'parent', 'nodes');
     *
     * print_r($tree);
     *   // 输出结果为：
     *   // array(
     *   //   array('id' => 1, ..., 'nodes' => array()),
     *   //   array('id' => 2, ..., 'nodes' => array(
     *   //        array(..., 'parent' => 2, 'nodes' => array()),
     *   //        array(..., 'parent' => 2, 'nodes' => array()),
     *   //   ),
     *   //   array('id' => 3, ..., 'nodes' => array(
     *   //        array('id' => 9, ..., 'parent' => 3, 'nodes' => array(
     *   //             array(..., , 'parent' => 9, 'nodes' => array(),
     *   //        ),
     *   //   ),
     *   // )
     * </code>
     *
     * 如果要获得任意节点为根的子树，可以使用 $refs 参数：
     * <code>
     * $refs = null;
     * $tree = Helper_Array::tree($rows, 'id', 'parent', 'nodes', $refs);
     *
     * // 输出 id 为 3 的节点及其所有子节点
     * $id = 3;
     * print_r($refs[$id]);
     * </code>
     *
     * @param array $arr 数据源
     * @param string $key_node_id 节点ID字段名
     * @param string $key_parent_id 节点父ID字段名
     * @param string $key_childrens 保存子节点的字段名
     * @param boolean $refs 是否在返回结果中包含节点引用
     *
     * return array 树形结构的数组
     */
    static function tree($arr, $key_node_id, $key_parent_id = 'parent_id', $key_childrens = 'childrens', & $refs = null) {
        $refs = array();
        foreach ($arr as $offset => $row) {
            $arr[$offset][$key_childrens] = array();
            $refs[$row[$key_node_id]] = & $arr[$offset];
        }

        $tree = array();
        foreach ($arr as $offset => $row) {
            $parent_id = $row[$key_parent_id];
            if ($parent_id) {
                if (!isset($refs[$parent_id])) {
                    $tree[]                   = & $arr[$offset];
                    continue;
                }
                $parent                   = & $refs[$parent_id];
                $parent[$key_childrens][] = & $arr[$offset];
            } else {
                $tree[] = & $arr[$offset];
            }
        }

        return $tree;
    }

    /**
     * 将树形数组展开为平面的数组
     *
     * 这个方法是 tree() 方法的逆向操作。
     *
     * @param array $tree 树形数组
     * @param string $key_childrens 包含子节点的键名
     *
     * @return array 展开后的数组
     */
    static function treeToArray($tree, $key_childrens = 'childrens') {
        $ret = array();
        if (isset($tree[$key_childrens]) && is_array($tree[$key_childrens])) {
            $childrens = $tree[$key_childrens];
            unset($tree[$key_childrens]);
            $ret[]     = $tree;
            foreach ($childrens as $node) {
                $ret = array_merge($ret, self::treeToArray($node, $key_childrens));
            }
        } else {
            unset($tree[$key_childrens]);
            $ret[] = $tree;
        }
        return $ret;
    }

    /**
     * 根据指定的键对数组排序
     *
     * 用法：
     * <code>
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1', 'parent' => 1),
     *     array('id' => 2, 'value' => '2-1', 'parent' => 1),
     *     array('id' => 3, 'value' => '3-1', 'parent' => 1),
     *     array('id' => 4, 'value' => '4-1', 'parent' => 2),
     *     array('id' => 5, 'value' => '5-1', 'parent' => 2),
     *     array('id' => 6, 'value' => '6-1', 'parent' => 3),
     * );
     *
     * $rows = Helper_Array::sort_by_col($rows, 'id', SORT_DESC);
     * print_r($rows);
     * // 输出结果为：
     * // array(
     * //   array('id' => 6, 'value' => '6-1', 'parent' => 3),
     * //   array('id' => 5, 'value' => '5-1', 'parent' => 2),
     * //   array('id' => 4, 'value' => '4-1', 'parent' => 2),
     * //   array('id' => 3, 'value' => '3-1', 'parent' => 1),
     * //   array('id' => 2, 'value' => '2-1', 'parent' => 1),
     * //   array('id' => 1, 'value' => '1-1', 'parent' => 1),
     * // )
     * </code>
     *
     * @param array $array 要排序的数组
     * @param string $keyname 排序的键
     * @param int $dir 排序方向
     *
     * @return array 排序后的数组
     */
    static function sortByCol($array, $keyname, $dir = SORT_ASC) {
        return self::sortByMultiCols($array, array($keyname => $dir));
    }

    /**
     * 将一个二维数组按照多个列进行排序，类似 SQL 语句中的 ORDER BY
     *
     * 用法：
     * <code>
     * $rows = Helper_Array::sort_by_multiCols($rows, array(
     *     'parent' => SORT_ASC,
     *     'name' => SORT_DESC,
     * ));
     * </code>
     *
     * @param array $rowset 要排序的数组
     * @param array $args 排序的键
     *
     * @return array 排序后的数组
     */
    static function sortByMultiCols($rowset, $args) {
        $sortArray = array();
        $sortRule = '';
        foreach ($args as $sortField => $sortDir) {
            foreach ($rowset as $offset => $row) {
                $sortArray[$sortField][$offset] = $row[$sortField];
            }
            $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
        }
        if (empty($sortArray) || empty($sortRule)) {
            return $rowset;
        }
        eval('array_multisort(' . $sortRule . '$rowset);');
        return $rowset;
    }

    /**
     * 根据key从数组中找到相关值，其中key是依据$delimiter分离的，默认为“.”
     *
     * // 比如获取值： $array['foo']['bar']
     * $value = Helper_Array::path($array, 'foo.bar');
     *
     * 使用 "*"作为匿名
     *
     * // Get the values of "color" in theme
     * $colors = Helper_Array::path($array, 'theme.*.color');
     *
     * @param array  $array   数组
     * @param string $path    path 的分隔符
     * @param string $default default 
     * 
     * @return mixed
     */
    public static function path($array, $path, $default = null) {
        if (array_key_exists($path, $array)) {
            return $array[$path];
        }

        $delimiter = ".";
        //$path = trim($path, "{$delimiter}* ");
        $keys      = explode($delimiter, $path);
        do {
            $key = array_shift($keys);

            if (isset($array[$key])) {
                if ($keys) {
                    if (is_array($array[$key])) {
                        $array = $array[$key];
                    } else {
                        break;
                    }
                } else {
                    return $array[$key];
                }
            } elseif ($key === '*') {
                $values = array();
                $inner_path = implode($delimiter, $keys);
                foreach ($array as $arr) {
                    $value = is_array($arr) ? self::path($arr, $inner_path) : $arr;
                    if ($value) {
                        $values[] = $value;
                    }
                }

                if ($values) {
                    return $values;
                } else {
                    break;
                }
            } else {
                break;
            }
        } while ($keys);

        return $default;
    }

    /**
     * 递归合并两个或多个数组
     * 本函数内使用for语句，以及func_get_arg函数，实现多个数组递归合并
     * $john = array('name' => 'john', 'children' => array('fred', 'paul', 'sally', 'jane'));
     * $mary = array('name' => 'mary', 'children' => array('jane'));
     *
     * $john = Helper_Array::merge($john, $mary);
     *
     * array('name' => 'mary', 'children' => array('fred', 'paul', 'sally', 'jane'))
     *
     * @param array $a1 原始数组
     * @param array $a2 需要合并的数组
     * 
     * @return array
     */
    public static function merge(array $a1, array $a2) {
        $result = array();
        for ($i     = 0, $total = func_num_args(); $i < $total; $i++) {
            $arr   = func_get_arg($i);
            $assoc = Helper_Array::isAssoc($arr);
            foreach ($arr as $key => $val) {
                if (isset($result[$key])) {
                    if (is_array($val) && is_array($result[$key])) {
                        if (Helper_Array::isAssoc($val)) {
                            $result[$key] = Helper_Array::merge($result[$key], $val);
                        } else {
                            $diff         = array_diff($val, $result[$key]);
                            $result[$key] = array_merge($result[$key], $diff);
                        }
                    } else {
                        if ($assoc) {
                            $result[$key] = $val;
                        } elseif (!in_array($val, $result, true)) {
                            $result[] = $val;
                        }
                    }
                } else {
                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }

    /**
     * 是否为关联数组
     *
     * @param array $array to check
     * 
     * @return boolean
     */
    public static function isAssoc(array $array) {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
    
    /**
     * 将对象转换为数组
     *  
     * @param object $object 对象
     * 
     * @return array
     **/
    public static function fromObject($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        } else {
            $array = $object;
        }
        return $array;
    }
    
     
    /**
     * 将多维数组转换为对象
     * 
     * @param array $array 数组 
     * 
     * @return object
     **/
    public static function toObject($array) {
        if (is_array($array)) {
            $obj = new StdClass();
            foreach ($array as $key => $val) {
                $obj->$key = $val;
            }
        } else { 
            $obj = $array; 
        }
        return $obj;
    }
    
}

