<?php

/**
 * File操作类
 * 
 * self::createDir('a/1/2/3');                建立文件夹  建一个a/1/2/3文件夹
 * self::createFile('b/1/2/3');               建立文件    在b/1/2/文件夹下面建一个3文件
 * self::createFile('b/1/2/3.exe');           建立文件    在b/1/2/文件夹下面建一个3.exe文件
 * self::copyDir('b','d/e');                  复制文件夹  建立一个d/e文件夹，把b文件夹下的内容复制进去
 * self::copyFile('b/1/2/3.exe','b/b/3.exe'); 复制文件    建立一个b/b文件夹，并把b/1/2文件夹中的3.exe文件复制进去
 * self::moveDir('a/','b/c');                 移动文件夹  建立一个b/c文件夹,并把a文件夹下的内容移动进去，并删除a文件夹
 * self::moveFile('b/1/2/3.exe','b/d/3.exe'); 移动文件    建立一个b/d文件夹，并把b/1/2中的3.exe移动进去                   
 * self::unlinkFile('b/d/3.exe');             删除文件    删除b/d/3.exe文件
 * self::unlinkDir('d');                      删除文件夹  删除d文件夹
 * 
 * @package Helper
 * @author  baojun <baojun@sina.com>
 */

abstract class Helper_File{
	
	/**
	 * 建立文件夹
	 *
	 * @param string $aimUrl aim url
	 *     	
	 * @return viod
	 */
	public static function createDir($aimUrl) {
		$aimUrl = str_replace ( '', '/', $aimUrl );
		$aimDir = '';
		$arr = explode ( '/', $aimUrl );
		$result = true;
		foreach ( $arr as $str ) {
			$aimDir .= $str . '/';
			if (! file_exists ( $aimDir )) {
				$result = mkdir ( $aimDir );
			}
		}
		return $result;
	}
	
	/**
	 * 建立文件夹v2
	 * 
	 * @param string $dir  dir
	 * @param number $mode mode
	 * 
	 * @return boolean
	 */
	public static function mkdirs($dir, $mode = 0777) {
		if (is_dir ( $dir ) || @mkdir ( $dir, $mode ))
			return true;
		if (! self::mkdirs ( dirname ( $dir ), $mode ))
			return false;
		return @mkdir ( $dir, $mode );
	}
	
	/**
	 * 建立文件
	 *
	 * @param string $aimUrl     aim url     	
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * 
	 * @return boolean
	 */
	public static function createFile($aimUrl, $overWrite = false) {
		if (file_exists ( $aimUrl ) && $overWrite == false) {
			return false;
		} elseif (file_exists ( $aimUrl ) && $overWrite == true) {
			self::unlinkFile ( $aimUrl );
		}
		$aimDir = dirname ( $aimUrl );
		self::createDir ( $aimDir );
		touch ( $aimUrl );
		return true;
	}
	
