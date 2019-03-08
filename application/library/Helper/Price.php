<?php
/**
 * 价格相关处理
 *
 * @package helper
 * @author  baojun <baojun4545@sina.com>
 */

abstract class Helper_Price{

    /**
     * 金额格式 转换
     * 
     * @param float $price  price
     * @param bool  $to_fen to fen
     * 
     * @return number
     */
    static public function format($price, $to_fen = true) {
        if ($to_fen) {
            return (int)(round($price * 100, 0));
        } else {
            return (float) round($price / 100, 2);
        }
    }

}
