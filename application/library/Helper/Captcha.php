<?php
/**
 * captcha类
 *
 * @package helper
 * @author  baojun <baojun4545@sina.com>
 */
class Helper_Captcha {
    //图片大小常量定义

    const HUGE   = "huge";
    const NORMAL = "normal";
    const SMALL  = "small";
    const TINY   = "tiny";
    const RULE   = "rule";
    const BIG    = "big";
    const POST   = "post";

    //图片格式常量定义
    const JPEG = "JPEG";
    const PNG  = "PNG";
    const GIF  = "GIF";
    const WBMP = "WBMP";

    //需要种cookie的KEY
    const COOKIE_NAME = 'weiba_captcha';

    //验证码图片的宽度
    protected $imgwidth;
    //验证码图片的高度
    protected $imgheight;
    //验证码图片格式
    public $imgformat;
    //生成的验证码随机字符串
    protected $imgtext;
    protected $leftspace;
    protected $huge_font = array('wqy-zenhei' => array('spacing'    => -2, 'minSize'    => 30, 'maxSize'    => 35, 'font'       => 'wqy-zenhei.ttf'));
    protected $normal_font = array('wqy-zenhei' => array('spacing'   => -1, 'minSize'   => 20, 'maxSize'   => 23, 'font'      => 'wqy-zenhei.ttf'));
    protected $small_font = array('wqy-zenhei' => array('spacing'  => -1, 'minSize'  => 15, 'maxSize'  => 18, 'font'     => 'wqy-zenhei.ttf'));
    protected $tiny_font = array('wqy-zenhei' => array('spacing'  => -1, 'minSize'  => 10, 'maxSize'  => 13, 'font'     => 'wqy-zenhei.ttf'));
    protected $rule_font = array('wqy-zenhei' => array('spacing' => -2, 'minSize' => 30, 'maxSize' => 35, 'font'    => 'wqy-zenhei.ttf'));
    protected $big_font = array('wqy-zenhei' => array('spacing'  => -2, 'minSize'  => 30, 'maxSize'  => 35, 'font'     => 'wqy-zenhei.ttf'));
    protected $post_font = array('wqy-zenhei' => array('spacing'        => -1, 'minSize'        => 17, 'maxSize'        => 20, 'font'           => 'wqy-zenhei.ttf'));
    public $fonts;
    //验证码图片背景颜色
    public $backgroundColor = array(255, 255, 255);
    //创建图片时图片被放大的倍数 ，为了图像有更好的显示质量，1-low 2-middle 3-high
    public $scale         = 2;
    //验证码最少字符个数
    public $minWordLength = 4;
    //验证码最大字符个数
    public $maxWordLength = 4;
    //是否开启debug模式
    public $debug         = false;
    //GD image图片
    public $img;
    //是否使用过滤器用高斯算法模糊图像
    public $blur          = false;
    //字体资源路径
    private $_ttf_path      = '/usr/local/SRV2/lib/X11/fonts/TTF/';

    /**
     * Font configuration 字体配置
     *
     * - font: TTF file 字体
     * - spacing: 字符间间隙空间
     * - minSize: 最小字体
     * - maxSize: 最大字体
     */
    //字符颜色，红，绿，蓝
    public $colors = array(array(221, 40, 9), array(26, 161, 40), array(30, 79, 184));
    //字符矢量变形配置
    public $Yperiod     = 12;
    public $Yamplitude  = 9;
    public $Xperiod     = 11;
    public $Xamplitude  = 4;
    //单个字符随机倾斜的角度范围配置
    public $maxRotation = 9;

    /**
     * 初始化
     * 
     * @param string $size size
     * @param string $type type 
     * 
     * @return void
     */
    public function __construct($size = self::HUGE, $type = self::PNG) {
        $this->setimginfo($size, $type);
    }

