<?php
/**
 * 时间转换类,用于对数据库查询出来的时间进行转化 ，把时间转化函数进行了一次封装
 *
 * @package helper
 * @author  baojun <zhangbaojun@yixia.com>
 */

define("TIME_FORMAT_SECONDTE", "%s秒前");
define('TIME_FORMAT_JUST', '刚刚');
define("TIME_FORMAT_MINITE", "%s分钟前");
define('TIME_FORMAT_HOUR', '%s小时前');
define('TIME_FORMAT_DAY', '%s天前');
define('TIME_FORMAT_WEEK', '%s周前');
define("TIME_FORMAT_TODAY", "今天 %s");
define("TIME_FORMAT_YESTODAY", "昨天 %s");
define("TIME_FORMAT_HISTORY", "%s-%s-%s");
define("TIME_FORMAT_HISTORY_VISITOR", "%s月%s日");
define('TIME_FORMAT_CAPTION_TODAY', '今天');
define('TIME_FORMAT_CAPTION_YESTODAY', '昨天');
define('TIME_FORMAT_CAPTION_YEAR', '年');
define('TIME_FORMAT_CAPTION_MONTH', '月');
define('TIME_FORMAT_CAPTION_DAY', '日');
define('TIME_FORMAT_CAPTION_HOUR', '点');
define('TIME_FORMAT_CAPTION_MINITE', '分');
define('TIME_FORMAT_CAPTION_SECOND', '秒');
define('TIME_FORMAT_EVENT_NOYEAR', "%s月%s日 周%s %s");
define('TIME_FORMAT_EVENT_WITHYEAR', "%s年%s月%s日 周%s %s");

// define("TIME_FORMAT_SECONDTE", "%s秒前");
// define('TIME_FORMAT_JUST', '剛剛');
// define("TIME_FORMAT_MINITE", "%s分鐘前");
// define('TIME_FORMAT_HOUR', '%s小時前');
// define('TIME_FORMAT_DAY', '%s天前');
// define('TIME_FORMAT_WEEK', '%s周前');
// define("TIME_FORMAT_TODAY", "今天 %s");
// define("TIME_FORMAT_YESTODAY", "昨天 %s");
// define("TIME_FORMAT_HISTORY", "%s-%s-%s");
// define("TIME_FORMAT_HISTORY_VISITOR", "%s月%s日");
// define('TIME_FORMAT_CAPTION_TODAY','今天');
// define('TIME_FORMAT_CAPTION_YESTODAY','昨天');
// define('TIME_FORMAT_CAPTION_YEAR','年');
// define('TIME_FORMAT_CAPTION_MONTH','月');
// define('TIME_FORMAT_CAPTION_DAY','日');
// define('TIME_FORMAT_CAPTION_HOUR','點');
// define('TIME_FORMAT_CAPTION_MINITE','分');
// define('TIME_FORMAT_CAPTION_SECOND','秒');
// define('TIME_FORMAT_EVENT_NOYEAR', "%s月%s日 周%s %s");
// define('TIME_FORMAT_EVENT_WITHYEAR', "%s年%s月%s日 周%s %s");

abstract class Helper_Time {
    public static $week_arr = array (0 => '周日',1 => '周一',2 => '周二',3 => '周三',4 => '周四',5 => '周五',6 => '周六',7 => '周日' );
    public static $week_num_arr = array (0 => '日',1 => '一',2 => '二',3 => '三',4 => '四',5 => '五',6 => '六',7 => '日' );
    public static $month_cn = array ("1" => "一","2" => "二","3" => "三","4" => "四","5" => "五","6" => "六","7" => "七","8" => "八","9" => "九","10" => "十","11" => "十一","12" => "十二" );
    
    /**
     * 静态调用模式，禁止实例化该类
     */
    final public function __construct() {
        throw new Exception_System(200402, "此类禁止实例化", '');
    }
    
    /**
     * 把unix时间戳转换成显示的格式 2月1日
     *
     * @param int $timestamp unix时间戳
     *       
     * @param bool $year 默认false 需要用到的时候传true 进来就行
     */
    static public function getformattime($timestamp, $year = false) {
        $time = $year ? date('Y年n月d日 H:i', $timestamp) : date('n月d日 H:i', $timestamp);
        return $time;
    }
    
