<?php

/**
 * 验证类
 * @package helper
 * @author  baojun <baojun@sina.com>
 */
abstract class Helper_Validator {

    // 短链接域名
    private static $_SHORT_URL_DOMAIN = array("t.cn", "sinaurl.cn");

    /**
     * 使用正则表达式进行验证
     *
     * @param mixed $value value
     * @param string $regxp regxp
     *
     * @return boolean
     */
    static function regex($value, $regxp) {
        return preg_match($regxp, $value) > 0;
    }

    /**
     * 是否等于指定值
     *
     * @param mixed $value value
     * @param mixed $test test
     *
     * @return boolean
     */
    static function equal($value, $test) {
        return $value == $test && strlen($value) == strlen($test);
    }

    /**
     * 不等于指定值
     *
     * @param mixed $value value
     * @param mixed $test test
     *
     * @return boolean
     */
    static function notEqual($value, $test) {
        return $value != $test || strlen($value) != strlen($test);
    }

    /**
     * 是否与指定值完全一致
     *
     * @param mixed $value value
     * @param mixed $test test
     *
     * @return boolean
     */
    static function same($value, $test) {
        return $value === $test;
    }

    /**
     * 是否与指定值不完全一致
     *
     * @param mixed $value value
     * @param mixed $test test
     *
     * @return boolean
     */
    static function notSame($value, $test) {
        return $value !== $test;
    }

    /**
     * 验证字符串长度
     *
     * @param string $value value
     * @param int $len len
     *
     * @return boolean
     */
    static function strlen($value, $len) {
        return mb_strlen($value) == (int)$len;
    }

    /**
     * 最小长度
     *
     * @param mixed $value value
     * @param int $len len
     *
     * @return boolean
     */
    static function minLength($value, $len) {
        return mb_strlen($value) >= $len;
    }

    /**
     * 最大长度
     *
     * @param mixed $value value
     * @param int $len len
     *
     * @return boolean
     */
    static function maxLength($value, $len) {
        return mb_strlen($value) <= $len;
    }

    /**
     * 最小值
     *
     * @param mixed  $value value
     * @param int|float $min min
     *
     * @return boolean
     */
    static function min($value, $min) {
        return $value >= $min;
    }

    /**
     * 最大值
     *
     * @param mixed    $value value
     * @param int|float $max max
     *
     * @return boolean
     */
    static function max($value, $max) {
        return $value <= $max;
    }

    /**
     * 在两个值之间
     *
     * @param mixed   $value    value
     * @param int     $min       min
     * @param int     $max       max
     * @param boolean $inclusive 是否包含min max
     * 
     * @return bool
     */
    static function between($value, $min, $max, $inclusive = true) {
        if ($inclusive) {
            return $value >= $min && $value <= $max;
        } else {
            return $value > $min && $value < $max;
        }
    }

    /**
     * 大于指定值
     *
     * @param mixed     $value value
     * @param int|float $test  test
     *
     * @return boolean
     */
    static function greaterThan($value, $test) {
        return $value > $test;
    }

    /**
     * 大于等于指定值
     *
     * @param mixed     $value value
     * @param int|float $test  test
     *
     * @return boolean
     */
    static function greaterOrEqual($value, $test) {
        return $value >= $test;
    }

    /**
     * 小于指定值
     *
     * @param mixed     $value value
     * @param int|float $test  test
     *
     * @return boolean
     */
    static function lessThan($value, $test) {
        return $value < $test;
    }

    /**
     * 小于登录指定值
     *
     * @param mixed     $value value
     * @param int|float $test  test 
     *
     * @return boolean
     */
    static function lessOrEqual($value, $test) {
        return $value <= $test;
    }

    /**
     * 不为 null
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function notNull($value) {
        return !is_null($value);
    }

    /**
     * 不为空
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function notEmpty(& $value) {
        return !empty($value);
    }

    /**
     * 是否是特定类型
     *
     * @param mixed $value value
     * @param string $type type
     *
     * @return boolean
     */
    static function isType($value, $type) {
        return gettype($value) == $type;
    }

