<?php
/**
 * Sessionpackage类提供管理session的静态方法,通过接口调用对用户session进行管理
 * 此文件相对独立，只对签名类进行包含
 * 使用时直接包含此文件即可
 * 此文件同时存在于svn如下两个位置，变更时请同时变更
 * https://svn.intra.sina.com.cn/sso/SessionServer/trunk/library/
 * https://svn.intra.sina.com.cn/sso/BSSO/trunk/module/sso
 * @author 王立霜
 */
class Comm_Sso_Sessmanager{

	const DS = '/';
	const SESSION_SERVER = "i.sn.sina.com.cn";
	const CREATE = "create";
	const QUERY = "query";
	const VALIDATE = "validate";
	const DESTROY = "destroy";

	const SERVER_NO_RESPONSE = 90001;
	//发生服务器无响应错误（90001）时，从属于该错误的延迟时间（在这么长一段时间内不要再链接服务）
	const SERVER_DELAY_TIME = 10;
	const SESSION_SERVER_FAILED = "Session server operation failed";

	static private $NOTICE_TIME_OUT = 3;
	
	static private $proxy = null;

	/**
	 * 设置代理服务器 ip
	 *
	 * @static
	 * @param $proxy  代理服务器 ip
	 *
	 * @return void
	 */
	public static function set_proxy($proxy) {
		self::$proxy = $proxy;
	}