    /**
     * 把unix时间戳转换成显示的格式 6月12日周六15：00
     *
     * @param int $timestamp unix时间戳
     *       
     * @param bool $year 默认false 需要用到的时候传true 进来就行
     */
    static public function getalltime($timestamp, $year = false) {
        if (! $year) {
            $time = date('Y年n月d日', $timestamp);
            $time1 = self::$week_arr[date('N', $timestamp)];
            $time2 = date('H:i:s', $timestamp);
        } else {
            $time = date('n月d日', $timestamp);
            $time1 = self::$week_arr[date('N', $timestamp)];
            $time2 = date('H:i', $timestamp);
        }
        return $time . ' ' . $time1 . ' ' . $time2;
    }
    
    /**
     * 把unix时间戳转换成显示的格式 2月1日不带小时的
     *
     * @param int $timestamp unix时间戳
     *       
     * @return string
     */
    static public function getformattimesim($timestamp) {
        $time = date('n月d日', $timestamp);
        return $time;
    }
    
    /**
     * 把unix时间戳转换成显示的格式 如果跨年的就显示年，如果不跨年就不显示年
     *
     * @param int $timestamp unix时间戳
     * @param string $his his
     *       
     * @return int
     */
    static public function getyeartime($timestamp, $his = '') {
        $nowyear = getdate();
        $yearunix = strtotime($nowyear['year'] . '-12-31 23:59:59');
        if (empty($his))
            return $timestamp > $yearunix ? date('Y年n月d日', $timestamp) : date('n月d日', $timestamp);
        else
            return $timestamp > $yearunix ? date('Y年n月d日 H:i:s', $timestamp) : date('n月d日  H:i:s', $timestamp);
    }
    
    /**
     * 把unix时间戳转换成周几暂时 只有event（活动详情页面调用 ）
     *
     * @param int $timestamp unix时间戳
     *       
     * @return string
     */
    static public function getweektime($timestamp) {
        $week_time = date('N', $timestamp);
        return self::$week_arr[$week_time];
    }
    
    /**
     * 格式化显示一条时间
     *
     * @param int $time 时间戳
     *       
     * @return string
     */
    public static function format($time) {
        $now = NOW;
        if (strpos($time, '-') !== false) {
            $time = strtotime($time);
        }
        if (($dur = $now - $time) < 3600) {
            if ($dur < 50) {
                $second = ceil($dur / 10) * 10;
                if ($second <= 0) {
                    $second = 10;
                }
                $time = sprintf(TIME_FORMAT_SECONDTE, $second);
            } else {
                $minutes = ceil($dur / 60);
                if ($minutes <= 0) {
                    $minutes = 1;
                }
                $time = sprintf(TIME_FORMAT_MINITE, $minutes);
            }
        } else if (date("Ymd", $now) == date("Ymd", $time)) {
            $time = sprintf(TIME_FORMAT_TODAY, date("H:i", $time));
        } else {
            if (date("Y") == date("Y", $time)) {
                $time = sprintf(TIME_FORMAT_HISTORY_VISITOR, date("n", $time), date("j", $time)) . " " . date("H:i", $time);
            } else {
                $time = sprintf(TIME_FORMAT_HISTORY, date("Y", $time), date("n", $time), date("j", $time)) . " " . date("H:i", $time);
            }
        }
        return $time;
    }
    
    /**
     * 格式化显示一条时间
     *
     * @param int $time 时间戳
     *       
     * @return string
     */
    public static function formatGroup($time) {
        $now = NOW;
        if (strpos($time, '-') !== false) {
            $time = strtotime($time);
        }
        if (($dur = $now - $time) < 3600) {
            if ($dur < 50) {
                $second = ceil($dur / 10) * 10;
                if ($second <= 0) {
                    $second = 10;
                }
                $time = sprintf(TIME_FORMAT_SECONDTE, $second);
            } else {
                $minutes = ceil($dur / 60);
                if ($minutes <= 0) {
                    $minutes = 1;
                }
                $time = sprintf(TIME_FORMAT_MINITE, $minutes);
            }
        } else if (date("Ymd", $now) == date("Ymd", $time)) {
            $time = sprintf(TIME_FORMAT_TODAY, date("H:i", $time));
        } else {
            $time = sprintf('%s-%s %s:%s', date("m", $time), date("d", $time), date("H", $time), date("i", $time));
        }
        return $time;
    }
    