    /**
     * 是否是字母加数字
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isAlnum($value) {
        return ctype_alnum($value);
    }

    /**
     * 是否是字母
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isAlpha($value) {
        return ctype_alpha($value);
    }

    /**
     * 是否是字母、数字加下划线
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isAlnumu($value) {
        return trim($value, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_') == '';
        //        return preg_match('/[^a-zA-Z0-9_]/', $value) == 0;
    }

    /**
     * 是否是中文(UTF-8)、字母、数字加下划线
     * 
     * @param mixed $value value
     * 
     * @return  boolean
     */
    static function isAlnumuc($value) {
        return preg_match('/[^' . chr(228) . chr(128) . chr(128) . '-' . chr(233) . chr(191) . chr(191) . 'a-zA-Z0-9_]/', $value) == 0;
    }

    /**
     * 是否是控制字符
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isCntrl($value) {
        return ctype_cntrl($value);
    }

    /**
     * 是否是数字
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isDigits($value) {
        return ctype_digit($value);
    }

    /**
     * 是否是可见的字符
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isGraph($value) {
        return ctype_graph($value);
    }

    /**
     * 是否是全小写
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isLower($value) {
        return ctype_lower($value);
    }

    /**
     * 是否是可打印的字符
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isPrint($value) {
        return ctype_print($value);
    }

    /**
     * 是否是标点符号
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isPunct($value) {
        return ctype_punct($value);
    }

    /**
     * 是否是空白字符
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isWhitespace($value) {
        return ctype_space($value);
    }

    /**
     * 是否是全大写
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isUpper($value) {
        return ctype_upper($value);
    }

    /**
     * 是否是十六进制数
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isXdigits($value) {
        return ctype_xdigit($value);
    }

    /**
     * 是否是 ASCII 字符
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isAscii($value) {
        return preg_match('/[^\x20-\x7f]/', $value) == 0;
    }

    /**
     * 是否是电子邮件地址
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isEmail($value) {
        return preg_match("/^\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i", $value);
    }

    /**
     * 是否是手机
     * 
     * @param string $value value
     * 
     * @return boolean
     */
    static function isCellphone($value) {
        return preg_match('/^1[358]\d{9}$/', $value);
    }

    /**
     * 是否是固定电话
     * 
     * @param string $value value
     * 
     * @return boolean
     */
    static function isFixedphone($value) {
        return preg_match('/^\d{3,4}-\d{7,8}(-\d{1,6})?$/', $value);
    }

    /**
     * 是否是日期（yyyy/mm/dd、yyyy-mm-dd）
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isDate($value) {
        if (strpos($value, '-') !== false) {
            $p = '-';
        } elseif (strpos($value, '/') !== false) {
            $p = '\/';
        } else {
            return false;
        }

        if (preg_match('/^\d{4}' . $p . '\d{1,2}' . $p . '\d{1,2}$/', $value)) {
            $arr = explode($p, $value);
            if (count($arr) < 3)
                return false;

            list ($year, $month, $day) = $arr;
            return checkdate($month, $day, $year);
        } else {
            return false;
        }
    }

    /**
     * 是否是时间（hh:mm:ss）
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isTime($value) {
        $parts = explode(':', $value);
        $count = count($parts);
        if ($count != 2 && $count != 3) {
            return false;
        }
        if ($count == 2) {
            $parts[2] = '00';
        }
        if (count(array_filter($parts, 'is_numeric')) !== 3) {
            return false;
        }
        $test = @strtotime($parts[0] . ':' . $parts[1] . ':' . $parts[2]);
        if ($test === -1 || $test === false) {
            return false;
        }

        return true;
    }

    /**
     * 是否是日期 + 时间
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isDatetime($value) {
        $test = @strtotime($value);
        if ($test === false || $test === -1) {
            return false;
        }
        return true;
    }

    /**
     * 是否是整数
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isInt($value) {
        if (is_null(self::$_locale)) {
            self::$_locale = localeconv();
        }

        $value = str_replace(self::$_locale['decimal_point'], '.', $value);
        $value = str_replace(self::$_locale['thousands_sep'], '', $value);

        if (strval(intval($value)) != $value) {
            return false;
        }
        return true;
    }

    /**
     * 是否是浮点数
     *
     * @param mixed $value value
     * 
     * @return boolean
     */
    static function isFloat($value) {
        if (is_null(self::$_locale)) {
            self::$_locale = localeconv();
        }

        $value = str_replace(self::$_locale['decimal_point'], '.', $value);
        $value = str_replace(self::$_locale['thousands_sep'], '', $value);

        if (strval(floatval($value)) != $value) {
            return false;
        }
        return true;
    }

