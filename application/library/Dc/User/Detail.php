<?php
/**
 * 用户-详细Data转換
 *
 * @package Dc
 * @author  huangxiran <huangxiran@yixia.com>
 */

class Dc_User_Detail extends Abstract_Dataconvert{

    /**
     * 是否启用新数组key编号
     * @var unknown
     */
    protected $is_newkey = true;

    /**
     * 拥有的字段
     * todo 字段在data层配置了
     * @var array
     */
    protected $fields = array(
        'cover'       => '^cover',
        'desc'        => '^desc|emojiDecode',
        'area'        => '^area',
        'gender'      => '^gender|intval',
        'status'      => '^status|intval',
        'create_at'   => '^create_time',
        'device_id'   => "^device_id"
    );

    /**
     * time format
     *
     * @param string $time time
     *
     * @return string
     */
    public function timeFormat($time){
        //todo
        $str_today = date('Y-m-d'); //获取今天的日期 字符串
        $ux_today =  strtotime($str_today); //将今天的日期字符串转换为 时间戳

        $ux_tomorrow = $ux_today+3600*24;// 获取明天的时间戳
        $str_tomorrow = date('Y-m-d',$ux_tomorrow);//获取明天的日期 字符串


        $ux_afftertomorrow = $ux_today+3600*24*2;// 获取后天的时间戳
        $str_afftertomorrow = date('Y-m-d',$ux_tomorrow);//获取后天的日期 字符串

        $str_in_format = date('n月j日',$time);//格式化为y-m-d的 日期字符串
        $day = '';
        if ($str_in_format==$str_today) {
            $day = "今天";
        } else if ($str_in_format==$str_tomorrow) {
            $day = "明天";
        } else if ($str_in_format==$str_afftertomorrow) {
            $day = "后天";
        } else {
            $day = $str_in_format;
        }
        return $day . ' ' . date('H:i', $time);
    }

    /**
     * emoji decode
     * @param string $data  data
     *
     * @return string
     */
    function emojiDecode( $data ) {
        return Helper_Miaopai_Emoji::stringutil_emojiDecode($data);
    }
}
