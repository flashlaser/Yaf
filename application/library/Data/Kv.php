<?php

/**
 * 获得配置信息数据层
 *
 * @package Data
 * @author  张宝军 <baojun4545@sina.com>
 */

class Data_Kv extends Abstract_Data{

    /**
     * 添加配置信息
     *
     * @param string $name 名称
     * @param string $val  值
     *       
     * @return bool
     */
    public function add($name, $val) {
        $val = serialize($val);
        return Comm_Db::d(Comm_Db::DB_BASIC)->insert(Comm_Db::t('kv'), array ('k' => $name, 'v' => $val), true);
    }

    /**
     * 获得一条配置信息
     *
     * @param string $key 名称
     *       
     * @return string
     */
    public function get($key) {
        $val = Comm_Mc::init()->getData('kv', array ($key));
        if ($val === false) {
            $sql = 'SELECT `v` FROM ' . Comm_Db::t('kv') . ' WHERE `k` = ? ';
            $val = Comm_Db::d(Comm_Db::DB_BASIC)->fetchOne($sql, $key);
            if ($val !== false) {
                $val = unserialize($val);
                $ret = Comm_Mc::init()->setData('kv', array ($key), $val);
            }
        }
        return $val;
    }

    /**
     * 修改配置信息
     *
     * @param string $key 名称
     * @param string $val 值
     *       
     * @return bool
     */
    public function modify($key, $val) {
        $result = Comm_Db::d(Comm_Db::DB_BASIC)->update(Comm_Db::t('kv'), array ('v' => serialize($val)), 'k = ?', array ($key));
        $ret = Comm_Mc::init()->setData('kv', array ($key), $val);
        if (! $ret) {
            Comm_Mc::init()->deleteData('kv', array ($key));
        }
        return $result;
    }
}
