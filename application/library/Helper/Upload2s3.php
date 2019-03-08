<?php
/**
 * 调试辅助类
 *
 * @package helper
 * @author  baojun <baojun@sina.com>
 */
require APP_PATH . 'application/library/Thirdpart/SinaStorageService.class.php';

abstract class Helper_Upload2s3{
    
    /**
     * 按文件url上传
     * 
     * @param string $file      file
     * @param string $dest_name dest name
     * @param string $mimetype  mime type
     * 
     * @return bool
     */
    static public function uploadByFile($file, $dest_name, $mimetype) {
        $o = new SinaStorageService();
        $o->setCURLOPTs(array(CURLOPT_VERBOSE=>1));
        $o->setAuth(true);
        $host = 'http://upcdn.miaopai.com/';
        $o->setDomain($host);
        $file_content = file_get_contents($file);
        $file_size = filesize($file);
        $result = '';
        $r = $o->uploadFile($dest_name, $file_content, $file_size, $mimetype, $result);
        return $r?true:false;
    }

    /**
     * create file name
     * 
     * @param unknown $key key
     * @param unknown $ext ext
     * 
     * @return string
     */
    static public function createFileName($key, $ext){
        return md5($key . uniqid(rand())) . '.' . $ext;
    }
    
    /**
     * get file type
     * 
     * @param array $files   files 
     * @param string $is_ext is ext
     * 
     * @return string[][]|unknown[][]|string[][][]
     */
    static public function getFileType($files=array(), $is_ext=true){
        /*文件扩展名说明
        *7173         gif
        *255216       jpg
        *13780        png
        *6677         bmp
        *239187       txt,aspx,asp,sql
        *208207       xls.doc.ppt
        *6063         xml
        *6033         htm,html
        *4742         js
        *8075         xlsx,zip,pptx,mmap,zip
        *8297         rar
        *01           accdb,mdb
        *7790         exe,dll
        *5666         psd
        *255254       rdp
        *10056        bt种子
        *64101        bat
        */

        $mimes = array( 'hqx'   =>  'application/mac-binhex40',
        'cpt'   =>  'application/mac-compactpro',
        'csv'   =>  array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
        'apk'   =>  'application/vnd.android',
        'bin'   =>  'application/macbinary',
        'dms'   =>  'application/octet-stream',
        'lha'   =>  'application/octet-stream',
        'lzh'   =>  'application/octet-stream',
        'exe'   =>  array('application/octet-stream', 'application/x-msdownload'),
        'class' =>  'application/octet-stream',
        'psd'   =>  'application/x-photoshop',
        'so'    =>  'application/octet-stream',
        'sea'   =>  'application/octet-stream',
        'dll'   =>  'application/octet-stream',
        'oda'   =>  'application/oda',
        'pdf'   =>  array('application/pdf', 'application/x-download'),
        'ai'    =>  'application/postscript',
        'eps'   =>  'application/postscript',
        'ps'    =>  'application/postscript',
        'smi'   =>  'application/smil',
        'smil'  =>  'application/smil',
        'mif'   =>  'application/vnd.mif',
        'xls'   =>  array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
        'ppt'   =>  array('application/powerpoint', 'application/vnd.ms-powerpoint'),
        'wbxml' =>  'application/wbxml',
        'wmlc'  =>  'application/wmlc',
        'dcr'   =>  'application/x-director',
        'dir'   =>  'application/x-director',
        'dxr'   =>  'application/x-director',
        'dvi'   =>  'application/x-dvi',
        'gtar'  =>  'application/x-gtar',
        'gz'    =>  'application/x-gzip',
        'php'   =>  'application/x-httpd-php',
        'php4'  =>  'application/x-httpd-php',
        'php3'  =>  'application/x-httpd-php',
        'phtml' =>  'application/x-httpd-php',
        'phps'  =>  'application/x-httpd-php-source',
        'js'    =>  'application/x-javascript',
        'swf'   =>  'application/x-shockwave-flash',
        'sit'   =>  'application/x-stuffit',
        'tar'   =>  'application/x-tar',
        'tgz'   =>  array('application/x-tar', 'application/x-gzip-compressed'),
        'xhtml' =>  'application/xhtml+xml',
        'xht'   =>  'application/xhtml+xml',
        'zip'   =>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
        'mid'   =>  'audio/midi',
        'midi'  =>  'audio/midi',
        'mpga'  =>  'audio/mpeg',
        'mp2'   =>  'audio/mpeg',
        'mp3'   =>  array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
        'mp4'   =>  'video/mp4',
        'aif'   =>  'audio/x-aiff',
        'aiff'  =>  'audio/x-aiff',
        'aifc'  =>  'audio/x-aiff',
        'ram'   =>  'audio/x-pn-realaudio',
        'rm'    =>  'audio/x-pn-realaudio',
        'rpm'   =>  'audio/x-pn-realaudio-plugin',
        'ra'    =>  'audio/x-realaudio',
        'rv'    =>  'video/vnd.rn-realvideo',
        'wav'   =>  array('audio/x-wav', 'audio/wave', 'audio/wav'),
        'bmp'   =>  array('image/bmp', 'image/x-windows-bmp'),
        'gif'   =>  'image/gif',
        'jpeg'  =>  array('image/jpeg', 'image/pjpeg'),
        'jpg'   =>  array('image/jpeg', 'image/pjpeg'),
        'jpe'   =>  array('image/jpeg', 'image/pjpeg'),
        'png'   =>  array('image/png',  'image/x-png'),
        'tiff'  =>  'image/tiff',
        'tif'   =>  'image/tiff',
        'css'   =>  'text/css',
        'html'  =>  'text/html',
        'htm'   =>  'text/html',
        'shtml' =>  'text/html',
        'txt'   =>  'text/plain',
        'text'  =>  'text/plain',
        'log'   =>  array('text/plain', 'text/x-log'),
        'rtx'   =>  'text/richtext',
        'rtf'   =>  'text/rtf',
        'xml'   =>  'text/xml',
        'xsl'   =>  'text/xml',
        'mpeg'  =>  'video/mpeg',
        'mpg'   =>  'video/mpeg',
        'mpe'   =>  'video/mpeg',
        'qt'    =>  'video/quicktime',
        'mov'   =>  'video/quicktime',
        'avi'   =>  'video/x-msvideo',
        'movie' =>  'video/x-sgi-movie',
        'doc'   =>  'application/msword',
        'docx'  =>  array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
        'xlsx'  =>  array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
        'word'  =>  array('application/msword', 'application/octet-stream'),
        'xl'    =>  'application/excel',
        'eml'   =>  'message/rfc822',
        'json' => array('application/json', 'text/json'),
        'ico' => 'image/x-icon'
        );
        $file_types = array();
        if (empty($files)) {
            return $file_types;
        }

        foreach ($files AS $file) {
            $file_type = '';
            if ($is_ext) {
                $path_info = pathinfo($file);
                if (isset($path_info['extension'])) {
                    $file_type = $path_info['extension'];
                }
            }

            if (empty($file_type)) {
                $fp = @fopen($file, "rb");
                $bin = @fread($fp, 2); //只读2字节
                @fclose($fp);
                $str_info  = @unpack("C2chars", $bin);
                $type_code = intval($str_info['chars1'].$str_info['chars2']);

                switch ($type_code) {
                    case 7790:
                        $file_type = 'exe';
                        break;
                    case 7784:
                        $file_type = 'midi';
                        break;
                    case 8075:
                        $file_type = 'zip';
                        break;
                    case 8297:
                        $file_type = 'rar';
                        break;
                    case 255216:
                        $file_type = 'jpg';
                        break;
                    case 7173:
                        $file_type = 'gif';
                        break;
                    case 6677:
                        $file_type = 'bmp';
                        break;
                    case 13780:
                        $file_type = 'png';
                        break;
                    case 7368:
                        $file_type = 'mp3';
                        break;
                    default:
                        $file_type = 'unknown';
                        break;
                }
            }

            $file_type = strtolower($file_type);

            $file_type_norm = array();
            if (isset($mimes[$file_type])) {
                $file_type_norm['ext'] = $file_type;
                $file_type_norm['mime'] = '';
                if (is_array($mimes[$file_type])) {
                    $file_type_norm['mime'] = $mimes[$file_type][0];
                } else {
                    $file_type_norm['mime'] = $mimes[$file_type];
                }
            }
            if (!empty($file_type_norm)) {
                $file_types[] = $file_type_norm;
            }
        }
        return $file_types;
    }
}