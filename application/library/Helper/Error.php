<?php
/**
 * 错误处理
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Helper_Error{

    //PHP错误翻译
    protected static $php_errors = array(
        E_ERROR   => 'Fatal_Error',
        E_WARNING => 'Warning',
        E_PARSE   => 'Parse_Error',
        E_NOTICE  => 'Notice',
//		E_DEPRECATED	=> 'Deprecated',

        E_USER_ERROR   => 'User_Error',
        E_USER_WARNING => 'User_Warning',
        E_USER_NOTICE  => 'User_Notice',
//		E_USER_DEPRECATED	=> 'User_DEPRECATED',

        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable_Error',
    );

    /**
     * 错误错误代码所对应的文案信息
     * 
     * @param type $code code
     * 
     * @return type
     */
    static public function showType($code) {
        return isset(self::$php_errors[$code]) ? self::$php_errors[$code] : $code;
    }

    /**
     * 将异常写入错误日志
     * 
     * @param Exception $e exeption 
     * 
     * @return mixed
     */
    static public function writeExceptionLog(Exception $e) {
        $error = $e->getMessage();

        $code     = $e->getCode();
        $file     = $e->getFile();
        $line     = $e->getLine();
        $metadata = var_export($e->getMetadata(), true);

        $class_name = get_class($e);
        $content    = "{$class_name}[{$code}]:{$error} @{$file}[{$line}]\n{$metadata}\n";

        //系统级别异常记录调用过程
        if ($e instanceof Exception_System) {
            $content .= $e->getTraceAsString();
        }

        Helper_Log::writeApplog($class_name, $content, FILE_APPEND, 100000);
    }

    //-------------------- 以下内容为输出详细错误页面使用 --------------------

    /**
     * 生成exception信息
     * 将实际路径替换为LIBPATH、APPPATH、SWFPATH
     * Exception [ Code ] File [ Line x ] : Message
     *
     * @param object $e Exception
     * 
     * @return string
     */
    public static function exceptionText(Exception $e) {
        $text = sprintf('%s [ %s ] %s [ line %d ]', get_class($e), $e->getCode(), $e->getFile(), $e->getLine());

        $msg = strip_tags($e->getMessage());
        if (!empty($msg)) {
            $text .= " : " . $msg;
        }

        return $text;
    }

    /**
     * 返回展现跟踪中每个步骤的HTML字符串
     *
     * @param array $trace to debug
     * 
     * @return string
     */
    public static function trace(array $trace = null) {
        if ($trace === null) {
            $trace = debug_backtrace();
        }

        $statements = array('include', 'include_once', 'require', 'require_once');

        $output = array();
        foreach ($trace as $step) {
            if (!isset($step['function'])) {
                continue;
            }

            if (isset($step['file']) and isset($step['line'])) {
                $source = self::debugSource($step['file'], $step['line']);
            }

            if (isset($step['file'])) {
                $file = $step['file'];

                if (isset($step['line'])) {
                    $line = $step['line'];
                }
            }

            // function()
            $function = $step['function'];

            if (in_array($step['function'], $statements)) {
                if (empty($step['args'])) {
                    $args = array();
                } else {
                    $args = array($step['args'][0]);
                }
            } elseif (isset($step['args'])) {
                if (!function_exists($step['function']) or strpos($step['function'], '{closure}') !== false) {
                    // Introspection on closures or language constructs in a stack trace is impossible
                    $params = null;
                } else {
                    if (isset($step['class'])) {
                        if (method_exists($step['class'], $step['function'])) {
                            $reflection = new ReflectionMethod($step['class'], $step['function']);
                        } else {
                            $reflection = new ReflectionMethod($step['class'], '__call');
                        }
                    } else {
                        $reflection = new ReflectionFunction($step['function']);
                    }

                    $params = $reflection->getParameters();
                }
                $args   = array();

                foreach ($step['args'] as $i => $arg) {
                    if (isset($params[$i])) {
                        $args[$params[$i]->name] = $arg;
                    } else {
                        // Assign the argument by number
                        $args[$i] = $arg;
                    }
                }
            }

            if (isset($step['class'])) {
                // Class->method() or Class::method()
                $function = $step['class'] . $step['type'] . $step['function'];
            }

            $output[] = array('function' => $function, 'args'     => isset($args) ? $args : null, 'file'     => isset($file) ? $file : null, 'line'     => isset($line) ? $line : null, 'source'   => isset($source) ? $source : null);

            unset($function, $args, $file, $line, $source);
        }

        return $output;
    }

    /**
     * 返回HTML字符串
     * 高亮显示文件中指定的行
     *
     * @param string  $file        to open
     * @param integer $line_number to highlight
     * @param integer $padding     number of padding lines
     * 
     * @return string source of file
     * @return false file is unreadable
     */
    public static function debugSource($file, $line_number, $padding = 5) {
        if (!$file or !is_readable($file)) {
            return false;
        }

        $file = fopen($file, 'r');
        $line = 0;

        $range = array('start' => $line_number - $padding, 'end'   => $line_number + $padding);

        $format = '% ' . strlen($range['end']) . 'd';

        $source = '';
        while (($row    = fgets($file)) !== false) {
            if (++$line > $range['end']) {
                break;
            }

            if ($line >= $range['start']) {
                $row = htmlspecialchars($row, ENT_NOQUOTES, "utf-8");
                $row = '<span class="number">' . sprintf($format, $line) . '</span> ' . $row;

                if ($line === $line_number) {
                    // 对该行高亮
                    $row = '<span class="line highlight">' . $row . '</span>';
                } else {
                    $row = '<span class="line">' . $row . '</span>';
                }
                $source .= $row;
            }
        }
        fclose($file);

        return '<pre class="source"><code>' . $source . '</code></pre>';
    }
    
    /**
     * 将异常发邮件
     *
     * @param Exception $e exeption
     *
     * @return mixed
     */
    static public function sendMail(Exception $e) {
        $error = $e->getMessage();
    
        $code     = $e->getCode();
        $file     = $e->getFile();
        $line     = $e->getLine();
        $metadata = var_export($e->getMetadata(), true);
    
        $class_name = get_class($e);
        $content    = "{$class_name}[{$code}]:{$error} @{$file}[{$line}]\n{$metadata}\n";
    
        //系统级别异常记录调用过程
        if ($e instanceof Exception_System) {
            $content .= $e->getTraceAsString();
        }
    
        Helper_Smtp::warning($class_name, $content);
    }

}
