<?php
/**
 * 系统级别异常（sql查询出错）
 *
 * 此级别的异常属于系统本身的错误，如系统服务不可用、程序代码配置错误等
 * 此类异常会记录文件日志
 * 判断标准为：若系统一切正常，任意操作都不可能出现的异常
 *
 * @package exception
 * @author  baojun <baojun4545@sina.com>
 */
class Exception_Db extends Exception_System {

}
