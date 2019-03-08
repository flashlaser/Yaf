<?php
/**
 * csv 文件下载类.直接输出数据流提供csv文件下载
 *
 * $arr = array(
  array(234,345,45,345,345),
  array(234,345,45,345,345),
  array(234,345,45,345,345),
  array(234,345,45,345,345),
  );

  $obj = new csv( 'csv', 'utf-8' );
  $obj->put_row(array('编号1','编号2','编号3','编号4','编号5'));
  $obj->put_rows($arr);
  $obj->put_rows($arr);
 *
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */

class Helper_Csv{

    protected $h_out   = null; //
    protected $charset = null;

    /**
     * 初始化 
     * 
     * @param string $file_name   下载后文件显示名称,不含扩展名
     * @param string $charset     设置下载数据的原字符集,内部会自动转码为gbk
     * @param string $output_path output path
     * 
     * @return void
     */
    public function __construct($file_name, $charset = 'utf-8', $output_path = 'php://output') {
        $this->h_out = fopen($output_path, 'w');
        $this->charset = strtolower($charset);
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$file_name.csv");
    }

    /**
     * 输出一行
     * @param array $row 一维数组
     */
    public function putRow($row) {
        if ($this->charset != 'gbk') {
            foreach ($row as &$v) {
                $v = iconv($this->charset, 'gbk', $v);
            }
        }
        fputcsv($this->h_out, $row);
    }

    /**
     * 一次输出多行
     * @param array $rows 二维数组
     */
    public function putRows($rows) {
        foreach ($rows as $row) {
            $this->putRow($row);
        }
    }

    /**
     * destruct 
     */
    public function __destruct() {
        fclose($this->h_out);
    }

}

