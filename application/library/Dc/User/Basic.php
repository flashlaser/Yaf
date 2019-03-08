<?php
/**
 * 用户-基本信息Data转換
 *
 * @package Dc
 * @author  baojun <zhangbaojun@yixia.com>
 */

class Dc_User_Basic extends Abstract_Dataconvert{
    const ORG_VERIFIED = 2;

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
        'uid'           => '^id|intval',
        'suid'          => '^id|encodeUid',
        'icon'          => '^icon|stdIcon',
        'nick'          => '^nick',
        'v'             => '^v|stdV',
        'org_v'         => '^v|stdOrgV',
        'login_name'    => '^loginName',
        'create_time'   => '^createTime',
        'icon_safe'     => '^iconSafe',
        'birthday'      => '^birthday',
        'status'        => '^status|intval',
    );

    /**
     * id 加密
     * @param int $uid 用户ID
     *
     * @return string
     */
    public function encodeUid( $uid ) {
        return Helper_Miaopai_User::encodeUid($uid);
    }


    /**
     * 完整icon
     * @param string $icon icon
     *
     * @return string
     */
    public function stdIcon($icon) {
        if ( isset( $icon) and !empty( $icon) ) {
            if (strpos($icon, "http://") === false) {
                $icon =  "http://wscdn.miaopai.com/user-icon/". $icon;
            }
        } else {
            $icon = "";
        }

        return $icon;
    }


    /**
     * v
     * @param int $v v
     *
     * @return int
     */
    public function stdV( $v ) {
        return $v == 0 ? false : true;

    }


    /**
     * org_v
     * @param int $v v
     *
     * @return int
     */
    public function stdOrgV( $v ) {
        return $v > ORG_VERIFIED ? ($v - ORG_VERIFIED) : 0;
    }
}
