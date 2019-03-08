<?php
/**
 * 字符串处理
 *
 * @package helper
 * @author  baojun <baojun4545@sina.com>
 */

class Helper_String{
    
    /**
     * 禁止实例化
     * 
     * @throws Exception_System
     */
    final protected function __construct() {
        throw new Exception_System(200402, "此类禁止实例化", '');
    }
    
    /**
     * 按照字长截取多字节字符串，并进行htmlspecialchars处理
     * 
     * @param string $string           字符串
     * @param <type> $length           长度
     * @param <type> $sublen           sub len
     * @param <type> $dot              标识
     * @param <type> $htmlspecialchars 是否编码
     * @param <type> $encoding         字符
     * 
     * @return <type>
     */
    static public function cnSubStr($string, $length, $sublen, $dot = '...', $htmlspecialchars = false, $encoding = 'utf-8') {
        if (mb_strlen($string, $encoding) > $length) {
            $string = mb_substr($string, 0, $sublen, $encoding) . $dot;
        }
        return $htmlspecialchars ? htmlspecialchars($string, ENT_QUOTES) : $string;
    }
    /**
     * 截取多字节字符串，并进行htmlspecialchars处理
     * 
     * @param string $string           字符串
     * @param int    $length           长度 
     * @param string $dot              标识
     * @param int    $htmlspecialchars 是否编码
     * 
     * @return string
     */
    static public function cnCut($string, $length, $dot = '...', $htmlspecialchars = true) {
        if (mb_strwidth($string) > $length) {
            $string = mb_strimwidth($string, 0, $length, $dot);
        }
        
        return $htmlspecialchars ? htmlspecialchars($string, ENT_QUOTES) : $string;
    }
    
    /**
     * 按显示字符的宽度截取已经htmlspecialchars后的字符串
     * 
     * @param type $string 字符串
     * @param type $length 长度
     * @param type $dot    标识
     * 
     * @return type
     */
    static public function cnCutEncoded($string, $length, $dot = '...') {
        if (mb_strwidth($string) <= $length) {
            return $string;
        }
        
        $string = htmlspecialchars_decode($string, ENT_QUOTES);
        $string = mb_strimwidth($string, 0, $length, $dot);
        return htmlspecialchars($string, ENT_QUOTES);
    }
    
    /**
     * 对传入的内容标红处理，可能存在多个关键字需要标红则循环处理
     *
     * @param string $content  内容
     * @param string $sear_key 标红的对象
     * 
     * @return string
     */
    static public function redTag($content, $sear_key) {
        if (in_array($sear_key, array ('~','/' ))) {
            $sear_key_arr = array ($sear_key );
        } else {
            $sear_key_arr = array_unique(preg_split("/[\s|\/|~]+/", $sear_key));
        }
        
        if (count($sear_key_arr) > 0) {
            foreach ( $sear_key_arr as $v ) {
                $vv = $v;
                if ($vv === true)
                    continue;
                    
                    // 过滤转义特殊字符
                $v_word = array ('+','.','?','$','^','*','(',')','[',']','{','}' );
                foreach ( $v_word as $vm ) {
                    $vv = str_replace($vm, "\\{$vm}", $vv);
                }
                $content = self::_dealRedTag($content, $vv);
            }
        }
        return $content;
    }
    
    /**
     * 标红处理
     *
     * @param string $content  内容
     * @param string $preg_key 标红的对象
     * 
     * @return string
     */
    static private function _dealRedTag($content, $preg_key) {
        if (empty($content) || empty($preg_key)) {
            return $content;
        }
        $html_tags = array ();
        preg_match_all("/<(\S*?)[^>]*>.*?<\/\\1>|<[^>]+>|<sina:link[^>]*>/i", $content, $tmps);
        
        foreach ( $tmps[0] as $k => $v ) {
            array_push($html_tags, array ('sTag' => "#tag{$k}#",'oTag' => $v ));
        }
        if (count($html_tags) > 0) {
            foreach ( $html_tags as $ht ) {
                $content = str_replace($ht['oTag'], $ht['sTag'], $content);
            }
        }
        $content = preg_replace("/($preg_key)/i", "<span style='color: red;'>\\1</span>", $content);
        $content = str_replace("＃", "#", $content);
        $content = preg_replace("/#([^#]+)#/ies", "strip_tags('#\\1#')", $content);
        if (count($html_tags) > 0) {
            foreach ( $html_tags as $ht ) {
                $content = str_replace($ht['sTag'], $ht['oTag'], $content);
            }
        }
        return $content;
    }
    
