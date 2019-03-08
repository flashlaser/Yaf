<?php

/**
 * 代码规范检测工具
 *
 * 使用方法：
 * 1.安装phpcs，见 http://tech.intra.weibo.com/App:code_standard
 * 2.php需要有执行系统命令的权限
 * 3.调用方法：
 *  $phpcs = new Helper_Phpcs($phpcs_path, $code_dir, $file_types);
 *  $phpcs->run();
 *
 * @package Helper
 * @author  张宝军 <baojun4545@sina.com>
 */

class Helper_Phpcs{

    private $_phpcs_path = '';
    private $_code_dir = '';
    private $_file_types = '';
    private $_len_type = 0;
    private $_len_var = 0;
    private $_data = array();

    /**
     * 构造方法
     *
     * @param string $phpcs_path phpcs 的路径，如 /usr/local/weibocs/bin/phpcs
     * @param string $code_dir   被检测代码的目录，必须以”/“结尾
     * @param array $file_types  需检测的文件类型的扩展名列表
     *
     * @return void
     */
    public function __construct($phpcs_path, $code_dir, $file_types = array('php', 'phtml')) {
        $this->_phpcs_path = $phpcs_path;
        $this->_code_dir = $code_dir;
        $this->_file_types = $file_types;
    }

    /**
     * run
     * 
     * @return boolean
     */
    public function run() {
        if (!empty($_GET['f'])) {//检查
            $file = $this->_code_dir . trim($_GET['f']);
            $cmd = $this->_phpcs_path . ' --standard="Weibo" --report="weibocsv"';
            $cmd .= ' --encoding="utf-8" --severity=3 "' . $file . '"';

            $result = $count = array();
            $handle = popen($cmd, 'r');
            $header = fgetcsv($handle);
            while (!feof($handle)) {
                $data = fgetcsv($handle);
                if (!$data) {
                    continue;
                }

                $data = array_combine($header, $data);
                $result[] = $data;
                isset($count[$data['Type']]) ? ++$count[$data['Type']] : $count[$data['Type']] = 1;
            }
            pclose($handle);

            $this->_outPutResult($result, $count);

            //passthru($cmd);
        } else {
            $arr_dir_ex = array();
            if (isset($_GET['dir'])) {
                $arr_dir_ex = explode('/', $_GET['dir']);
            }
            $this->_outPut($this->_code_dir, $arr_dir_ex);
        }

        return false;
    }

    /**
     * get file list
     * 
     * @param string $path path
     * 
     * @return void|unknown
     */
    protected function _getFlieLsit($path) {
        if (!is_dir($path)) {
            return;
        }
        $arr['dir'] = array();
        $arr['file'] = array();

        $handle = opendir($path);
        if (!$handle) {
            return;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file{0} != '.') {
                if (is_dir($path . $file)) {
                    $arr['dir'][] = $file;
                } else {
                    $type = pathinfo($file, PATHINFO_EXTENSION);
                    in_array($type, $this->_file_types) && $arr['file'][] = $file;
                }
            }
        }
        closedir($handle);

