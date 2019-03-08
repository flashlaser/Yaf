<?php

/**
 * Channel
 *
 * @package helper
 * @author  baojun <baojun4545@sina.com>
 */
define('USERUTIL_ANYMORE', 0);
define('USER_STATUS_BAN',-1);                           //<0 被屏蔽
define('USERUTIL_AUTH_TOKEN_KEY', 'PZW2agjMF5P6');
define('USERUTIL_SUID_KEY', 'PZW2agjMSUID');
class Helper_Miaopai_User {
	
	/**
	 * 获取AuthToken (save)
	 *
	 * @return bool
	 */
	public static function saveGetAuthToken($user_arr) {
		if (! $user_arr)
			return "";
		if ($user_arr ['id'] == USERUTIL_ANYMORE)
			return "";
		$pwd = $user_arr ['pwd'];
		if (strlen ( $pwd ) > 10)
			$pwd = substr ( $pwd, 0, 9 );
		return Helper_Xxtea::encrypt ( $user_arr ['id'] . "-" . $pwd, USERUTIL_AUTH_TOKEN_KEY );
	}
	
	/**
	 * 获取AuthToken
	 *
	 * @return bool
	 */
	public static function getAuthToken($user_arr) {
		$user_arr = ( array ) $user_arr;
		if ($user_arr ['id'] == USERUTIL_ANYMORE)
			return '';
		$pwd = $user_arr ['pwd'];
		if (strlen ( $pwd ) > 10)
			$pwd = substr ( $pwd, 0, 9 );
		return Helper_Xxtea::encrypt ( $user_arr ['id'] . "-" . $pwd, USERUTIL_AUTH_TOKEN_KEY );
	}
	
	public static function decodeTokenIdUnsafe($token) {
		if ($token == '' || $token == "null" || $token == "(null)")
			return USERUTIL_ANYMORE;
		$CI = &get_instance ();
		Helper_Xxtea::decrypt ( $token, USERUTIL_AUTH_TOKEN_KEY );
		$arr = split ( '-', $auth );
		if (isset ( $arr [0] )) {
			return $arr [0];
		}
		return USERUTIL_ANYMORE;
	}
	
	/**
	 * 解码token
	 *
	 * @return obj
	 */
	public static function decodeToken($authToken) {
		$auth = Helper_Xxtea::decrypt ( $authToken, USERUTIL_AUTH_TOKEN_KEY );
		if (strpos ( $auth, '-' ) === 0) {
			$pos = strpos ( $auth, '-', 1 );
		} else {
			$pos = strpos ( $auth, '-', 0 );
		}
		$arr = preg_split ( '/-/', $auth );
		$uid = substr ( $auth, 0, $pos );
		$pwd = substr ( $auth, $pos + 1 );
		$token_user_arr = array ();
		$token_user_arr ['id'] = $uid;
		$token_user_arr ['pwd'] = $pwd;
		return $token_user_arr;
	}
	
	/**
	 * 校验token
	 *
	 * @return
	 *
	 */
    public static function isRightToken($token_user_arr, $user_arr) {
		if (empty ($token_user_arr) || empty ($user_arr)) {
			return FALSE;
        }
		if ($user_arr['status'] == USER_STATUS_BAN)
			return FALSE;
		$pwd = $user_arr['pwd'];
		$pwd1 = md5 ( $pwd );
		$pwd2 = $pwd;
		$pwd3 = $pwd1;
		if (strlen ( $pwd ) > 10)
			$pwd2 = substr ( $pwd, 0, 9 );
		if (strlen ( $pwd1 ) > 10)
			$pwd3 = substr ( $pwd1, 0, 9 );
		$tpwd = $token_user_arr['pwd'];
		$tpwd1 = md5 ( $tpwd );
		$tpwd2 = $tpwd;
		$tpwd3 = $tpwd1;
		if (strlen ( $tpwd ) > 10)
			$tpwd2 = substr ( $tpwd, 0, 9 );
		if (strlen ( $tpwd1 ) > 10)
			$tpwd3 = substr ( $tpwd1, 0, 9 );
		$p = '/' . $tpwd . '|' . $tpwd1 . '|' . $tpwd2 . '|' . $tpwd3 . '/i';
		// 兼容旧模式
		return (preg_match ( $p, $pwd ) || preg_match ( $p, $tpwd ) || preg_match ( $p, $pwd1 ) || preg_match ( $p, $pwd2 ) || preg_match ( $p, $pwd3 )) && $user_arr['id'] == $token_user_arr['id'];
    }
    
    public static function encodeUid($uid) {
        return Helper_Xxtea::encrypt((string)$uid, USERUTIL_SUID_KEY);
    }

    public static function decodeUid($suid) {
        if (empty($suid)) return USERUTIL_ANYMORE;
        return Helper_Xxtea::decrypt($suid, USERUTIL_SUID_KEY);
    }

    /**
     * decode uid by token
     *
     * @param string $token user token
     */
    public static function getUidByToken($token) {
        $info = self::decodeToken($token);
        return isset($info['id']) ? $info['id'] : 0;
    }
}
