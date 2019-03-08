<?php

/**
 * time helper
 *
 * @package null
 * @author  dh <lidonghui@yixia.com>
 */
class Helper_Miaopai_Time {
    /**
     * diff
     *
     * @param bigint $time    time
     * @param bigint $timeNow timeNow
     */
    public static function getfriendlytimediff($time, $timeNow) {
        $timeNow = empty($timeNow)?self::getMicrotimeLong():$timeNow;
        $diff = $timeNow - $time;
        $str = "刚刚";
        if ($diff > 0) { 
            $s = (int)($diff / (60 * 1000));
            $h = (int)($s / 60); 
            $d = (int)($h / 24); 
            $m = (int)($d / 30); 
            $y = (int)($m / 12); 
            $time /= 1000;
            $time = (int)$time;
         
            $timeNow_1 = $timeNow/1000;
            //昨天起始时间戳
            $start = mktime(0,0,0,date("m",$timeNow_1),date("d",$timeNow_1)-1,date("Y",$timeNow_1));
            $end = mktime(23,59,59,date("m",$timeNow_1),date("d",$timeNow_1)-1,date("Y",$timeNow_1));
         
            /***新作格式时间规则**/
            if ($y > 0) { 
                $str = date("Y-m-d H:i", $time);
            } elseif ($m > 0) { 
                $str = date("m-d H:i", $time);
            } elseif ($d > 0) { 
                $str = date("m-d", $time);
            } elseif ($h > 0) { 
                if ($h >= 1) { 
                    $str = date("H:i", $time);
                } else if ($h < 1) { 
                    $str = $s . '分钟前';//"分钟前";
                }    
         
            } elseif ($s > 0) { 
                $str = $s . '分钟前';//"分钟前";
            }    
            //判断时间是否是昨天
            if ($time >= $start && $time < $end) { 
                $str = "昨天";
            }    
            /***END**/

        } 
        return $str;   
    }
    /**
     * time now
     */
    public static function getMicrotimeLong() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}
