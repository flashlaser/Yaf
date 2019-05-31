<?php
/**
 * 配置类
 *
 * @package   Swift
 * @author    baojun <zhangbaojun@yixia.com>
 * @copyright 2016 www.yixia.com all rights reserved
 */

class Comm_Config {
    
    /**
     * 加载指定的配置文件
     *
     * @param string $config_file 映射configuration文件名
     * 
     * @return array
     */
    public static function load($config_file) {
        $file = self::swiftFindFile('config', $config_file);
        if (empty($file)) {
            throw new Comm_Exception_Program("config file not exists");
        }
        $config = array();
        $config = Comm_Array::merge($config, self::swiftLoad($file));
        return $config;
    }

    /**
     * find file 
     * 
     * @param unknown $dir  dir
     * @param unknown $file file 
     * 
     * @return boolean|string
     */
    public static function swiftFindFile($dir, $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file . ".php";
        $found = false;
        $paths = array(APP_PATH . DIRECTORY_SEPARATOR . 'application');
        foreach ($paths as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $path)) {
                $found = $dir . DIRECTORY_SEPARATOR . $path;
                break;
            }
        }
        return $found;
    }

    /**
     * load file
     * 
     * @param unknown $file file 
     *
     * @return file
     */
    public static function swiftLoad($file) {
        return include $file;
    }

    /**
     * 读取配置信息
     *
     * @param string $path 节点路径，第一个是文件名，使用点号分隔。如:"app","app.product.routes"
     *
     * @return array/string    成功返回数组或string
     */
    static public function get($path) {
        $arr = explode('.', $path, 2);
        try {
            $static_key = 'get_' . $arr[0];
            $conf = Comm_Sdata::get(__CLASS__, $static_key);
            if ($conf === false) {
                $conf = new Yaf_Config_ini(APP_PATH . '/conf/' . $arr[0] . '.ini');
                Comm_Sdata::set(__CLASS__, $static_key, $conf);
            }
        } catch (Exception $e) {
        }
        !empty($arr[1]) && !empty($conf) && $conf = $conf->get($arr[1]);
        
        if (!isset($conf) || is_null($conf)) {
            throw new Exception_System(200401, "读取的配置信息不存在", array('path' => $path));
        }
    
        return is_object($conf) ? $conf->toArray() : $conf;
    }
    
    /**
     * 读取配置信息
     *
     * @param string $path 节点路径，第一个是文件名，使用点号分隔。如:"app","app.product.routes"
     *
     * @return array/string    成功返回数组或string
     */
    static public function getConf($path) {
    	return self::get($path);
    }
    
    /**
     * 读取配置信息（使用静态数据缓存）
     *
     * @param string $path 节点路径，第一个是文件名，使用点号分隔。如:"app","app.product.routes"
     *
     * @return array/string    成功返回数组或string
     */
    static public function getUseStatic($path) {
        $static_key = 'get_' . $path;
    
        $result = Comm_Sdata::get(__CLASS__, $static_key);
        if ($result === false) {
            $result = self::getConf($path);
            Comm_Sdata::set(__CLASS__, $static_key, $result);
        }
        return $result;
    }
    
}
