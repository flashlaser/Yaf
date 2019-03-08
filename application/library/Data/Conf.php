<?php

/**
 * 获得配置信息数据层
 *
 * @package Data
 * @author  baojun <baojun4545@sina.com>
 */
class Data_Conf extends Abstract_Data {

    /**
     * 添加配置信息
     *
     * @param string $name 名称
     * @param string $val  值
     *
     * @return bool
     */
    public function add($name, $val) {
        return Comm_Db::d(Comm_Db::DB_BASIC)->insert(
            Comm_Db::t('conf'), array('k' => $name, 'v' => $val), true
        );
    }

    /**
     * 获得一条配置信息
     *
     * @param string $name 名称
     *
     * @return string
     */
    public function get($name) {
        $info = Comm_Mc::init()->getData('conf', array($name));
        if (!$info) {
            $sql  = 'SELECT `v` FROM ' . Comm_Db::t('conf') . ' WHERE `k` = ? ';
            $info = Comm_Db::d(Comm_Db::DB_BASIC)->fetchOne($sql, $name);
            Comm_Mc::init()->setData('conf', array($name), $info);
        }
        return $info;
    }

    /**
     * 修改配置信息
     *
     * @param string $name 名称
     * @param string $val  值
     *
     * @return bool
     */
    public function modify($name, $val) {
        $result = Comm_Db::d(Comm_Db::DB_BASIC)->update(
            Comm_Db::t('conf'), array('v' => $val), 'k = ?', array($name)
        );

        Comm_Mc::init()->setData('conf', array($name), $val);

        return $result;
    }

}