    /**
     * 是否是 IPv4 地址（格式为 a.b.c.h）
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isIpv4($value) {
        $test = @ip2long($value);
        return $test !== -1 && $test !== false;
    }

    /**
     * 验证是否为纯数字
     * 
     * @param mixed $value     value
     * @param int   $minLength min length
     * @param int   $maxLength max length
     * 
     * @return boolean
     */
    static function isNumint($value, $minLength = null, $maxLength = null) {
        $value = (string)$value;
        $result = trim($value, '1234567890');
        if ($result)
            return false;

        if ($minLength && !isset($value{$minLength}))
            return false;
        if ($maxLength) {
            $maxLength++;
            return !isset($value{$maxLength});
        }

        return true;
    }

    /**
     * 判断给定的值是否是UID
     * 
     * @param int $uid uid
     * 
     * @return	boolean
     */
    static public function isUid($uid) {
        $uid = trim($uid);
        $result = trim($uid, '1234567890');
        return $result == '' && isset($uid{4}) && !isset($uid{11});
    }

    /**
     * 是否是八进制数值
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isOctal($value) {
        return preg_match('/0[0-7]+/', $value);
    }

    /**
     * 是否是二进制数值
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isBinary($value) {
        return preg_match('/[01]+/', $value);
    }

    /**
     * 是否是 Internet 域名
     *
     * @param mixed $value value
     *
     * @return boolean
     */
    static function isDomain($value) {
        return preg_match('/[a-z0-9\.]+/i', $value);
    }

    /**
     * 验证MySQL LIMIT后的写法是否正确
     * 
     * @param string $value value
     * 
     * @return boolean
     */
    static function limit($value) {
        return preg_match('/^\s*\d+(\s*,\s*\d+)?\s*$/', $value);
    }

    /**
     * 验证是否是ip地址
     * 
     * @param string $value value
     * 
     * @return boolean
     */
    static function isIp($value) {
        $ip = explode(".", $value);
        $count = count($ip);
        for ($i = 0; $i < $count; $i++) {
            if ($ip[$i] > 255)
                return false;
        }
        return ereg("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$", $value);
    }

    /**
     * 判断长链接格式是否合法，add by baojun，copy from v5
     *
     * @param string $url 链接地址
     *
     * @return boolean 是否成功
     */
    public static function checkUrl($url) {
        $url_info = parse_url($url);
        $scheme = isset($url_info['scheme']) ? $url_info['scheme'] : '';
        if (in_array(strtolower($scheme), array('http', 'https'))) {
            return true;
        }
        return false;
    }

    /**
     * 判断短链接格式是否合法，add by baojun，copy from v5
     *
     * @param string $url 链接地址
     *
     * @return boolean 是否成功
     */
    public static function checkShortUrl($url) {
        $url_parse = parse_url($url);
        if (!isset($url_parse['host']) || !in_array($url_parse['host'], self::$_SHORT_URL_DOMAIN)) {
            return false;
        }
        return true;
    }

}

