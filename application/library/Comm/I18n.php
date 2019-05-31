<?php
/**
 * 多语言支持
 *
 * @package Comm
 * @author  baojun <zhangbaojun@yixia.com>
 */

class Comm_I18n{
    
    /**
     *
     * @var string 当前语言
     */
    public static $current_lang = 'zh-cn';
    
    /**
     *
     * @var array 按package, file结构进程内缓存避免重复IO
     */
    public static $lang = array ();
    // 语言包
    public static $lang_file = array ();
    
    /**
     * 设置当前的语言
     *
     * @param string $lang 格式：zh-cn
     * 
     * @return string
     */
    public static function setCurrentLang($lang) {
        self::$current_lang = strtolower ( str_replace ( array (' ','_' ), '-', $lang ) );
    }
    
    /**
     * 获取单个或多项目
     *
     * @param string $key     两级dot path
     * @param string $package package 
     * @param string $lang    lang 
     * 
     * @return string
     */
    public static function text($key, $package = '', $lang = null) {
        $lang === null && $lang = self::$current_lang;
        self::load ( $key, $package, $lang );
        
        if (! empty ( $package )) {
            $key = $package . "." . $key;
        }
        $found = Helper_Array::path ( self::$lang, $key, "#not found#" );
        if ($found === "#not found#") {
            if ($lang === 'zh-cn') {
                return str_replace ( 'exception.', '', $key );
            } else {
                // 非简体中文用简体中文再试一次
                return self::text ( $key, $package, 'zh-cn' );
            }
        } else {
            return $found;
        }
    }
    
    /**
     * 获取需要替换的语言包
     *
     * @param string $key  key
     * @param string $val1 value 1
     * @param string $val2 value 2
     *       
     * @return string
     */
    public static function dynamicText($key, $val1, $val2 = null) {
        $args = func_get_args ();
        array_shift ( $args );
        $text = self::text ( $key );
        return vsprintf ( $text, $args );
    }
    
    /**
     * 获取单个或多项目(同::text)
     *
     * @param string $key     两级dot path
     * @param string $package package
     *       
     * @return string
     */
    public static function _($key, $package = '') {
        return self::text ( $key, $package );
    }
    
    /**
     * 返回指定语言和分组的所有信息
     *
     * @param string $key     需要载入的语言
     * @param string $package 分组
     * @param string $lang    语言选项
     *       
     * @return array
     */
    public static function load($key, $package = "", $lang = null) {
        if ($lang === null) {
            $lang = self::$current_lang;
        }
        
        if (! empty ( $package )) {
            if (Helper_Array::path ( self::$lang, $package . '.' . $key ) !== null) {
                return Helper_Array::path ( self::$lang, $package . '.' . $key );
            }
        } else {
            if (Helper_Array::path ( self::$lang, $key ) !== null) {
                return Helper_Array::path ( self::$lang, $key );
            }
        }
        $files = explode ( '.', $key );
        $path = APP_PATH . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;
        if (! empty ( $package )) {
            $path .= $package . DIRECTORY_SEPARATOR;
            $file_path = $package . '.';
        } else {
            $file_path = '';
        }
        $t = array ();
        
        array_pop ( $files );
        if (file_exists ( $path . implode ( DIRECTORY_SEPARATOR, $files ) . '.ini' )) {
            $keys = $files;
            if ($package) {
                array_unshift ( $keys, $package );
            }
            $keys = array_reverse ( $keys );
            $arr = array ();
            $count = count ( $keys );
            $i = 1;
            foreach ( $keys as $file ) {
                if ($i ++ == 1) {
                    $arr = self::loadLangFile ( $path . implode ( DIRECTORY_SEPARATOR, $files ) . '.ini' );
                } else {
                    $arr = $t;
                }
                if (isset ( $t[$file] ) && is_array ( $t[$file] )) {
                    $t[$file] = Helper_Array::merge ( $t[$file], $arr );
                } else {
                    $t = array ();
                    $t[$file] = $arr;
                }
            }
        }
        
        if ($t) {
            self::$lang = Helper_Array::merge ( self::$lang, $t );
        }
        
        if (! empty ( $package )) {
            if (Helper_Array::path ( self::$lang, $package . '.' . $key ) !== null) {
                return Helper_Array::path ( self::$lang, $package . '.' . $key );
            }
        } else {
            if (Helper_Array::path ( self::$lang, $key ) !== null) {
                return Helper_Array::path ( self::$lang, $key );
            }
        }
        // return $t;
    }
    
    /**
     * 加载语广义民文件
     *
     * @param string $file file
     *       
     * @return array
     */
    protected static function loadLangFile($file) {
        if (! isset ( self::$lang_file[$file] )) {
            $content = parse_ini_file ( $file );
            self::$lang_file[$file] = $content;
        } else {
            $content = self::$lang_file[$file];
        }
        return $content;
    }
}