    /**
     * 字符串截取方法
     * 支持中英文、全角半角、
     *
     * @param string $string 要截取的字符串
     * @param int $length 截取长度
     * @param string $dot 截取后，显示符
     * @param string $charset 字符编码
     * @return string
     */
    public static function truncate($string, $length = 50, $dot = '...', $charset = 'utf-8') {
        $strlen = strlen($string);
        // $strlen = mb_strwidth($string, $charset);
        if ($strlen <= $length)
            return $string;
        $string = str_replace(array ('&nbsp;','&amp;','&quot;','&#039;','&ldquo;','&rdquo;','&mdash;','&lt;','&gt;','&middot;','&hellip;' ), array (' ','&','"',"'",'“','”','—','<','>','·','…' ), $string);
        $strcut = '';
        if (strtolower($charset) == 'utf-8') {
            $n = $tn = $noc = 0;
            while ( $n < $strlen ) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n ++;
                    $noc ++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n ++;
                }
                if ($noc >= $length)
                    break;
            }
            if ($noc > $length)
                $n -= $tn;
            $strcut = substr($string, 0, $n);
            // $strcut = mb_strimwidth($string, 0, $n, "", $charset);
        } else {
            $dotlen = strlen($dot);
            $maxi = $length - $dotlen - 1;
            for ($i = 0; $i < $maxi; $i ++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++ $i] : $string[$i];
            }
        }
        $strcut = str_replace(array ('&','"',"'",'<','>' ), array ('&amp;','&quot;','&#039;','&lt;','&gt;' ), $strcut);
        return $strcut . $dot;
    }
    
    /**
     * HTML标签闭合
     *
     * @param string  $html      输入HTML 
     * @param boolean $auto_span 纯JS时是否自动套空SPAN，避免SCRIPT被过滤的问题
     *       
     * @return string 处理后的HTML
     */
    static public function tidy($html, $auto_span = true) {
        if (! extension_loaded('tidy')) {
            if (function_exists('dl')) {
                Helper_Debug::error('Tidy is not loaded, try to dl.');
                dl('tidy.so');
            } else {
                throw new Exception_System(200107, 'PHP extension tidy not loaded.', array ());
            }
        }
        
        $html = trim($html);
        if ($auto_span && stripos($html, '<script') === 0 && stripos($html, '</script>') === (strlen($html) - 9)) {
            $html = '<span>' . $html . '</span>';
        }
        return tidy_repair_string($html, array ('output-xhtml' => true,'show-body-only' => true,'input-encoding' => 'utf8','output-encoding' => 'utf8','char-encoding' => 'utf8' ), 'utf8');
    }
    
    /**
     * 去掉最右边的URL
     * 
     * @param string $text        内容
     * @param string $link_prefix 链接前缀(支持正则表过式)
     * 
     * @return type
     */
    static public function cutRightUrl($text, $link_prefix = '详情:') {
        return preg_replace("~\s*{$link_prefix}https?://[a-z0-9A-Z/?.]+$~", '', $text);
    }
    
    /**
     * 检查字符串中是否有空白
     * 
     * @param type $text 内容
     * 
     * @return boolean
     */
    static public function hasGap($text) {
        return ( boolean ) preg_match('/\s/', $text);
    }
    
    /**
     * 判断一个字符串是否包含4个字节的utf8字符
     *
     * @param string $text 要检测的字符串
     * 
     * @return int 1.包含；0.不包含
     */
    static public function isUtf8mb4($text) {
        return preg_match('/[\xF0-\xFF]/', $text);
    }
    
    /**
     * 中文分词
     *
     * @param string $content 内容
     * @param enum   $mode    分词的规则,enum(SCWS_MULTI_SHORT, SCWS_MULTI_DUALITY, SCWS_MULTI_ZMAIN, SCWS_MULTI_ZALL) 分别表示短语，二元，主要单字，所有单字
     *       
     * @return array $splits 包含被分的词的数组
     */
    public static function cnSplit($content, $mode = SCWS_MULTI_ZALL) {
        if (empty($content) || ! function_exists('scws_new')) {
            return $content;
        }
        $ws_obj = scws_new();
        $ws_obj->set_charset('utf8');
        $ws_obj->send_text($content);
        $ws_obj->set_multi($mode);
        $splits = array ();
        while ( $tmp = $ws_obj->get_result() ) {
            foreach ( $tmp as $each ) {
                $splits[] = $each['word'];
            }
        }
        $ws_obj->close();
        return $splits;
    }
    
    /**
     * 过滤ascii码的所有特殊字符
     *
     * @param string $str     字符串
     * @param string $replace 过滤掉的ascii码的替代字符
     *       
     * @return string $rst
     */
    public static function escapeAsciiSpecAll($str, $replace = '') {
        return preg_replace("/([\\x00-\\x1f\\x21-\\x2f\\x3a-\\x40\\x5b-\\x60\\x7b-\\x7f]+)/i", $replace, $str);
    }
    
    /**
     * 转化全角空格为半角，\n,\r为空格，合并多个空格为1一个，把全角@转成半角@
     * 这个是发微博的需求
     *
     * @param string $str 字符串
     *
     * @return string
     */
    public static function trimAllBlank($str) {
        $str = str_replace(array ("　","\n","\r" ), " ", $str);
        $str = preg_replace("/[ ]{1,}/", " ", $str);
        $str = str_replace('＠', '@', $str);
        return $str;
    }
    
    /**
     * 计算字符串的长度
     * [注] 常用于DB字段长度判断,如:varchar(255),代表存255个字符，可为255汉字，可为255英文，也存在混杂情况。
     *
     * @param string $string string
     * 
     * @return int ($zhStr = '您好,中国!', $len = 6; $str = 'Hello,中国！'; $len=9)
     */
    public static function utf8Strlen($string = null) {
        // 将字符串分解为单元
        preg_match_all('/./us', $string, $match);
        // 返回单元个数
        return count($match[0]);
    }
    
    /**
     * check status len
     * 
     * @param type $content 内容
     * @param int  $max     最大数
     * 
     * @return type
     */
    public static function checkStatusLength($content, $max = 140) {
        $min = 41;
        $surl = 20;
        $urlCount = 0;
        preg_match_all("/http:\/\/[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([-A-Z0-9a-z_\$\.\+\!\*\(\)\/,:;@&=\?\~\#\%]*)*/i", $content, $urls);
        foreach ( $urls[0] as $url ) {
            $count = mb_strwidth($url, 'UTF-8');
            if (preg_match('/^(http:\/\/t.cn)/', $url)) {
                continue;
            } else {
                if (preg_match('/^(http:\/\/)+(t.sina.com.cn|t.sina.cn)/', $url) || preg_match('/^(http:\/\/)+(weibo.com|weibo.cn)/', $url)) {
                    // 本域的小于41字符按照实际值算，大于41小于等于140以20字符算，超过140为20加溢出长度
                    $urlCount += $count <= $min ? $count : ($count <= $max ? $surl : ($count - $max + $surl));
                } else {
                    // 非本域的小于140的按照20算，超过140为20加溢出长度
                    $urlCount += $count <= $max ? $surl : ($count - $max + $surl);
                }
            }
            $content = str_replace($url, "", $content);
        }
        $result = ceil(($urlCount + mb_strwidth($content, 'UTF-8')) / 2);
        return $result;
    }
    /**
     * 美化展示
     * 原本在四舍五入前有一步floor向下取整的操作
     * 修改去掉亿以下的数在四舍五入前的floor取整操作
     * 100
     * 1,100
     * 11,100
     * 11.5万
     * 110万
     * 1000万
     * 1亿
     *
     * @param int $cnt cnt
     *
     * @return string
     */
    public static function stringutilgetCountNice($cnt) {
        if ($cnt<1000)
            return $cnt;
        else if ($cnt<10000)
            return round($cnt/1000) . "," . self::stringutil_zeroprefix($cnt%1000,3);
        else if ($cnt<1000000)
            return round($cnt/1000)/10.0 . "万";
        else if ($cnt<100000000)
            return round($cnt/10000) . "万";
        return round(floor($cnt/100000000)) . "亿";
    }
    /**
     * 长度美化
     *
     * @param int $cnt 长度
     *
     * @return string
     */
    public static function v2stringutilgetCountNice($cnt) {
        if ($cnt < 10000) {
            return $cnt;
        } else if ($cnt < 1000000) {
            return round(floor($cnt / 1000) / 10.0) . "万";
        } else if ($cnt<100000000) {
            return round(floor($cnt/10000)) . "万";
        }
        return round(floor($cnt/100000000)) . "亿";
    }
    /**
     * coin长度美化
     *
     * @param int $cnt 长度
     *
     * @return string
     */
    public static function v3stringutilgetCountNice($cnt) {
        if ($cnt < 10000) {
            return $cnt;
        } else if ($cnt<100000000) {
            return round($cnt/10000) . "万";
        }
        return 1 . "亿";
    }
}
