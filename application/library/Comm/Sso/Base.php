<?PHP
/**
 * SSOBase class file.
 *
 * @package SSOClinent
 * @author lijunjie <junjie2@staff.sina.com.cn>
 * @author liuzhiyu <zhiyu@staff.sina.com.cn>
 * @copyright Copyright (c) 2011 SINA R&D Centre
 * @version $Id: SSOBase.php 34552 2011-03-17 03:38:11Z zhiyu $
 */

class Comm_Sso_Base {
	/**
	 * 当前版本号
	 */
    const VERSION		= 'PHP5-1.4';
    /**
     * 标识是否出错
     */
    private $_is_error	= false;
    /**
     * 错误描述
     */
    private $_err_str	= '';	
    /**
     * 错误代码
     */
    private $_err_no	= '';	

    //============== method about error ==========//
    /**
     * 设置出错信息
     */
    protected function _setError($str,$num = '') {
        $this->_is_error = true;
        $this->_err_str = $str;
        $this->_err_no = $num;
        return true;
    }
    /**
     * 判断是否出错
     */
    public function isError() {
        return $this->_is_error;
    }
    /**
     * 返回出错信息
     */
    public function getError() {
        return $this->_err_str;
    }
    /**
     * 返回错误号
     */
    public function getErrno() {
        return $this->_err_no;
    }
    /**
     * 清除出错信息
     */
    public function clearError() {
        $this->_is_error = false;
        $this->_err_str = '';
        $this->_err_no = '';
        return true;
    }

}
?>
