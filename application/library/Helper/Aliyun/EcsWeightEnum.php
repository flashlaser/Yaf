<?php

/**
 * @desc slb后端服务器权重
 *
 * @package Helper.Aliyun
 * @author  huangxiran <huangxiran@yixia.com>
 */

require_once APP_PATH."application/library/Helper/Enum.php";

class Helper_Aliyun_EcsWeightEnum extends Enum {
    const __DEFAULT = self::INIT_WEIGHT;

    const INIT_WEIGHT = 100;
    const STEP_WEIGHT = 20;
    const NULL_WEIGHT = 50;

}