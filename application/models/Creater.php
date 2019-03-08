<?php
/**
 * 发号器
 *
 * @package   model
 * @author    张宝军 <baojun4545@sina.com>
 * @copyright 2016 Yixia.com all rights reserved
 */

class CreaterModel extends Abstract_M{
    /**
     * 用于参数检查
     * 
     * @var type
     */
    protected static $type_config = array (Data_Creater::TYPE_FOLLOW, Data_Creater::TYPE_MARK, Data_Creater::TYPE_USER, Data_Creater::TYPE_FEED, Data_Creater::TYPE_CHANNEL, Data_Creater::TYPE_MSG);

    /**
     * get uid
     * 
     * @return number
     */
    public static function getUid() {
        return self::getNumber(Data_Creater::TYPE_USER);
    }
    
    /**
     * get msg id
     */
    public static function getMsgId() {
        return self::getNumber(Data_Creater::TYPE_MSG);
    }

    /**
     * get feed id
     *
     * @return number
     */
    public static function getFeedId() {
        $keep_len = 8;
        $num = self::getNumber(Data_Creater::TYPE_FEED, $keep_len);
        $pos = '2';
        $id  = $pos . date('Ymd') . $num;
        
        return $id;
    }
    
    /**
     * get follow id
     *
     * @return number
     */
    public static function getFollowId() {
        return self::getNumber(Data_Creater::TYPE_FOLLOW);
    }
    
    /**
     * get mark id
     *
     * @return number
     */
    public static function getMarkId() {
        return self::getNumber(Data_Creater::TYPE_MARK);
    }
    
    /**
     * get channel id
     *
     * @return number
     */
    public static function getChannelId() {
        $keep_len = 7;
        $num = self::getNumber(Data_Creater::TYPE_CHANNEL, $keep_len);
        $pos = '0';
        $id  = date('Ymd') . $pos . $num;
    
        return $id;
    }

    /**
     * 重置发号器值
     * 
     * @param integer $bid   业务号
     * @param integer $count 自加区间
     * 
     * @return boolean
     */
    public function init($bid, $count) {
        $count += 0;
        if (! self::_checkBid($bid) or $count <= 0)
            return false;
        $ret = self::_getData()->init($bid, $count);
        return $ret;
    }

    /**
     * 拼装输出发号器MAXID
     * 
     * @param integer $bid     业务号
     * @param boolean $keepLen 不足10位是否补齐长度
     * 
     * @return integer
     */
    public static function getNumber($bid, $keep_len = false) {
        if (! self::_checkBid($bid))
            return false;
        $count = self::_getData()->increment($bid);
        // 失败重试一次
        if ($count === false) {
            $count = self::_getData()->increment($bid);
            if ($count === false)
                return false;
        }
        if ($keep_len) {
            $count = sprintf("%0{$keep_len}s", $count);
            $count = substr($count, -$keep_len);
        }
        
        return $count;
    }

    /**
     * 检测业务号
     * 
     * @param integer $bid 业务号
     * 
     * @return boolean
     */
    private static function _checkBid(&$bid) {
        $bid += 0;
        if (! in_array($bid, self::$type_config))
            return false;
        
        return true;
    }
    
    /**
     * get data object
     *
     * @return Data_Creater
     */
    private static function _getData() {
        return new Data_Creater();
    }
}