    /**
     * 格式化显示一条时间
     *
     * @param int $stime 开始时间戳
     * @param int $etime 结束时间
     *       
     * @return string
     */
    public static function formatEventsList($stime, $etime) {
        $stime = sprintf(TIME_FORMAT_EVENT_NOYEAR, date("m", $stime), date("d", $stime), self::$week_num_arr[date("N", $stime)], date("H:i", $stime));
        $etime = sprintf(TIME_FORMAT_EVENT_NOYEAR, date("m", $etime), date("d", $etime), self::$week_num_arr[date("N", $etime)], date("H:i", $etime));
        return $stime . " - " . $etime;
    }
    
    /**
     * 格式化时间
     *
     * 1.完整格式如: 2011年5月1日 09:05
     * 2.本年时间则不显示年份,如: 5月1日 09:05
     *
     * @param int $time 时间戳
     *       
     * @return string
     */
    static public function formatTime($time) {
        if (date('Y', $time) != date('Y')) { // 非本年
            $format = 'Y年n月j日 H:i';
        } else { // 本年不显示年份了
            $format = 'n月j日 H:i';
        }
        
        return date($format, $time);
    }
    
    /**
     * 格式化两个时间
     * 2011年3月2日 周六 23:02 - 2012年3月2日 周六 09:02
     * 2011年3月2日 周六 10:02 - 18:02
     * 3月2日 周六 10:02 - 18:02
     *
     * @param int $stime 开始时间
     * @param int $etime 结束时间
     * @param bool $is_week 是否显示星期
     *       
     * @return string
     */
    static public function twoTime($stime, $etime, $is_week = false) {
        $n_y = date('Y');
        $s_info = getdate($stime);
        $e_info = getdate($etime);
        
        // 开始时间
        $w = $is_week ? ' ' . self::getweektime($stime) : '';
        if ($s_info['year'] != $n_y) { // 非本年
            $format = "Y年n月j日{$w} H:i";
        } else { // 本年不显示年份了
            $format = "n月j日{$w} H:i";
        }
        $str = date($format, $stime);
        
        // 结束时间
        if ($e_info['year'] == $s_info['year'] && $e_info['yday'] == $s_info['yday']) { // 同年月日
            $format = 'H:i';
        } else {
            $w = $is_week ? ' ' . self::getweektime($etime) : '';
            if ($e_info['year'] == $s_info['year']) { // 同年
                $format = "n月j日{$w} H:i";
            } else {
                if ($e_info['year'] != $n_y) { // 非本年
                    $format = "Y年n月j日{$w} H:i";
                } else { // 本年不显示年份了
                    $format = "n月j日{$w} H:i";
                }
            }
        }
        $str .= ' - ' . date($format, $etime);
        
        return $str;
    }
    
    /**
     * 给定一个日期，获取它所在周的起止日期范围（周一为每周的第一天）
     * 
     * @param string $date 日期：2011-04-05
     * 
     * @return array 日期范围：
     */
    static public function getWeekRange($date) {
        
        // /所给日期是周几
        $week = date("w", strtotime($date));
        $tmp = explode("-", $date);
        
        // /上个周日的日期
        $sat = mktime(0, 0, 0, $tmp[1], $tmp[2] - $week, intval($tmp[0]));
        return array ("start" => date("Y-m-d", $sat + 86400),"end" => date("Y-m-d", $sat + 7 * 86400) );
    }
    
    /**
     * 把秒数转化成方便阅读的时间段，如：3天4小时8分9秒，3小时0分0秒
     *
     * @param type $n 秒数
     *       
     * @return string
     */
    static public function lengthTime($n) {
        $str = '';
        if ($n <= 0) {
            return $str;
        }
        
        // 天数
        $d = intval($n / 24 / 3600);
        $d > 0 && $str .= $d . '天';
        $n = $n % (24 * 3600);
        
        // 小时
        $h = intval($n / 3600);
        ($h || $str) && $str .= $h . '小时';
        $n = $n % 3600;
        
        // 分
        $m = intval($n / 60);
        ($m || $str) && $str .= $m . '分';
        $n = $n % 60;
        
        // 秒
        $str .= $n . '秒';
        
        return $str;
    }
    
    /**
     * 帖子脉络时间格式化
     *
     * @param int $timestamp unix时间戳
     *       
     * @return string
     */
    static public function getoutlinetime($timestamp) {
        $y = date('Y', $timestamp);
        $year_today = date("Y", time());
        $time = $year_today > $y ? date('Y m-d H:i', $timestamp) : date('m-d H:i', $timestamp);
        return $time;
    }
    
    /**
     * 获取毫秒时间戳
     * 
     * @return number
     */
    static public function getMicrotimeLong() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}