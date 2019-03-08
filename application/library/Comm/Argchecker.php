<?php

/**
 * 参数校验
 *
 * @package Comm
 * @author  wangying <wangying7@satff.sina.com>
 */
class Comm_Argchecker {

    const OPT_NO_DEFAULT    = 1;
    const OPT_USE_DEFAULT   = 2;
    const NEED              = 3;
    const WRONG_NO_DEFAULT  = 1;
    const WRONG_USE_DEFAULT = 2;
    const RIGHT             = 3;

    /**
     * 检查int
     *
     * @param mixed  $data         数据
     * @param string $rule         rule规则。如"max,5;min,-3;"。
     * @param enum   $is_needed    是否必须有
     * @param enum   $must_correct 是否必须对
     * @param mixed  $default      默认值
     *
     * @return bool
     *
     * @throws Exception_Arg
     */
    public static function int($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_Int', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 检查字符串
     *
     * @param mixed  $data         //
     * @param string $rule         //
     * @param enum   $is_needed    //
     * @param enum   $must_correct //
     * @param mixed  $default      //
     *
     * @return bool
     */
    public static function string($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_String', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 检查浮点类型
     *
     * @param mixed  $data         /
     * @param string $rule         /
     * @param enum   $is_needed    /
     * @param enum   $must_correct /
     * @param mixed  $default      /
     *
     * @return bool
     */
    public static function float($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_Float', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 检查枚举类型
     *
     * @param mixed  $data         /
     * @param string $rule         /
     * @param enum   $is_needed    /
     * @param enum   $must_correct /
     * @param mixed  $default      /
     *
     * @return bool
     */
    public static function enum($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_Enum', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 检查多重数据
     *
     * 如果规则的值里面包含逗号和分号，则需要将里面的逗号和分号转义为 \,和\;，否则会导致规则出错。比如：
     * 		delimeter,\,  //以 ,为delimeter规则的参数
     * 		delimeter2,\,,\;;delimeter3,'	//以","和";"分别为delimeter2规则的第一个参数和第二个参数，以"'"为delimeter3规则的第三个参数
     *
     * @param mixed  $data         /
     * @param string $rule         /
     * @param enum   $is_needed    /
     * @param enum   $must_correct /
     * @param mixed  $default      /
     *
     * @return bool
     */
    public static function datalist($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_Datalist', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 递归检查数组中的数据
     *
     * @param mixed  $data         /
     * @param string $rule         /
     * @param enum   $is_needed    /
     * @param enum   $must_correct /
     * @param mixed  $default      /
     *
     * @return bool
     */
    public static function arr($data, $rule, $is_needed = 1, $must_correct = 1, $default = null) {
        return self::runChecker('Comm_Argchecker_Array', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 将转义后的,和;解除转义
     *
     * @param string $data /
     * 
     * @return string
     */
    public static function extractEscapedChars($data) {
        return str_replace(array('\,', '\;'), array(',', ';'), $data);
    }

    /**
     * run checker
     * 
     * @param unknown $argchecker_type type
     * @param unknown $data            data 
     * @param unknown $rule            rule
     * @param unknown $is_needed       is need
     * @param unknown $must_correct    is must correct 
     * @param unknown $default         default 
     * 
     * @return NULL|unknown|boolean|mixed|sting|array
     */
    protected static function runChecker($argchecker_type, $data, $rule, $is_needed, $must_correct, $default) {
        if (($return_data = self::getValue($data, $is_needed, $default)) !== true) {
            return $return_data;
        }

        $parse_rules = self::parseRules($argchecker_type, $rule);
        if ($parse_rules) {
            $data = self::validate($argchecker_type, $parse_rules, $data, $must_correct, $default);
        }

        return self::getReturn($data, $is_needed, $must_correct, $default);
    }

    /**
     * pare rules
     * 
     * @param unknown $argchecker_type type
     * @param unknown $rules           rules
     * 
     * @throws Exception_Arg
     * @return string[][]|mixed[][]|unknown[][]
     */
    protected static function parseRules($argchecker_type, $rules) {
        $rules = preg_split('#(?<!\\\\);#', $rules);
        if (class_exists($argchecker_type) && method_exists($argchecker_type, 'basic')) {
            //        another possible approach to avoid the method_exists memleak under php <= 5.2.9
            //        $class = new ReflectionClass($argchecker_type);
            //        if ($class->hasMethod('basic')) {
            $parse_rules = array(array('method' => 'basic', 'para'   => array()));
        } else {
            $parse_rules = array();
        }
        if ($rules) {
            foreach ($rules as $rule) {
                $rule        = preg_split('#(?<!\\\\),#', $rule);
                $method_name = array_shift($rule);
                if (!$method_name) {
                    continue;
                }
                if (!method_exists($argchecker_type, $method_name)) {
                    throw new Exception_Arg('argchecker_method_not_exist');
                } else {
                    $parse_rules[] = array('method' => $method_name, 'para'   => $rule);
                }
            }
        }
        return $parse_rules;
    }

    /**
     * get value 
     * 
     * @param unknown $data      data
     * @param unknown $is_needed is need
     * @param unknown $default   default
     *  
     * @throws Exception_Msg
     * @throws Exception_Msg
     * @return NULL|unknown|boolean
     */
    protected static function getValue($data, $is_needed, $default) {
        if (!in_array($is_needed, array(self::OPT_NO_DEFAULT, self::OPT_USE_DEFAULT, self::NEED))) {
            $is_needed = htmlspecialchars($is_needed);
            throw new Exception_Msg(200404, "Argchecker is_needed must be in (1,2,3).", array('is_needed' => $is_needed));
        }
        if ($data === null || $data === '') {
            // 可以没有，且不需要使用默认值
            if ($is_needed == self::OPT_NO_DEFAULT) {
                return null;
            }
            // 可以没有，且需要使用默认值
            if ($is_needed == self::OPT_USE_DEFAULT) {
                return $default;
            }
            // 必须要有
            if ($is_needed == self::NEED) {
                throw new Exception_Msg('211001');
            }
        }
        return true;
    }

    /**
     * validate 
     * 
     * @param unknown $argchecker_type type
     * @param unknown $rules           rules
     * @param unknown $data            data 
     * @param unknown $is_correct      is conrrect 
     * @param unknown $default         default 
     * 
     * @throws Exception_Msg
     * @return NULL|unknown
     */
    protected static function validate($argchecker_type, $rules, $data, $is_correct, $default) {
        if (!in_array($is_correct, array(self::RIGHT, self::WRONG_NO_DEFAULT, self::WRONG_USE_DEFAULT))) {
            throw new Exception_Msg('argchecker_PARAM_ERROR');
        }

        foreach ($rules as $rule) {
            if (!$rule) {
                continue;
            }
            array_unshift($rule['para'], $data);
            $rst = call_user_func_array(array($argchecker_type, $rule['method']), $rule['para']);
            if ($rst === false) {
                break;
            }
        }
        if ($rst === false) {
            // 可以不对，且不需要使用默认值
            if ($is_correct == self::WRONG_NO_DEFAULT) {
                return null;
            }
            // 可以不对，且需要使用默认值
            if ($is_correct == self::WRONG_USE_DEFAULT) {
                return $default;
            }
            // 必须要对
            if ($is_correct == self::RIGHT) {
                throw new Exception_Msg('211002');
            }
        }
        return $data;
    }

    /**
     * get return 
     * 
     * @param array   $data       data 
     * @param boolean $is_needed  is need
     * @param boolean $is_correct is correct 
     * @param sting   $default    default 
     * 
     * @return mixed
     */
    protected  static function getReturn($data, $is_needed, $is_correct, $default) {
        if ($data === null && ($is_needed == self::OPT_USE_DEFAULT || $is_correct == self::WRONG_USE_DEFAULT)) {
            return $default;
        }
        return $data;
    }

}