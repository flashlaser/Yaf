<?php

/**
 * 分页类
 * 
 * @package Helper
 * @author  baojun <baojun4545@sina.com>
 */
class Helper_Pager {
    //长样式

    const STYLE_LONG = 'htmlLong';

    //短样式
    const STYLE_SHORT = 'htmlShort';

    //PAGE分页样式
    const STYLE_PAGE = 'htmlPage';

    //AJAX的HREF
    const AJAX_HREF = 'javascript:;';

    //直播list
    const STYLE_LIVE_LIST = 'htmlLiveList';

    const STYLE_ADMIN = 'htmlAdmin';

    public $total; //总条数
    public $pagetotal; //总页数
    public $curpage; //当前页
    public $pagenum; //每页数
    public $start; //第一页
    public $end; //最后一页
    public $url; //参考URL
    public $param = ''; //属性

    /**
     * 构造方法
     * 
     * @param int    $total    总页数
     * @param int    $pernum   每页多少项
     * @param string $url      固定URL格式
     * @param int    $max_page 最多多少页
     * 
     * @return void
     */

    public function __construct($total, $pernum, $url = false, $max_page = 50) {
        $this->total = $total;
        $this->pagenum = $pernum;
        $this->url = $url;
        $this->pagetotal = ceil($this->total / $this->pagenum);
        if ($this->pagetotal > $max_page) {
            $this->pagetotal = $max_page;
        }
        $this->curpage = (isset($_GET['p']) && $_GET['p'] >= 1) ? (int)$_GET['p'] : 1;
        if ($this->curpage > $max_page) {
            $this->curpage = $max_page;
        }
        $this->curpage = ($this->curpage > $this->pagetotal) ? $this->pagetotal : $this->curpage;
        $this->nextpage = ($this->curpage == $this->pagetotal) ? '' : ($this->curpage + 1);
        $this->prepage = ($this->curpage == 1) ? '' : ($this->curpage - 1);
    }

    /**
     * 取得SQL查询的LIMIT字符串内容
     * @return string
     */
    public function fetchFimit() {
        $this->start = ($this->curpage - 1) * $this->pagenum;
        $this->start < 0 && $this->start = 0;
        $result = "LIMIT {$this->start},{$this->pagenum}";
        return $result;
    }

    /**
     * 取得无分页查询的URL
     * @staticvar string $url
     * @return string
     */
    public function getUrl() {
        static $url = null;

        if ($url === null) {
            $url = Comm_Util::getServer('SCRIPT_URL') . '?' . $this->getQuery();
        }

        return $url;
    }

    /**
     * 获取当前GET查询字符串
     * @return string
     */
    protected function getQuery() {
        static $query = null;
        if ($query === null) {
            $url = Comm_Util::getCurrentUrl(false);
            $tmp = parse_url($url);
            isset($tmp['query']) && parse_str($tmp['query'], $query);

            if (isset($query['p'])) {
                unset($query['p']); //去掉当前GET中的p参数
            }

            //重新组合URL
            $query = empty($query) ? '' : http_build_query($query);
            $query && $query .= '&';
        }

        return $query;
    }

    /**
     * 生成链接里的属性
     * 
     * @param int $p 页码
     * 
     * @return string
     */
    public function makeAttr($p) {
        $url = $this->url ? $this->url : $this->getUrl() . "p={$p}";

        $result = " href=\"{$url}\" ";
        $this->param && $result .= $this->param;
        $result = str_replace('|page|', $p, $result);
        return $result;
    }

    /**
     * 生成HTML
     * 
     * @param int $style 样式 1:默认样式, 2:箭头翻页样式
     * 
     * @return string
     */
    public function html($style = self::STYLE_LONG) {
        $style = Comm_Argchecker::enum($style, 'enum,' . self::STYLE_LONG . ',' . self::STYLE_SHORT . ',' . self::STYLE_PAGE . ',' . self::STYLE_LIVE_LIST. ',' . self::STYLE_ADMIN, 3, 3);

        if (!$this->_process()) {
            return '';
        }

        $html = $this->$style();
        return $html;
    }

