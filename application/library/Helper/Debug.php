<?php
/**
 * 调试辅助类
 *
 * @package Helper
 * @author  baojun <baojun@sina.com>
 */

/**
 * inclue fire php library
 * 
 * @package Thrirdpard
 * @author  baojun <baojun@sina.com>
 */
require APP_PATH . 'application/library/Thirdpart/FirePHP.class.php';

abstract class Helper_Debug {
    /**
     * 当前环境类型
     */
    const EVN_UNKNOWN    = 0; //未知
    const EVN_DEBUG      = 1; //开发
    const EVN_TEST       = 2; //测试
    const EVN_SIMULATION = 3; //仿真
    const EVN_PRODUCT    = 4; //线上
    const EVN_GRAY       = 5; //灰度
    
        
    private static $_firephp_enabled = true;

    /**
     * 输出资源使用量
     * 
     * @param boolean $output 是否输出结果
     * 
     * @return mixed
     */
    static public function resource($output = true) {
        $result = array('memory' => self::getMemory());

        $output && self::dump($result);
        return $output ? null : $result;
    }

    /**
     * 取得内存使用量
     * @return int
     */
    static public function getMemory() {
        return memory_get_usage();
    }

    /**
     * 输出变量的内容
     * 如果启用了 FirePHP 支持，将输出到浏览器的 FirePHP 窗口中，不影响页面输出。
     * 可以使用 dump() 这个简写形式。
     *
     * @param mixed   $vars   要输出的变量
     * @param string  $label  标签
     * @param boolean $return 是否返回输出内容
     * 
     * @return mixed
     */
    static public function dump($vars, $label = null, $return = false) {
        if (!$return && self::$_firephp_enabled) {
            if (self::isDebug() && !headers_sent()) {
                self::firephp()->fb($vars, $label);
            }

            return null;
        }
        if (ini_get('html_errors')) {
            $content = "<pre>\n";
            if ($label !== null && $label !== '') {
                $content .= "<strong>{$label} :</strong>\n";
            }
            $content .= htmlspecialchars(print_r($vars, true));
            $content .= "\n</pre>\n";
        } else {
            $content = "\n";
            if ($label !== null && $label !== '') {
                $content .= $label . " :\n";
            }
            $content .= print_r($vars, true) . "\n";
        }
        if ($return) {
            return $content;
        }

        echo $content;
        return null;
    }

    /**
     * 以表格形式输出调试数据
     * 
     * @param array  $vars  二级数组（一般为从数据库中取出的数据）
     * @param string $label 标签
     * 
     * @return string 
     */
    static public function table($vars, $label = 'Dump Table') {
        if (self::isDebug() && self::$_firephp_enabled) {
            self::firephp()->fb($vars, $label, FirePHP::TABLE);
            return;
        }

        $result = '<table border="1">';
        $field  = true;
        foreach ($vars as $row) {
            if ($field) {
                $result .= '<tr>';
                foreach (array_keys($row) as $item) {
                    $result .= "<th>{$item}</th>";
                }
                $result .= '</tr>';
                $field = false;
            }

            $result .= '<tr>';
            foreach ($row as $key => $value) {
                $result .= "<td>{$value}</td>";
            }
            $result .= '</tr>';
        }
        echo $result;
    }

    /**
     * 抛出错误
     * 
     * @param mixed  $vars  要输出的内容
     * @param string $label 标签
     * @param enum   $type  错误类型(FirePHP::ERROR/FirePHP::WARN)
     * 
     * @return void
     */
    static public function error($vars, $label = 'Error', $type = null) {
        if (self::isDebug() && !headers_sent() &&  self::$_firephp_enabled) {

            !$type && $type = FirePHP::ERROR;
            ;
            self::firephp()->fb($vars, $label, $type);
        }
    }