	/**
	 * 向session server发送请求
	 *
	 * @param	string	$url session servr 接口地址
	 * @param	array	$data POST 参数数组
	 * @param	int		$timeout 请求参数数组
	 * @throws	Exception	当发生错误时候，抛出异常
	 */
	private static function _request($url, $post_array, $timeout){

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_array));
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		
		if(isset(self::$proxy)) {
			curl_setopt($ch, CURLOPT_PROXY, self::$proxy . ':80');
		}
		
		$result = curl_exec($ch);

		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if ($curl_errno > 0) {
			throw new SessionException(self::SESSION_SERVER_FAILED . ': ' . $curl_error, $curl_errno, self::SERVER_DELAY_TIME);
		}

		// curl 返回 false（约等于 false），或返回的不是一个字符串
		if(!$result || !is_string($result)) {
			throw new SessionException(self::SESSION_SERVER_FAILED . ': result is not valid', self::SERVER_NO_RESPONSE, self::SERVER_DELAY_TIME);
		}

		$result = json_decode($result, true);

		// curl 返回结果没有 json_decode 成一个数组
		if(!is_array($result)) {
			throw new SessionException(self::SESSION_SERVER_FAILED . ': json decode failed', self::SERVER_NO_RESPONSE, self::SERVER_DELAY_TIME);
		}

		return $result;
	}
	/**
	 * 创建session
	 * @param	string	$entry 产品标识.目前只有sso
	 * @param	string	$uid 用户id
	 * @param	string	$domain session的域
	 * @param	string	$ip 用户ip
	 * @param	string	$agent 用户的浏览器
	 * @param	string	$signkey 签名key
	 * @return	string	成功，"sid":"SID-2211566881-1311847570-xd-A69Q7-4f17db2f4ce6e85b3c82ed3688abfba9"
	 * 		    SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function create($entry, $uid, $domain, $ip, $agent, $signkey) {
		$ctime = time();
		$data = compact("entry", "uid", "domain", "ip", "agent", "ctime");

		$signed = SinaSSO_Signed::makeSigned($data, $signkey);

		$data["signed"] = $signed;
		$result = self::_request(self::SESSION_SERVER . self::DS . self::CREATE, $data, self::$NOTICE_TIME_OUT);

		if ($result['retcode'] > 0) {
			throw new SessionException(self::SESSION_SERVER_FAILED, $result['retcode'], $result['delaytime']);
		}

		if(empty($result['sid'])) {
			throw new SessionException(self::SESSION_SERVER_FAILED . ': create but sid is empty', self::SERVER_NO_RESPONSE, self::SERVER_DELAY_TIME);
		}
		
		return $result['sid'];
	}
	/**
	 * 私有函数：查询session
	 * @param	string	$entry 产品标识。
	 * @param	string	$uid 用户id
	 * @param	string	$sid session id
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * @return	array	成功， 根据sid:Array([et] => 1312534710, [ip] => 1.2.3.4, [domain] => .sina.com.cn, [agent] => ff)
	 *                        根据uid:Array([SID-12552883-1312448213-ja-1hsgp-8c630fe3ac6817ce309673c5e575e006] => Array
	 *								        (
	 *								            [et] => 1312534613
	 *								            [ip] => 1.2.3.4
	 *								            [domain] => .sina.com.cn
	 *								            [agent] => ff
	 *								        )
	 *
	 *								    [SID-12552883-1312448228-ja-fucd7-856453cb3820497615cfeec51c4b18e4] => Array
	 *								        (
	 *								            [et] => 1312534628
	 *								            [ip] => 1.2.3.4
	 *								            [domain] => .sina.com.cn
	 *								            [agent] => ff
	 *								        ))
	 * 		    SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	private static function _query($entry, $uid, $sid, $ip, $signkey) {
		$ctime = time();
		$data = compact("entry", "ctime");
		
		if(isset($sid) && !empty($sid)) $data['sid'] = $sid;
		if(isset($uid) && !empty($uid)) $data['uid'] = $uid;
		if(isset($ip) && !empty($ip)) $data['ip'] = $ip;

		$signed = SinaSSO_Signed::makeSigned($data, $signkey);

		$data['signed'] = $signed;
		$result = self::_request(self::SESSION_SERVER . self::DS . self::QUERY, $data, self::$NOTICE_TIME_OUT);

		if ($result['retcode'] > 0) {
			throw new SessionException(self::SESSION_SERVER_FAILED, $result['retcode'], $result['delaytime']);
		}

		if(!isset($result['session'])) {
			throw new SessionException(self::SESSION_SERVER_FAILED . ': query no session', self::SERVER_NO_RESPONSE, self::SERVER_DELAY_TIME);
		}
		
		return $result['session'];
	}
	
	/**
	 * 根据SID查找session
	 * @param	string	$entry 产品标识.
	 * @param	string	$sid session id
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * 
	 * @return	array	成功，Array([et] => 1312534710, [ip] => 1.2.3.4, [domain] => .sina.com.cn, [agent] => ff)
	 * 			SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function query_by_sid($entry, $sid, $ip, $signkey) {
		return self::_query($entry, null, $sid, $ip, $signkey);
	}
	
	/**
	 * 根据UID查找session
	 * @param	string	$entry 产品标识.
	 * @param	string	$uid 用户唯一号
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * 
	 * @return	array	成功，Array([et] => 1312534710, [ip] => 1.2.3.4, [domain] => .sina.com.cn, [agent] => ff)
	 * 					SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function query_by_uid($entry, $uid, $ip, $signkey) {
		return self::_query($entry, $uid, null, $ip, $signkey);
	}
	
	/**
	 * 验证session
	 * @param string $entry 产品标识.目前只有sso
	 * @param string $sid session id
	 * @param string $domain session的域
	 * @param string $ip 如果需要检查IP，则该IP为用户的IP； 不提供则不检查
	 * @param string $signkey 签名key
	 * @param string $cookie 如果需要为cookie续时，则该参数为需要续时的cookie
	 * @param string $signkey 签名key
	 * @return array成功
	 (1)如果需要续时： {"uid":"用户唯一号","cookie":"新的cookie"}
	 (2) 如果不需要续时： {"uid":"用户唯一号"}
	 *         SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function validate($entry, $sid, $domain, $ip, $signkey, $cookie) {
		$ctime = time();
		$data = compact("entry", "sid", "domain", "ip", "ctime");
		
		if(isset($cookie) && !empty($cookie)) $data['cookie'] = $cookie;

		$signed = SinaSSO_Signed::makeSigned($data, $signkey);

		$data["signed"] = $signed;
		$result = self::_request(self::SESSION_SERVER . self::DS . self::VALIDATE, $data, self::$NOTICE_TIME_OUT);

		if ($result['retcode'] > 0) {
			throw new SessionException(self::SESSION_SERVER_FAILED, $result['retcode'], $result['delaytime']);
		}
		unset($result['retcode']);
		return $result;
	}
	/**
	 * 私有函数：删除session
	 * @param	string	$entry 产品标识.目前只有sso
	 * @param	string	$sid session id
	 * @param	string	$uid 用户id
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * @return	bool	成功，true
	 * 		    SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	private static function _destroy($entry, $uid, $sid, $ip, $signkey) {
		$ctime = time();
		$data = compact("entry", "ctime");
		
		if(isset($sid) && !empty($sid)) $data['sid'] = $sid;
		if(isset($uid) && !empty($uid)) $data['uid'] = $uid;
		if(isset($ip) && !empty($ip)) $data['ip'] = $ip;

		$signed = SinaSSO_Signed::makeSigned($data, $signkey);

		$data["signed"] = $signed;
		$result = self::_request(self::SESSION_SERVER . self::DS . self::DESTROY, $data, self::$NOTICE_TIME_OUT);

		if ($result['retcode'] > 0) {
			throw new SessionException(self::SESSION_SERVER_FAILED, $result['retcode'], $result['delaytime']);
		}

		return true;
	}
	
	/**
	 * 根据SID删除session
	 * @param	string	$entry 产品标识.目前只有sso
	 * @param	string	$sid session id
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * @return	bool	成功，true
	 * 		    SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function destroy_by_sid($entry, $sid, $ip, $signkey) {
		return self::_destroy($entry, null, $sid, $ip, $signkey);
	}
	
	/**
	 * 根据UID删除session
	 * @param	string	$entry 产品标识.目前只有sso
	 * @param	string	$uid 用户唯一号
	 * @param	string	$ip 客户端ip
	 * @param	string	$signkey 签名key
	 * @return	bool	成功，true
	 * 		    SessionException	失败	，抛出异常，包含错误号以及发生该错误应该延迟的时间（在这个时间段之内不要再发请求）
	 */
	public static function destroy_by_uid($entry, $uid, $ip, $signkey) {
		return self::_destroy($entry, $uid, null, $ip, $signkey);
	}
	/**
	 * 设置超时长
	 * @param	int	$time 超时时长
	 * @return	bool	成功，true
	 */
	public static function settimeout($time) {
		self::$NOTICE_TIME_OUT = $time;
	}
	/**
	 * 获取超时时长
	 * @param	void
	 * @return	int	$NOTICE_TIME_OUT
	 */
	public static function gettimeout() {
		return self::$NOTICE_TIME_OUT;
	}
}
