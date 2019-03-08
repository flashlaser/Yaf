<?php

/**
 * 将语言包由简体转换为繁体
 *
 * @package    action
 * @author     baojun <baojun4545@sina.com>
 */
class ActionI18nc2t extends Yaf_Action_Abstract {

    private $i18n_cn_path;
    private $i18n_tw_path;

    //执行Action
    public function execute() {

        //仅允许调试环境下执行此脚本
        if (!Helper_Debug::isDebug()) {
            throw new Exception_Msg(100001, 'Can not run this script in no debug mode.');
        }

        $i18n_path = APP_PATH . '/i18n/';
        $this->i18n_cn_path = $i18n_path . 'zh-cn/';
        $this->i18n_tw_path = $i18n_path . 'zh-tw/';

        $this->convert_all_file($this->i18n_cn_path);
    }

    public function convert_all_file($directory) {
        $files = new DirectoryIterator($directory);
        foreach ($files as $file) {
            $name = $file->getFilename();

            //不操作.和..
            if ($name == '.' || $name == '..') {
                continue;
            }


            if ($file->isDir()) { //递归目录
                $this->convert_all_file($directory . '/' . $name);
            } else {    //处理INI文件
                //处理
                if ($file->getExtension() == 'ini') {
                    $this->convert_file($file);
                }
            }
        }
    }

    //转换文件
    public function convert_file(SplFileInfo $file) {
        $filepath   = $file->getRealPath();
        $content_tw = Helper_Trans::c2t(file_get_contents($filepath));

        $cn_path_len = strlen($this->i18n_cn_path);
        if (substr($filepath, 0, $cn_path_len) !== $this->i18n_cn_path) {
            throw new Exception_Msg(100001, 'path_error');
        }

        $filepath_tw = $this->i18n_tw_path . substr($filepath, $cn_path_len);
        $filedir_tw  = dirname($filepath_tw);
        if (!is_dir($filedir_tw)) {
            mkdir($filedir_tw, 0777, true);
        }

        file_put_contents($filepath_tw, $content_tw);
        @chmod($filepath_tw, 0777);

        echo $filepath_tw . "\r\n";
    }

}
