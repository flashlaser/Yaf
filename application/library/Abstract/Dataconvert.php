<?php
/**
 * 数据转换抽象类
 *
 * @package dc
 * @author  baojun <baojun4545@sina.com>
 */
abstract class Abstract_Dataconvert{

    // 时间格式
    const DATE_FORMAT = 'D M d H:i:s O Y';

    /**
     * 数据内容
     *
     * @var array
     */
    protected $data = array ();

    /**
     * 是否是批量数据（如果是二维数组，传入true）
     *
     * @var unknown_type
     */
    protected $is_muti = false;

    /**
     * 是否启用新数组key编号
     *
     * @var unknown
     */
    protected $is_newkey = false;

    /**
     * 字段关系对应表($new_key=>$old_key_format)
     * $old_key_format :
     * $old_key //普通的字段更改
     * '&'.$old_key.'|'.$dc_obj_name（.'*'） //字段数据需要调用另外一个Dc来格式化，最后的*表示是否是批量替换
     * '^'.$old_key.'|'.$func_name //字段数据需要一个方法来处理
     *
     * @var array
     */
    protected $fields = array ();

    /**
     * 构造方法，传处基本数据
     *
     * @param array   $data    data
     * @param boolean $is_muti is muti or not
     *
     * @return void
     */
    public function __construct(array $data = array(), $is_muti = false) {
        $this->setData ( $data, $is_muti );
    }

    /**
     * 重新设置数据
     *
     * @param array   $data    data
     * @param boolean $is_muti is muti or not
     *
     * @return void
     */
    public function setData(array $data, $is_muti = false) {
        $this->data = $data;
        $this->is_muti = ( boolean ) $is_muti;
    }

    /**
     * 获取数据
     *
     */
    final public function fetch() {
        $this->_prepare ();
        $result = array ();
        if ($this->is_muti) {
            $i = 0;
            foreach ( $this->data as $key => $value ) {
                $data = $this->convertAlias ( $value );
                $data = $this->process ( $data, $value );
                if ($this->is_newkey) {
                    $key = $i;
                }
                $data !== null && $result[$key] = $data;
                if ($this->is_newkey) {
                    $i ++;
                }
            }
        } else {
            $result = $this->convertAlias ( $this->data );
            $result = $this->process ( $result, $this->data );
        }

        return $result;
    }

    /**
     * 获取转换后的字段数据
     *
     * @param array $data data
     *
     * @return array
     */
    final protected function convertAlias(array $data) {
        $result = array ();
        foreach ( $this->fields as $new_name => $value ) {
            if ($value) {
                // 单纯字段替换
                if (is_string ( $value )) {
                    $old_name = $extra_pro = null;
                    $flag = substr ( $value, 0, 1 );
                    if ($flag === '&' || $flag === '^') {
                        $value = substr ( $value, 1 );
                        list( $old_name, $extra_pro ) = explode ( '|', $value );
                    } else {
                        $old_name = $value;
                    }
                    //因字段值存在NULL,故注释掉isset判断.暂时未发现异常
                    //if (isset ( $data[$old_name] )) {
                    $result[$new_name] = $data[$old_name];
                    switch ($flag) {
                        case '&' : // 是另外一个转换的数据对象
                            if (substr ( $extra_pro, - 1 ) === '*') {
                                $multi = true;
                                $extra_pro = substr ( $extra_pro, 0, - 1 );
                            } else {
                                $multi = false;
                            }
                            if (class_exists ( $extra_pro )) {
                                $convert_child_obj = new $extra_pro ( $result[$new_name], $multi );
                                $result[$new_name] = $convert_child_obj->fetch ();
                                $convert_child_obj = null;
                            }
                            break;
                        case '^' : // 调用自定义的处理方法
                            if (method_exists ( $this, $extra_pro )) {
                                $result[$new_name] = $this->$extra_pro ( $result[$new_name] );
                            } else if (function_exists ( $extra_pro )) {
                                $result[$new_name] = eval ( "return \$extra_pro(\$result[\$new_name]);" );
                            }
                            break;
                    }
                    //}
                }
            }
        }

        return $result;
    }

    /**
     * 做准备工作（可继承后重写）
     *
     * @author baojun
     */
    protected function _prepare() {
    }

    /**
     * 核心处理方法(可继承后使用)
     *
     * @param array $data data
     *
     * @return array
     */
    protected function process(array $data) {
        return $data;
    }

    /**
     * 格式化时间
     *
     * @param int $timestamp timestamp
     *
     * @return string
     */
    static public function formatTime($timestamp) {
        return date ( self::DATE_FORMAT, $timestamp );
    }
}