    /**
     * 计算相关分页数据
     *
     * @return boolean
     */
    protected function _process() {
        if ($this->pagetotal <= 1) {
            return false;
        }

        //计算开始页
        $this->start = $this->curpage - 2;
        $this->start = max(1, $this->start);

        //计算结束页
        $this->end = $this->start + 4;
        $this->curpage <= 3 && $this->end++;
        $this->end = ($this->end > 0 && $this->end < 5) ? 5 : $this->end;
        $this->end = min($this->end, $this->pagetotal);

        if ($this->end == $this->pagetotal) {
            $this->start > 1 && $this->start = max(1, $this->start - (3 - ($this->pagetotal - $this->curpage)));
        }
        $i = $this->start;
        return true;
    }

    /**
     * 长分页模式（水平样式）
     */
    protected function htmlLong() {
        $html = '<div class="W_pages W_pages_comment">';

        //上一页
        if ($this->curpage != 1) {
            $html .= '<a class="W_btn_a" ' . $this->makeAttr($this->prepage) . '><span>' . Comm_I18n::text('helper.pager.previous_page') . '</span></a>';
        }

        //第一页
        $i = $this->start;
        if ($i > 1) {
            //第一页
            $html .= '<a ' . $this->makeAttr(1) . '>1</a>';
            if ($i > 2) {
                $html .= '<span>…</span>';
            }
        }

        //循环显示中间部分
        for ($i; $i <= $this->end; $i++) {
            if ($i == $this->curpage) {
                $html .= '<a class="current">' . $i . '</a>';
            } else {
                $html .= '<a ' . $this->makeAttr($i) . '>' . $i . '</a>';
            }
        }

        //最后一页
        if ($this->end < $this->pagetotal) {
            if ($this->end + 1 < $this->pagetotal) {
                $html .= '<span>…</span>';
            }
            $html .= '<a ' . $this->makeAttr($this->pagetotal) . '>' . $this->pagetotal . '</a>';
        }

        //下一页
        if ($this->nextpage) {
            $html .= '<a class="W_btn_a" ' . $this->makeAttr($this->nextpage) . '><span>' . Comm_I18n::text('helper.pager.next_page') . '</span></a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 短分页样式（垂直下拉样式），
     */
    protected function htmlShort() {
        $max = 10;
        $this->end = min($this->end, $max);

        $html = '<div class="W_pages">';
        //上一页
        if ($this->curpage != 1) {
            $html .= '<a class="W_btn_a" ' . $this->makeAttr($this->prepage) . '><span>' . Comm_I18n::text('helper.pager.previous_page') . '</span></a>';
        }
        $html .= '<span class="list">';
        $html .= '<div style="display: none;" node-type="list-more">';

        for ($i = $this->end; $i > 0; $i--) {
            if ($i == $this->curpage) {
                $html .= '<a class="current" ' . $this->makeAttr($i) . '>' . Comm_I18n::dynamicText('helper.pager.page', $i) . '</a>';
            } else {
                $html .= '<a ' . $this->makeAttr($i) . '>' . Comm_I18n::dynamicText('helper.pager.page', $i) . '</a>';
            }
        }
        $html .= '</div>';
        $html .= '<a node-type="list-more-button" class="W_moredown" onclick="return false;" href="javascript:;"><span class="txt">' . Comm_I18n::dynamicText('helper.pager.page', '&nbsp;' . $this->curpage . '&nbsp;') . '</span><i class="W8_fonticons">v</i></a>';
        $html .= '</span>';
        //下一页
        if ($this->nextpage) {
            $html .= '<a class="W_btn_a" onclick="return false;" ' . $this->makeAttr($this->nextpage) . '><span>' . Comm_I18n::text('helper.pager.next_page') . '</span></a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * html标准分页输出
     * 
     * @return string
     */
    protected function htmlPage() {
        $html = '<div class="W_pages W_pages_comment">';

        //上一页
        if ($this->curpage != 1) {
            $html .= '<a class="W_btn_c" ' . $this->makeAttr($this->prepage) . '><span>' . Comm_I18n::text('helper.pager.previous_page') . '</span></a>';
        }

        //第一页
        $i = $this->start;
        if ($i > 1) {
            //第一页
            $html .= '<a class="page S_bg1" ' . $this->makeAttr(1) . '>1</a>';
            if ($i > 2) {
                $html .= '<span class="page S_txt2">…</span>';
            }
        }

        //循环显示中间部分
        for ($i; $i <= $this->end; $i++) {
            if ($i == $this->curpage) {
                $html .= '<a class="page S_txt1">' . $i . '</a>';
            } else {
                $html .= '<a class="page S_bg1" ' . $this->makeAttr($i) . '>' . $i . '</a>';
            }
        }

        //最后一页
        if ($this->end < $this->pagetotal) {
            if ($this->end + 1 < $this->pagetotal) {
                $html .= '<span class="page S_txt2">…</span>';
            }
            $html .= '<a class="page S_bg1" ' . $this->makeAttr($this->pagetotal) . '>' . $this->pagetotal . '</a>';
        }

        //下一页
        if ($this->nextpage) {
            $html .= '<a class="W_btn_c" ' . $this->makeAttr($this->nextpage) . '><span>' . Comm_I18n::text('helper.pager.next_page') . '</span></a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 后台分页html输出
     * 
     * @return string
     */
    protected function htmlAdmin() {
        $html = '<div class="pagin">';

        $from = ($this->curpage - 1) * $this->pagenum;
        $to   = $from + $this->pagenum;
        $from = max($from, 1);

        $html .= '<div class="message">';
		$html .= '共<i class="blue"> '.$this->total.' </i>条记录，当前显示第&nbsp;<i class="blue">'.$this->curpage.'&nbsp;</i>页';
		$html .= '</div>';

		$html .= '<ul class="paginList">';

        //上一页
        if ($this->curpage != 1) {
            $html .= '<li class="paginItem"><a ' . $this->makeAttr($this->prepage) . '><span class="pagepre"></span></a></li>';
        }

        //第一页
        $i = $this->start;
        if ($i > 1) {
            //第一页
            $html .= '<li class="paginItem"><a ' . $this->makeAttr(1) . '>1</a></li>';
            if ($i > 2) {
                $html .= '<li class="paginItem more"><a>…</a></li>';
            }
        }

        //循环显示中间部分
        for ($i; $i <= $this->end; $i++) {
            if ($i == $this->curpage) {
                $html .= '<li class="paginItem current"><a ' . $this->makeAttr($i) . '>' . $i . '</a></li>';
            } else {
                $html .= '<li class="paginItem"><a ' . $this->makeAttr($i) . '>' . $i . '</a></li>';
            }
        }

        //最后一页
        if ($this->end < $this->pagetotal) {
            if ($this->end + 1 < $this->pagetotal) {
                $html .= '<li><a class="paginItem more">…</a></li>';
            }
            $html .= '<li class="paginItem"><a ' . $this->makeAttr($this->pagetotal) . '>' . $this->pagetotal . '</a></li>';
        }

        //下一页
        if ($this->nextpage) {
            $html .= '<li class="paginItem"><a ' . $this->makeAttr($this->nextpage) . '><span class="pagenxt"></span></a></li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * 获取分页所需的数据
     *
     * @return array
     */
    public function pagedata() {
        if (!$this->_process()) {
            return array();
        } else {
            return get_object_vars($this);
        }
    }

    /**
     * 设置A标签属性
     * 
     * @param array $param 参数
     * 
     * @return Helper_Pager
     */
    public function setParam(array $param) {
        $this->param = '';
        foreach ($param as $key => $value) {
            $value = htmlspecialchars($value);
            $this->param .= "{$key}=\"{$value}\" ";
        }
        return $this;
    }

    /**
     * 魔术方法，读取内部属性
     * 
     * @param string $name 属性名称
     * 
     * @return  mixed
     */
    public function __get($name) {
        return $this->__isset($name) ? $this->$name : null;
    }

    /**
     * 魔术方法，判断内部属性是否存在
     * 
     * @param string $name 属性名称
     * 
     * @return  boolean
     */
    public function __isset($name) {
        return isset($this->$name);
    }

    /**
     * 魔术方法，转换为字符串
     */
    public function __toString() {
        return $this->html();
    }

}
