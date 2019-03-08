<?php
/**
 * 提供断言类
 *
 * 断言用于验证程序中不可能出现的情况。可以通过 Comm_Assert::as_* 系列方法调整断言的行为。
 * 默认行为为不输出任何内容。
 *
 * @package Comm
 * @author  Rodin <luodan@staff.sina.com.cn>
 */
class Comm_Assert {

    //默认为异常模式
    protected static $assert_type = 3;

    /**
     * 设置assert行为为不输出任何内容
     *
     * @return void
     */
    static public function asDumb() {
        self::$assert_type = 0;
    }

    /**
     * 设置assert行为为触发warning
     *
     * @return void
     */
    static public function asWarning() {
        self::$assert_type = 1;
    }

    /**
     * 设置assert行为为抛出exception
     *
     * @return void
     */
    static public function asException() {
        self::$assert_type = 3;
    }

    /**
     * 设置assert行为为触发error
     *
     * @return void
     */
    static public function asError() {
        self::$assert_type = 2;
    }

    /**
     * 验证条件是否为成立，如果不成立，则提示指定的message
     *
     * @param bool   $condition /
     * @param string $message   /
     * @param int    $code      /
     *
     * @return void
     */
    static public function true($condition, $message = null, $code = 0) {
        if (!$condition) {
            self::act($message, $code);
        }
    }

    /**
     * 验证条件是否不成立，如果为成立，则提示指定的message
     *
     * @param bool   $condition /
     * @param string $message   /
     * @param int    $code      /
     *
     * @return void
     */
    static public function false($condition, $message = null, $code = 0) {
        if ($condition) {
            self::act($message, $code);
        }
    }

    /**
     * 处理判言
     * 
     * @param string $message /
     * @param int    $code    /
     *
     * @return void
     * @throws	Exception_Msg
     */
    static protected function act($message, $code) {
        switch (self::$assert_type) {
            case 2 :
                trigger_error($message, E_USER_ERROR);
                break;
            case 3 :
                !$code && $code = 302002;
                throw new Exception_Msg($code, $message);
                break;
            case 1 :
                trigger_error($message, E_USER_WARNING);
                break;
            default :
        }
    }

}