    /**
     * 设置验证码图片信息
     * @param unknown_type $size 验证码图片的size 默认为HUGE
     * @param unknown_type $type 验证码图片的格式   默认为PNG
     */
    public function setImgInfo($size = self::HUGE, $type = self::PNG) {
        //设置验证码图片大小
        switch ($size) {
            case self::HUGE :
                $this->imgwidth = 120;
                $this->imgheight = 45;
                $this->fonts = $this->huge_font;
                $this->leftspace = 25;
                break;
            case self::SMALL :
                $this->imgwidth = 60;
                $this->imgheight = 30;
                $this->fonts = $this->small_font;
                $this->leftspace = 12;
                break;
            case self::TINY :
                $this->imgwidth = 40;
                $this->imgheight = 15;
                $this->fonts = $this->tiny_font;
                $this->leftspace = 8;
                break;
            case self::BIG :
                $this->imgwidth = 100;
                $this->imgheight = 35;
                $this->fonts = $this->big_font;
                $this->leftspace = 20;
                break;
            case self::RULE :
                $this->imgwidth = 450;
                $this->imgheight = 50;
                $this->fonts = $this->rule_font;
                $this->leftspace = 90;
                break;
            case self::POST :
                $this->imgwidth = 69;
                $this->imgheight = 26;
                $this->fonts = $this->post_font;
                $this->leftspace = 14;
                break;
            default :
                $this->imgwidth = 80;
                $this->imgheight = 30;
                $this->fonts = $this->normal_font;
                $this->leftspace = 18;
                break;
        }
        //设置验证码图片格式
        if ($type == self::JPEG || $type == self::GIF || $type == self::WBMP || $type == self::PNG) {
            $this->imgformat = $type;
        } else {
            $this->imgformat = self::PNG;
        }
        $gdinfo = gd_info();
        if (!$gdinfo[$this->imgformat . " Support"]) {
            $this->imgformat = "";
            return false;
        }
        return true;
    }

    /**
     * 获取验证码随机字符
     * 
     * @param int $length 验证码随机字符的个数
     * 
     * @return string
     */
    protected function getRandomCaptchaText($length = null) {
        if (empty($length)) {
            $length = mt_rand($this->minWordLength, $this->maxWordLength);
        }

        $words = "abcdefghijlmnpqrstvwyz";
        $text  = "";
        for ($i     = 0; $i < $length; $i++) {
            $text .= substr($words, mt_rand(0, 22), 1);
        }
        return $text;
    }

    /**
     * 将验证码字符写入MC 并种cookie
     * 
     * @return mixed
     */
    private function _captchaText2MC() {
        $mc_key = md5(uniqid() . '_' . Yaf_Registry::get('current_uid'));

        setcookie(self::COOKIE_NAME, $mc_key, null, '/');
        return $this->addCaptcha($mc_key, $this->imgtext);
    }

    /**
     * 创建图像
     */
    public function createImage() {
        $ini = microtime(true);

        //画布初始化
        $this->imageAllocate();
        //获取随机验证码字符
        $this->imgtext = $this->getRandomCaptchaText();
        $fontcfg = $this->fonts[array_rand($this->fonts)];
        $this->drawConfusionLines();
        $this->writeText($this->imgtext, $fontcfg);
        $this->_captchaText2MC();

        //字体矢量变形
        //$this->waveImage();
        if ($this->blur && function_exists('imagefilter')) {
            imagefilter($this->img, IMG_FILTER_GAUSSIAN_BLUR);
        }

        //缩减图片大小
        $this->ReduceImage();

        if ($this->debug) {
            imagestring($this->img, 1, 1, $this->imgheight - 8, "$text {$fontcfg['font']} " . round((microtime(true) - $ini) * 1000) . "ms", $this->GdFgColor);
        }

        //输出
        $this->WriteImage();
        $this->Cleanup();
    }

