<?php

/**
 * 配置文件相关
 *
 * @package Models
 * @author  baojun <baojun4545@sina.com>
 */

class ConfModel extends Abstract_M{

    const BG_GRAY = 'bg_gray';
    
    /**
     *  css ver
     * @var string
     */
    const VERSION_CSS = 'css_version';
    
    /**
     * js ver
     * @var string
     */
    const VERSION_JS  = 'js_version';
    
    /**
     * 使用SDATA
     * @var bool
     */
    const USE_SDATA = true;
    
    /**
     * 获得一条配置
     * 
     * @param string $key key
     * 
     * @return string
     */
    static public function get($key) {
        echo 1;die;
        if (self::USE_SDATA) {
            $result = Comm_Sdata::get(__CLASS__, $key);
        } else {
            $result = false;
        }
        if ($result === false) {
            $data_conf = new Data_Conf();
            $result = $data_conf->get($key);
            
            self::USE_SDATA && Comm_Sdata::set(__CLASS__, $key, $result);
        }
        
        return $result;
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
