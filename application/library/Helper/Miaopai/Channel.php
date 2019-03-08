<?php

/**
 * Channel
 *
 * @package helper
 * @author  baojun <baojun4545@sina.com>
 */

define('CHANNELUTIL_ILL_CHANNEL', 0);
define('CHANNELUTIL_SCID_KEY', "PZW2agjMSCID");

class Helper_Miaopai_Channel {
	
	static public function channelutil_decodeCid($scid) {
		$scid = isset ( $scid ) ? $scid : null;
		if (empty($scid))
			return CHANNELUTIL_ILL_CHANNEL;
		try {
			$decrypt = Helper_Xxtea::decrypt ( $scid, CHANNELUTIL_SCID_KEY );
			$channelId = self::channelutil_extraCidFromCusid ( $decrypt );
			return intval ( $channelId );
		} catch ( Exception $e ) {
			return CHANNELUTIL_ILL_CHANNEL;
		}
		$decrypt = isset ( $decrypt ) ? $decrypt : null;
		if (empty ($decrypt)) {
			return CHANNELUTIL_ILL_CHANNEL;
		}
	}
	
	static public function channelutil_extraCidFromCusid($cusid){
		$cid = CHANNELUTIL_ILL_CHANNEL;
		$end = strrpos($cusid, '_');
		if($end !== FALSE)
			$cusid = substr($cusid, 0, $end);
			return $cusid;
	}

	/**
	 * scid解码
	 * @param string $scid scid
	 *
	 * @return string|number
	 */
	static public function decodeCid($scid) {
		$scid = isset ( $scid ) ? $scid : null;
		if (empty($scid))
			return CHANNELUTIL_ILL_CHANNEL;
		try {
			$decrypt = Helper_Xxtea::decrypt ( $scid, CHANNELUTIL_SCID_KEY );
			$channelId = self::_praseCid( $decrypt );
			return intval ( $channelId );
		} catch ( Exception $e ) {
			return CHANNELUTIL_ILL_CHANNEL;
		}
		$decrypt = isset ( $decrypt ) ? $decrypt : null;
		if (empty ($decrypt)) {
			return CHANNELUTIL_ILL_CHANNEL;
		}
	}
	/**
	 * cid编码
	 *
	 * @param string $cid           cid
	 * @param string $upload_server upload server
	 *
	 * @return string|number
	 */
	static public function encodeCid($cid, $upload_server) {
		$scid = $cid . '_' . $upload_server;

		$encrypt = Helper_Xxtea::encrypt($scid, CHANNELUTIL_SCID_KEY );

		return $encrypt;
	}

	/**
	 * 解析视频id
	 *
	 * @param string $cusid cusid
	 *
	 * @return string
	 */
	static private function _praseCid($cusid){
		$cid = CHANNELUTIL_ILL_CHANNEL;
		$end = strrpos($cusid, '_');
		if ($end !== false) {
			$cusid = substr($cusid, 0, $end);
		}

		return $cusid;
	}

}