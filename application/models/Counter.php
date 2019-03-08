<?php
/**
 * 计数器
 *
 * @package   model
 * @author    baojun <baojun4545@sina.com>
 * @copyright 2014 Yixia.com all rights reserved
 */

class CounterModel extends Abstract_M {

    /**
     * counter base type
     * @var string
     */
    static private $_type;
    
    /**
     * counter type
     * @var string
     */
    static private $_counter_type;
    
    /**
     * type config
     * 
     * @var array
     */
    static private $_counter_conf;
    
    /**
     * use db
     * @var bool
     */
    static private $_use_db = true;
    
    /**
     * use redis
     * @var bool
     */
    static private $_use_redis = false;
    
    /**
     * init 
     * 
     * @param unknown $alias alias
     * 
     * @return CounterModel
     */
    static public function init($alias) {
        $this->_checkType($alias);

        $obj = new self;
        
        return $obj;
    }

    /**
     * set 
     * 
     * @param string $key   key
     * @param int    $args  args
     * @param int    $value value
     * 
     * @return number
     */
    public static function set($key, $args, $value=1) {
        $config = Comm_Config::get("counter.{$key}");
        //self::_checkData(self::$_type, $data, false);
        $d = new Data_Counter($config);
        
        return $d->set($key, $args, $value);
    }
    
    /**
     * set
     *
     * @param string $key   key
     * @param int    $args  args
     * @param int    $value value
     * 
     * @return number
     */
    public static function replace($key, $args, $value) {
        $config = Comm_Config::get("counter.{$key}");
        //self::_checkData(self::$_type, $data, false);
        $d = new Data_Counter($config);
    
        return $d->replace($key, $args, $value, true);
    }
    
    /**
     * incr
     * 
     * @param string $key   key
     * @param int    $args  args
     * @param int    $value value
     * 
     * @return number
     */
    public static function incr($key, $args, $value=1) {
        return self::set($key, $args, $value);;
    }
    
    /**
     * decr
     * 
     * @param string $key   key
     * @param int    $args  args
     * @param int    $value value
     * 
     * @return number
     */
    public static function decr($key, $args, $value=1) {
        return self::set($key, $args, -abs($value));
    }
    
    /**
     * get 
     * 
     * @param string $key  key
     * @param int    $args args
     * 
     * @return Ambigous <int/array, number, multitype:Ambigous <number, mixed> >
     */
    public static function get($key, $args) {
        $r =  self::gets($key, $args);

        return array_pop($r);
    }
    
    /**
     * get
     *
     * @param string $key  key
     * @param int    $args args
     * 
     * @return Ambigous <int/array, number, multitype:Ambigous <number, mixed> >
     */
    public static function gets($key, $args) {
        $config = Comm_Config::get("counter.{$key}");
        //self::_checkData(self::$_type, $data, false);
        $d = new Data_Counter($config);
        
        return $d->gets($key, $args);
    }
    
    /**
     * 检查计数器类型参数，有错误将抛异常
     * 
     * @param string $alias type
     * 
     * @return type
     */
     private function _checkType($alias) {
         $config = Comm_Config::get("counter.{$alias}");
         self::$_counter_type = $alias;
        /*
        !is_array($count_type) && $count_type = array($count_type);
        
        $config = Comm_Config::get("counter");
        if (!isset($config[$type])) {
            //计数器配置错误：指定计数器不存在
            throw new Exception_System('200201', null, array(
                    'f'        => __FILE__,
                    'l'        => __LINE__,
                    'type'     => $type,
                    'arr_type' => $config,
            ));
        }
        $counter_conf = $config[$type];
        $type_config = array_keys($counter_conf['type']);
        foreach ($count_type as $v) {
            if ( ! in_array($v, $type_config)) {
                throw new Exception_Arg(null, array(
                    '__LINK__' => __LINE__,
                    '__FILE__' => __FILE__,
                    'check_name' => $type,
                    'count_type' => $count_type,
                ));
            }
        }*/
        
        if (isset($config['use_db']) && $config['use_db']) {
            self::$_use_db = true;
        } elseif (isset($config['use_redis']) && $config['use_redis']) {
            self::$_use_redis = true;   
        }
        //self::$_type = $type;
        self::$_counter_conf  = $config;
        
        return true;
    }
    
    /**
     * check set/get/replace data
     * 
     * @param string $type    type
     * @param array  $arr_key eky 
     * @param bool   $is_set  is set
     * 
     * @throws Exception_System
     * @throws Exception_Msg
     * @return boolean
     */
    private function _checkData($type, array $arr_key, $is_set=true) {
        $arrfield  = '';//是数组的字段名
        //检查必须参数
        foreach (self::$_counter_conf['col'] as $k => $arg_type) {
            if (!isset($arr_key[$k])) {
                //200406="参数异常"
                throw new Exception_System('200406', null, array(
                        'f'       => __FILE__,
                        'l'       => __LINE__,
                        'type'    => $type,
                        'key'     => $k,
                        'arr_key' => $arr_key,
                ));
            }
        
            if (!is_array($arr_key[$k])) {
                $list = array($arr_key[$k]);
            } elseif ($arg_type == 'int_or_array' && !$is_set) {
                $list     = $arr_key[$k];
                $arrfield = $k;
            } else {
                if ($is_set) {
                    throw new Exception_Msg('301004');//计数器不能批量set
                } else {
                    throw new Exception_Msg('301005');//此计数器不支持批量get
                }
            }

            //计数器参数错误：批量get时量不能超过100
            if (count($list) > 100) {
                throw new Exception_Msg('301006', null, $list);
            }

            foreach ($list as $v) {
                if (is_array($arg_type)) {//只允许数组内的值
                    if (!in_array($v, $arg_type)) {
                        throw new Exception_Msg('301006', null, array('type值非法', $v, $conf));
                    }
                } elseif (!Helper_Validator::isNumint($v)) {
                    ;//throw new Exception_Msg('301006');//计数器参数错误：key必须是num
                }
            }
            unset($arr_key[$k]);
        }
        //检查是否有多余参数
        /*if ($arr_key) {
            //计数器配置错误：有多余key
            throw new Exception_System('200203', null, array(
                    'f'       => __FILE__,
                    'l'       => __LINE__,
                    'type'    => $type,
                    'arr_key' => $arr_key,
            ));
        }*/
        
        return true;
    }

}