	/**
	 * 移动文件夹
	 *
	 * @param string $oldDir     old dir    	
	 * @param string $aimDir     aim dir      	
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * 
	 * @return boolean
	 */
	public static function moveDir($oldDir, $aimDir, $overWrite = false) {
		$aimDir = str_replace ( '', '/', $aimDir );
		$aimDir = substr ( $aimDir, - 1 ) == '/' ? $aimDir : $aimDir . '/';
		$oldDir = str_replace ( '', '/', $oldDir );
		$oldDir = substr ( $oldDir, - 1 ) == '/' ? $oldDir : $oldDir . '/';
		if (! is_dir ( $oldDir )) {
			return false;
		}
		if (! file_exists ( $aimDir )) {
			self::createDir ( $aimDir );
		}
		@ $dirHandle = opendir ( $oldDir );
		if (! $dirHandle) {
			return false;
		}
		while ( false !== ($file = readdir ( $dirHandle )) ) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (! is_dir ( $oldDir . $file )) {
				self::moveFile ( $oldDir . $file, $aimDir . $file, $overWrite );
			} else {
				self::moveDir ( $oldDir . $file, $aimDir . $file, $overWrite );
			}
		}
		closedir ( $dirHandle );
		return rmdir ( $oldDir );
	}
	
	/**
	 * 移动文件
	 *
	 * @param string $fileUrl    file url       	
	 * @param string $aimUrl     aim url     	
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * 
	 * @return boolean
	 */
	public static function moveFile($fileUrl, $aimUrl, $overWrite = false) {
		if (! file_exists ( $fileUrl )) {
			return false;
		}
		if (file_exists ( $aimUrl ) && $overWrite = false) {
			return false;
		} elseif (file_exists ( $aimUrl ) && $overWrite = true) {
			self::unlinkFile ( $aimUrl );
		}
		$aimDir = dirname ( $aimUrl );
		self::createDir ( $aimDir );
		rename ( $fileUrl, $aimUrl );
		return true;
	}
	
	/**
	 * 删除文件夹
	 *
	 * @param string $aimDir aim Directory   
	 *     	
	 * @return boolean
	 */
	public static function unlinkDir($aimDir) {
		$aimDir = str_replace ( '', '/', $aimDir );
		$aimDir = substr ( $aimDir, - 1 ) == '/' ? $aimDir : $aimDir . '/';
		if (! is_dir ( $aimDir )) {
			return false;
		}
		$dirHandle = opendir ( $aimDir );
		while ( false !== ($file = readdir ( $dirHandle )) ) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (! is_dir ( $aimDir . $file )) {
				self::unlinkFile ( $aimDir . $file );
			} else {
				self::unlinkDir ( $aimDir . $file );
			}
		}
		closedir ( $dirHandle );
		return rmdir ( $aimDir );
	}
	
	/**
	 * 删除文件
	 *
	 * @param string $aimUrl aim url
	 *        	
	 * @return boolean
	 */
	public static function unlinkFile($aimUrl) {
		if (file_exists ( $aimUrl )) {
			unlink ( $aimUrl );
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 复制文件夹
	 *
	 * @param string $oldDir     old dir      	
	 * @param string $aimDir     aim dir        	
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * 
	 * @return boolean
	 */
	public static function copyDir($oldDir, $aimDir, $overWrite = false) {
		$aimDir = str_replace ( '', '/', $aimDir );
		$aimDir = substr ( $aimDir, - 1 ) == '/' ? $aimDir : $aimDir . '/';
		$oldDir = str_replace ( '', '/', $oldDir );
		$oldDir = substr ( $oldDir, - 1 ) == '/' ? $oldDir : $oldDir . '/';
		if (! is_dir ( $oldDir )) {
			return false;
		}
		if (! file_exists ( $aimDir )) {
			self::createDir ( $aimDir );
		}
		$dirHandle = opendir ( $oldDir );
		while ( false !== ($file = readdir ( $dirHandle )) ) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			if (! is_dir ( $oldDir . $file )) {
				self::copyFile ( $oldDir . $file, $aimDir . $file, $overWrite );
			} else {
				self::copyDir ( $oldDir . $file, $aimDir . $file, $overWrite );
			}
		}
		return closedir ( $dirHandle );
	}
	
	/**
	 * 复制文件
	 *
	 * @param string  $fileUrl   file url    	
	 * @param string  $aimUrl    aim url
	 * @param boolean $overWrite 该参数控制是否覆盖原文件
	 * 
	 * @return boolean
	 */
	public static function copyFile($fileUrl, $aimUrl, $overWrite = false) {
		if (! file_exists ( $fileUrl )) {
			return false;
		}
		if (file_exists ( $aimUrl ) && $overWrite == false) {
			return false;
		} elseif (file_exists ( $aimUrl ) && $overWrite == true) {
			self::unlinkFile ( $aimUrl );
		}
		$aimDir = dirname ( $aimUrl );
		self::createDir ( $aimDir );
		copy ( $fileUrl, $aimUrl );
		return true;
	}
}