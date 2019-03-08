<?php

/**
 * 配置文件相关
 *
 * @package Models
 * @author  baojun <baojun4545@sina.com>
 */

class SyncModel extends Abstract_M{

    static public function getModuleName($shopId) 
    {
        $data_shops = new Data_Shops();
        return $data_shops->getModule($shopId);
    }

    /**
     * [获得店铺配置]
     * @sonj     Tse
     * @DateTime 2018-08-03T15:01:18+0800
     * @param    [type]                   $shop_id [description]
     * @return   [type]                            [description]
     */
    static public function getConfigure($shop_id)
    {
        $data_shops = new Data_Shops();
        return $data_shops->getConfigure($shop_id);
    }

    /**
     * 写值
     * 
     * @param string $key   key
     * @param string $value value
     * 
     * @return mixed
     */
    static public function set($key, $value) {
        $result = self::get($key);

        $data_conf = new Data_Conf();
        if ( ! $result) {
            $query = $data_conf->add($key, $value);
        }

        if ($result || empty($query)) {
            $query = $data_conf->modify($key, $value);
        }
        
        self::USE_SDATA && Comm_Sdata::set(__CLASS__, $key, $value);

        return $query;
    }

}