    /**
     * 追踪访问
     * 
     * @param string  $label         label
     * @param boolean $return        是否返回数据，如果不返回，将直接输出
     * @param boolean $force_display 强制将数据显示在页面上而不输出FirePHP
     * 
     * @return mixed
     */
    static public function trace($label = 'trace', $return = false, $force_display = false) {
        if (self::$_firephp_enabled && !$force_display) {
            !headers_sent() && self::firephp()->trace($label);
            return;
        }

        $debug = debug_backtrace();
        $lines = '';
        $index = 0;
        $count = count($debug);
        for ($i     = 0; $i < $count; $i++) {
            if ($i == 0) {
                continue;
            }
            $file = $debug[$i];
            if (!isset($file['file'])) {
                $file['file'] = 'eval';
            }
            if (!isset($file['line'])) {
                $file['line'] = null;
            }
            $line         = "#{$index} <font color='green'>{$file['file']}</font>(<font color='red'>{$file['line']}</font>): ";

            if (isset($file['class'])) {
                $line .= "{$file['class']}{$file['type']}";
            }
            $line .= "{$file['function']}</font>(<font color='red'>";
            if (isset($file['args']) && count($file['args'])) {
                foreach ($file['args'] as $arg) {
                    $line .= gettype($arg) . ', ';
                }
                $line = substr($line, 0, -2);
            }
            $line .= '</font>)';
            $lines .= $line . "\n";
            $index++;
        } // for


        $lines .= "#{$index} {main}\n";

        if (ini_get('html_errors')) {
            $result = nl2br($lines);
        } else {
            $result = $lines;
        }

        if ($return) {
            return $result;
        }
        echo $result;
    }

    /**
     * 取得FirePHP对象
     * @return FirePHP
     */
    static public function firephp() {
        return FirePHP::getInstance(true);
    }

    /**
     * 检测当前模式是否为调试模式
     * @staticvar boolean $result
     * @return boolean
     */
    static public function isDebug() {
        static $result = null;
        if ($result === null) {
            $result = DEVELOP_LEVEL == 1 || DEVELOP_LEVEL == 2 || !empty($_SERVER['SRV_DEBUG_MODE']);
        }

        return $result;
    }

    /**
     * 检测当前模式是否为调试模式
     * @staticvar boolean $result
     * @return boolean
     */
    static public function isProduct() {
        return self::currentEnv() == self::EVN_PRODUCT;
    }
    
    /**
     * 检测当前模式是否为灰度
     * @staticvar boolean $result
     * @return boolean
     */
    static public function isGray() {
        return self::currentEnv() == self::EVN_GRAY;
    }
    
    /**
     * 获取当前debug级别 (0.未知、1.开发、2.内网测试、3.仿真、4.生产)
     */
    static public function currentEnv() {
        return isset($_SERVER['SRV_DEVELOP_LEVEL']) ? $_SERVER['SRV_DEVELOP_LEVEL'] : Helper_Debug::EVN_UNKNOWN;
    }

    /**
     * 开发环境下的危险操作
     * 
     * @param int     $uid         被操作人
     * @param boolean $very_danger 是否非常危险，仿真都要检查（默认为false）
     * 
     * @return boolean 线上环境返回true，开发环境测试用户返回true，其次返回false (安全true, 危险false)
     *
     * @author baojun
     */
    static public function checkDanger($uid, $very_danger = false) {
        
        //线上环境直接返回true
        if (DEVELOP_LEVEL == 4) {
            return true;
        }
        
        //开发环境，或非常危险的仿真环境，白名单内返回true
        if (Helper_Debug::isDebug() || $very_danger) {
            $developer = Comm_Config::get('tester.develop');
            return in_array($uid, $developer['uids']);
        }
        
        //仿真不危险的环境，返回true，其它意外情况，返回false
        return (!Helper_Debug::isDebug() && !$very_danger);
    }

    /**
     * 检查用户是否在测试白名单中
     * 
     * @param int $uid uid
     * 
     * @return bool
     */
    static public function isWhiteUser($uid) {
        $developer = Comm_Config::get('tester.develop');
        return in_array($uid, $developer['uids']);
    }
}