    /**
     * 创建image画布及画笔资源分配
     */
    protected function imageAllocate() {
        //释放当前image
        if (!empty($this->img)) {
            imagedestroy($this->img);
        }

        $this->img = imagecreatetruecolor($this->imgwidth * $this->scale, $this->imgheight * $this->scale);

        //背景颜色
        $this->GdBgColor = imagecolorallocate($this->img, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        imagefilledrectangle($this->img, 0, 0, $this->imgwidth * $this->scale, $this->imgheight * $this->scale, $this->GdBgColor);

        //前端字体颜色
        $color = $this->colors[mt_rand(0, sizeof($this->colors) - 1)];
        $this->GdFgColor = imagecolorallocate($this->img, $color[0], $color[1], $color[2]);
    }

    /**
     * 将生成的验证码写到画布上
     * @param unknown_type $text 验证码字符
     * @param unknown_type $fontcfg 字体配置文件
     */
    protected function writeText($text, $fontcfg = array()) {
        if (empty($fontcfg)) {
            $fontcfg = $this->fonts[array_rand($this->fonts)];
        }

        //全路径字体资源
        $fontfile = $this->_ttf_path . $fontcfg['font'];

        //如果字符个数较少则增加字体的大小每少一个字符增加10%的字体大小增量
        $lettersMissing = $this->maxWordLength - strlen($text);
        $fontSizefactor = 1 + ($lettersMissing * 0.1);

        //逐个写字符到画布上
        $x      = $this->leftspace * $this->scale;
        $y      = round(($this->imgheight * 27 / 40) * $this->scale);
        $length = strlen($text);
        for ($i      = 0; $i < $length; $i++) {
            $degree   = mt_rand($this->maxRotation * (-1), $this->maxRotation);
            $fontsize = mt_rand($fontcfg['minSize'], $fontcfg['maxSize']) * $this->scale * $fontSizefactor;
            $letter   = substr($text, $i, 1);

            $coords = imagettftext($this->img, $fontsize, $degree, $x, $y, $this->GdFgColor, $fontfile, $letter);
            $x += ($coords[2] - $x) + ($fontcfg['spacing'] * $this->scale);
        }
    }

    /**
     * 画干扰线
     */
    protected function drawConfusionLines() {
        $y_edge = mt_rand(15 * $this->scale, ($this->imgheight - 15) * $this->scale);
        $x_edge = 15 * $this->scale;
        $x1     = $x_edge;
        $y1     = $this->imgheight * $this->scale - $y_edge;
        $x2     = $this->imgwidth * $this->scale - $x_edge * 2;
        $y2     = $y_edge;
        imagesetthickness($this->img, mt_rand(2, 3) * $this->scale);
        imageline($this->img, $x1, $y1, $x2, $y2, $this->GdFgColor);
    }

    /**
     * 画干扰点
     */
    protected function drawConfusionPixel() {
        for ($i = 0; $i < intval($this->imgwidth * $this->imgheight / 20); $i++) {
            imagesetpixel($this->img, rand() % ($this->imgwidth), rand() % ($this->imgheight), imagecolorallocate($this->img, 0, 0, 0));
        }
    }

    /**
     * 对图像进行矢量变形
     */
    protected function waveImage() {
        // X轴方向矢量变形
        $xp = $this->scale * $this->Xperiod * rand(1, 2);
        $k  = mt_rand(0, 100);
        for ($i  = 0; $i < ($this->imgwidth * $this->scale); $i++) {
            imagecopy($this->img, $this->img, $i - 1, sin($k + $i / $xp) * ($this->scale * $this->Xamplitude), $i, 0, 1, $this->imgheight * $this->scale);
        }

        // Y轴方向矢量变形
        $k  = mt_rand(0, 100);
        $yp = $this->scale * $this->Yperiod * rand(1, 2);
        for ($i  = 0; $i < ($this->imgheight * $this->scale); $i++) {
            imagecopy($this->img, $this->img, sin($k + $i / $yp) * ($this->scale * $this->Yamplitude), $i - 1, 0, $i, $this->imgwidth * $this->scale, 1);
        }
    }

    /**
     * 缩减最终验证码图像的size
     */
    protected function reduceImage() {
        $imResampled = imagecreatetruecolor($this->imgwidth, $this->imgheight);
        imagecopyresampled($imResampled, $this->img, 0, 0, 0, 0, $this->imgwidth, $this->imgheight, $this->imgwidth * $this->scale, $this->imgheight * $this->scale);
        imagedestroy($this->img);
        $this->img = $imResampled;
    }

    /**
     * 输出图像
     */
    protected function writeImage() {
        if (function_exists("image" . $this->imgformat)) {
            header("Content-type: image/$this->imgformat" . "\n\n");
            $showimgfunc = "image" . $this->imgformat;
            $showimgfunc($this->img);
        }
    }

    /**
     * 清理图像，释放内存
     */
    protected function cleanup() {
        imagedestroy($this->img);
    }

    /**
     * 将验证码数据写入MC
     * 
     * @param string $key  key
     * @param string $code code
     * 
     * @return	boolean
     */
    protected function addCaptcha($key, $code) {
        if (strlen($key) < 1 || strlen($code) < 1) {
            return false;
        }

        $result = Comm_Mc::init()->setData('captcha', array($key), $code);
        return $result;
    }

    /**
     * 验证用户输入的验证码是否正确
     * 
     * @param string $input input
     * 
     * @return boolean
     */
    public static function verifyCapt($input) {
        $key = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : '';

        if (strlen($key) < 1 || strlen($input) < 1) {
            throw new Exception_Msg(302005);
        }
        $input = strtolower($input); //强制转为小写
        //获取Code数据
        $mc    = Comm_Mc::init();
        $code  = $mc->getData('captcha', array($key));

        //清理保存的验证码数据
        $mc->deleteData('captcha', array($key));
        setcookie(self::COOKIE_NAME, null, null, '/');

        if (!$code || $code !== $input) {
            throw new Exception_Msg(302004);
        }

        return true;
    }

}
