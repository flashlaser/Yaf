<?php
/**
 * 首页
 *
 * @package Index
 * @author  baojun <zhangbaojun@yixia.com>
 */

class IndexController extends Abstract_Controller_Default
{
    protected $allow_no_login = true;
    private $_languageID = 1;

    /**
     * 是否获取当前用户信息
     *
     * @var boolean
     */
    protected $fetch_current_user = false;

    /**
     * 首页
     *
     * @return void
     */
    public function indexAction()
    {
//        phpinfo();die;
    }

    /**
     *
     */
    public function ajListAction()
    {
        $page  = $this->getRequest()->getRequest('page', 1);
        $size  = $this->getRequest()->getRequest('size', 10);
        $title = $this->getRequest()->getRequest('title', '');
        $data  = new Data_Movie();
        $res   = $data->getList($title, $page, $size);
        $this->getResponse()->setBody(json_encode(array('list' => $res)),'');

        return false;
    }

    public function infoAction()
    {
        $id   = $this->getRequest()->getRequest('id');
        $data = new Data_Movie();
        $res  = $data->get($id);
        echo '<!DOCTYPE HTML><html><body>';

        echo '<video src="' . $res['d_url'] . '" controls="controls"></video>';

        echo '</body ></html >';
        die();
    }
}