        sort($arr['dir']);
        sort($arr['file']);
        return $arr;
    }

    /**
     * show list
     * 
     * @param string $path       path
     * @param string $arr_dir_ex arr dir
     * @param string $dir        dir 
     * @param string $nbsp       nbsp
     * 
     * @return mixed
     */
    protected function _showList($path, $arr_dir_ex, $dir = '', $nbsp = '') {
        $self = $_SERVER['SCRIPT_URL'];
        $arr_list = $this->_getFlieLsit($path . $dir);
        $n_dir = count($arr_list['dir']);
        $n_file = count($arr_list['file']);

        $dir_now = array_shift($arr_dir_ex);
        for ($i = 0; $i < $n_dir; $i++) {
            if ($arr_list['dir'][$i] == $dir_now) {
                echo $nbsp . '&nbsp;<font color="red">-</font>&nbsp;' . ($i + 1) . '、<a href="' . $self . '?dir=' . $dir . '"><b>' . $arr_list['dir'][$i] . '</b></a><br>' . "\n";
                if ($i == $n_dir - 1)
                    $this->_showList($path, $arr_dir_ex, $dir . $dir_now . '/', $nbsp . '&nbsp;&nbsp;&nbsp;');
                else
                    $this->_showList($path, $arr_dir_ex, $dir . $dir_now . '/', $nbsp . '&nbsp;|&nbsp;');
            } else {
                echo $nbsp . '&nbsp;<font color="red">+</font>&nbsp;' . ($i + 1) . '、<a href="' . $self . '?dir=' . $dir . $arr_list['dir'][$i] . '/"><b>' . $arr_list['dir'][$i] . '</b></a><br>' . "\n";
            }
        }

        for ($i = 0; $i < $n_file; $i++) {
            echo $nbsp . '&nbsp;&nbsp;&nbsp;' . ($i + 1) . '、
                ' . $arr_list['file'][$i] . '&nbsp;&nbsp;
                <a target="check" href="?f=' . $dir . $arr_list['file'][$i] . '"  >
                    检查
                </a>&nbsp;&nbsp;
                &nbsp;&nbsp;
                <br>' . "\n";
        }
    }

    /**
     * format 
     * 
     * @param string $str str
     * 
     * @return string
     */
    public function format($str) {
        $str = $this->formatComment($str);
        $str = str_replace("\r\n", "\n", $str);
        return $str;
    }

    /**
     * format 
     * 
     * @param type $str str
     * 
     * @return string
     */
    public function formatComment($str) {
        echo '<xmp>';
        $reg = '#/\*\*((?!\*/).)+\*/[\s\t\n]*([a-z]+\s+)+?function#is';
        return preg_replace_callback($reg, array($this, 'callbackComment'), $str);
    }

    /**
     * callback comment
     * 
     * @param type $match match
     * 
     * @return type
     */
    public function callbackComment($match) {


        //print_r($match);

        $this->_data = array();
        $this->_len_type = 0;
        $this->_len_var = 0;

        //$reg = '/(\s*\*)\s@param[\s\t]+([^\s\t\r\n]+[\s\t]+)(\$[^\s\t\r\n]+[\s\t]*)([^\r\n]*)/';
        $reg = '/([ \t]*\*)[ \t]@param[ \t]+([^\s]+[ \t]+)(\$[a-z0-9_]+[ \t]*)(.*)/i';
        $str = preg_replace_callback($reg, array($this, 'callbackParam'), $match[0]);

        $s = $r = array();
        foreach ($this->_data as $k => $v) {
            $comm = trim($v[4]);
            !$comm && $comm = '/';
            $s[] = $k;
            $r[] = $v[1] . ' @param ' . str_pad($v[2], $this->_len_type) . ' '
                . str_pad($v[3], $this->_len_var) . ' ' . $comm;
        }

        //print_r($r);

        $str = str_replace($s, $r, $str);

        return $str;
    }

    /**
     * callback
     * 
     * @param string $match match
     * 
     * @return string
     */
    public function callbackParam($match) {
        //print_r($match);
        //echo '(',$match[0],")\n";

        $match[2] = trim($match[2]);
        $match[3] = trim($match[3]);
        $len_type = strlen($match[2]);
        $len_var = strlen($match[3]);
        if ($len_type > $this->_len_type) {
            $this->_len_type = $len_type;
        }
        if ($len_var > $this->_len_var) {
            $this->_len_var = $len_var;
        }


        $str_re = '@param' . $match[3];
        $this->_data[$str_re] = $match;

        return $str_re;
    }

    /**
     * output
     * 
     * @param string $path 路径
     * @param array  $arr_dir_ex 数据
     * 
     * @return void
     */
    protected function _outPut($path, $arr_dir_ex) {
        ?>

        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>代码检查</title>
                <style type="text/css">
                    <!--
                    a:link {
                        color: #0000FF;
                        text-decoration: none;
                    }
                    a:visited {
                        text-decoration: none;
                        color: #0000FF;
                    }
                    a:hover {
                        text-decoration: none;
                        color: #0000FF;
                    }
                    a:active {
                        text-decoration: none;
                        color: #FF00FF;
                    }
                    -->
                </style>
            </head>
            <body>

                <?php
                echo '<a href="?"><b>' . $path . '</b></a><br>' . "\n";
                $this->_showList($path, $arr_dir_ex);
                ?>
            </body>
        </html>
        <?php
    }
    
    /**
     * output 
     * 
     * @param array $result  结果集
     * @param unknown $count 计数
     * 
     * @return void
     */
    protected function _outPutResult($result, $count) {
        $first = reset($result);
        ?>
        <?php echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';    //直接写HTML Zendstudio会报错 ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>代码检查结果</title>
                <style type="text/css">
                    body{
                        background: #000;
                        color: lawngreen;
                    }
                    .center{
                        text-align:center;
                    }
                    .type_error{
                        color: #FF00FF;
                    }
                    table.result {
                        border:3px #3B5998 double;
                        font-size: 16px;
                        margin:0 auto;
                    }
                    table.result caption{
                        padding-bottom: 5px;
                        font-size: 24px;
                        font-weight: bold;

                    }
                    table.result caption em{
                        font-style: normal;
                        color: aqua;
                    }
                    table.result thead{
                        /*background-color:#C3D9FF;*/
                        text-align: center;
                        color:#CCC;
                    }
                    table.result th, table.result td{
                        padding:5px;
                    }
                    table.result td{
                        border-top: 1px #bdc7d8 dotted;
                    }
                    table.result .col_type {
                        min-width: 80px;
                        text-align: left;
                    }
                    table.result .col_line, table.result .col_column{
                        min-width:50px;
                    }
                </style>
            </head>
            <body>
                <h1 class="center">代码检查结果</h1>
                <h4 class="center"><?php echo htmlspecialchars($first['File'])?></h4>
                <hr />

                <table class="result" cellpadding="0" cellspacing="0">
                    <caption>
                        <?php foreach ($count as $key => $value) :?>
                            <?php echo $key;?> <em><?php echo $value;?></em> ;
                        <?php endforeach;?>
                    </caption>
                    <thead>
                        <tr>
                            <th class="col_type">错误类别</th>
                            <th class="col_line">行</th>
                            <th class="col_column">列</th>
                            <th class="col_msg">错误内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $value) :?>
                            <tr class="type_<?php echo strtolower($value['Type'])?>">
                                <td><?php echo $value['Type']?></td>
                                <td class="center"><?php echo $value['Line']?></td>
                                <td class="center"><?php echo $value['Column']?></td>
                                <td><?php echo htmlspecialchars($value['Message'])?></td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </body>
        </html>
        <?php
    }

}

