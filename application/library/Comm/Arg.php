<?php

/**
 * 参数校验
 * 调用实例：
 *     Comm_Arg::init('timestamp')->int($r->getQuery('t'), 'basic', 3, 3);
 *     Comm_Arg::init('timestamp', array('','500001'))->int($r->getQuery('t'), 'len,10', 3, 3);
 *     Comm_Arg::init('timestamp', array('500001',''))->int($r->getQuery('t'), 'len,10', 3, 3);
 *     Comm_Arg::init('timestamp', array('500001','500002'))->int($r->getQuery('t'), 'len,10', 3, 3);
 *     
 * @package Comm
 * @author  baojun <baojun@rkylin.com.cn>
 */
class Comm_Arg {
    
    /**
     * param name
     *
     * @var string
     */
    protected $name;
    
    /**
     * error code defined
     *
     * @var array
     */
    protected $code;
    protected $config = array('int', 'string', 'enum', 'float', 'datalist', 'arr');

    /**
     * 获取当前用户操作对象（单例模式）
     *  
     * @param string $name name 
     * @param array  $code code
     * 
     * @return Comm_Arg
     */
    static public function init($name = null, $code = array()) {
        $obj = new self();
        $obj->name = $name;
        $obj->code = $code;
        
        return $obj;
    }

    /**
     * 动态调用Comm_Argchecker方法
     *
     * @param string $method 操作方法
     * @param array  $param  param
     *          
     * @return bool
     */
    public function __call($method, $param = null) {
        if (!in_array($method, $this->config)) {
            throw new Exception_Arg('argchecker_method_not_exist');
        }
        $param && !is_array($param) && $param = ( array ) $param;
        $argchecker = new Comm_Argchecker();
        try {
            $result = call_user_func_array(array($argchecker, $method), $param);
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code == '211001') {
                if (isset($this->code[0]) && !empty($this->code[0])) {
                    throw new Exception_Arg($this->code[0]);
                }
                $message = sprintf(Comm_I18n::text('errcode.211001'), $this->name);
                throw new Exception_Arg($code, $message);
            } elseif ($code == '211002') {
                if (isset($this->code[1]) && !empty($this->code[1])) {
                    throw new Exception_Arg($this->code[1]);
                }
                $ac_type = $method . '[' . $param[1] . ']';
                $message = sprintf(Comm_I18n::text('errcode.211002'), $this->name, $ac_type, $param[0]);
                throw new Exception_Arg($code, $message);
            } else {
                throw new Exception_Arg('211001', $e->getMessage());
            }
        }
        
        return $result;
    }
}