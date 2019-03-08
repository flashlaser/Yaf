<?php
/**
 * 用户-详细Data转換
 *
 * @package Dc
 * @author  huangxiran <huangxiran@yixia.com>
 */

class Dc_User_Private extends Abstract_Dataconvert{

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
        "phone"     => "^phone|intval",
        "email"     => "^email",
        "email_v"   => "^emailv|intval",
        "create_ip" => "^createIp"
    );
}
