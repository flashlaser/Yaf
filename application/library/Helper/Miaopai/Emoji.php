<?php

/**
 * emoji handle
 *
 */
class Helper_Miaopai_Emoji {
    function stringutil_emojiEncode($str, $bAddTail){
        if (empty($str)) return "";
        if (strpos($str, "[yixiaemojiformat]") !== FALSE) return $str;
        $ret = "[yixiaemojiformat]" . base64_encode($str);
        if ($bAddTail) $ret = $ret . "|" . $str;
        return $ret;
    }

    function stringutil_emojiDecode($str){
        if (empty($str)) return "";
        if (strpos($str, "[yixiaemojiformat]") === FALSE) return $str;
        $str = substr($str, strlen("[yixiaemojiformat]"));
        $pos = strpos($str, "|");
        if ($pos !== FALSE) $str = substr($str,0,$pos);
        return base64_decode($str);
    }
    function is_emoji($str) {
        return preg_match('/[\x{1F600}-\x{1F64F}]/u', $str)
        || preg_match('/[\x{1F300}-\x{1F5FF}]/u', $str)
        || preg_match('/[\x{1F680}-\x{1F6FF}]/u', $str)
        || preg_match('/[\x{1F1E0}-\x{1F1FF}]/u', $str)
        ;
    }

    function replace_emoji($str) {
        $str = preg_replace('/[\x{1F600}-\x{1F64F}]/u',"", $str) ;
        $str =  preg_replace('/[\x{1F300}-\x{1F5FF}]/u', "", $str);
        $str = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', "", $str);
        $str = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', "", $str);
        return $str;
    }

}
