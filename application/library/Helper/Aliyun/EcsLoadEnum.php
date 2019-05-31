<?php

/**
 * @desc
 *
 * @package Helper.Aliyun
 * @author  huangxiran <huangxiran@yixia.com>
 */

require_once APP_PATH."application/library/Helper/Enum.php";

class Helper_Aliyun_EcsLoadEnum extends Enum {
    const __DEFAULT = self::NORMAL;

    const LOW       = -1;
    const NORMAL    =  0;
    const HIGH      =  1;
    const VERY_HIGH =  2;

}