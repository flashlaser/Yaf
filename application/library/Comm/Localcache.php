<?php

/**
 * 本地缓存类，用于缓存不经常变动的数据对象
 *
 * @package Comm
 * @author  baojun <baojun4545@sina.com>
 */
class Comm_Localcache {

    const CACHE_DURATION_DAY  = 86400;
    const CACHE_DURATION_HOUR = 3600;

    /**
     * 版本号
     * @var int
     */
    private static $_version = 1;

    /**
     * 获取缓存根目录
     * 
     * @return string
     */
    public static function getCacheDir() {
        //SRV_CACHE_DIR必须以/结尾
        return isset($_SERVER['SRV_CACHE_DIR']) ? $_SERVER['SRV_CACHE_DIR'] : '/tmp';
    }

    /**
     * 获取缓存路径
     * 
     * @param string $ns              namespace 
     * @param string $key             key
     * @param bool   $auto_create_dir 是否自动创建目录
     * 
     * @return  string
     */
    public static function getCachePath($ns, $key, $auto_create_dir=true) {
        $save_dir = self::getCacheDir() . $ns;
        if ($auto_create_dir && !is_dir($save_dir)) {
            if (!mkdir($save_dir, 0755)) {
                $cache_file = 'local-cache-dir';
                $file_path = Helper_Log::getAppLogFilePath($cache_file);
                if (!is_file($file_path)) {
                    $msg = sprintf('Failed to create dir %s ', $save_dir);
                    Helper_Smtp::warning('本地缓存目录创建失败', $msg);
                    Helper_Log::writeApplog($cache_file, $msg, 0);
                }

                return '/tmp';
            }
        }

        return $save_dir . '/' . $key;
    }

    /**
     * 向本地缓存中写数据
     *
     * @param string $ns       namespace, 建议为__CLASS__, 注意，不支持多级目录，namespace不能为 xxx/xxx的形式
     * @param string $key      key
     * @param object $value    php对象
     * @param int    $expire   过期时间 Comm_Localcache::CACHE_DURATION_DAY | Comm_Localcache::CACHE_DURATION_HOUR
     * @param bool   $compress 是否压缩，对于较大的缓存对象压缩后读取性能会比较好
     * @param bool   $fix_time 是否采用整点过期策略。如果使用整点过期策略，则过期时间为每天的3点，或者下一个小时的整点
     * 
     * @return  bool       true 成功; false 失败
     */
    public static function write($ns, $key, $value, $expire, $compress = true, $fix_time = true) {

        $content               = array();
        $content['version']    = 1;
        $content['compressed'] = $compress;
        
        if ($fix_time) {
            //整点过期策略      
            if ($expire == Comm_Localcache::CACHE_DURATION_HOUR) {
                $content['expire'] = strtotime(date('Y-m-d H:00:00', time())) + 3600;
            } else { //Comm_Localcache::CACHE_DURATION_DAY
                $current_time = time();
                $expire_time = strtotime(date('Y-m-d 03:00:00', $current_time));
                $content['expire'] = ($current_time > $expire_time) ? $expire_time+86400 : $expire;
            }
        } else {
            $content['expire'] = time() + $expire;
        }

        $content['value'] = $compress ? gzcompress(serialize($value)) : $value;
        $cache_path       = self::getCachePath($ns, $key);

        $data = serialize($content);
        if (!file_put_contents($cache_path, $data, LOCK_EX)) {
            $cache_file = 'local-cache';
            $file_path  = Helper_Log::getAppLogFilePath($cache_file);
            if (!is_file($file_path)) {
                $msg = sprintf('Failed to write "%s" Length:%d ns:%s key:%s ', $cache_path, strlen($data), $ns, $key);
                Helper_Smtp::warning('本地缓存写入失败', $msg);
                Helper_Log::writeApplog($cache_file, $msg, 0);
            }

            return false;
        }

        return true;
    }

    /**
     * 从本地缓存中读取数据
     * 
     * @param unknown $ns  ns
     * @param unknown $key key
     * 
     * @return boolean|mixed
     */
    public static function read($ns, $key) {
        $cachePath = self::getCachePath($ns, $key, false);
        if (!file_exists($cachePath)) {
            return false;
        }

        $data    = file_get_contents($cachePath);
        $content = unserialize($data);

        if ($content['version'] == self::$_version && $content['expire'] > time()) {
            return $content['compressed'] ? unserialize(gzuncompress($content['value'])) : $content['value'];
        }

        return false;
    }

    /**
     * 删除缓存
     *
     * @param string $ns  namespace
     * @param string $key key
     * 
     * @return bool 是否成功
     */
    public static function clean($ns, $key) {
        return unlink(self::getCachePath($ns, $key));
    }

    //TODO: 未使用，暂时不实现
    //    public static function cleanCacheByNs($ns) {
    //        
    //    }
} 
