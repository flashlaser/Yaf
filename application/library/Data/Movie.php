<?php

class Data_Movie extends Abstract_Data
{
    public function getList($title, $page, $size)
    {
        $start = ($page - 1) * $size;
        $param = array();
        if(!$title){
            $sql = "SELECT * FROM movie_list  limit $start,$size";
        }else{
            $title = "%$title%";
            $sql = "SELECT * FROM movie_list WHERE title like  ? limit $start,$size";
            $param = array($title);
        }
        $res = Comm_Db::d(Comm_Db::DB_BASIC)->query($sql, $param);
        $res = $res->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }

    public function get($id)
    {
        $sql = "SELECT * FROM movie_list WHERE id = ?";
        $res = Comm_Db::d(Comm_Db::DB_BASIC)->query($sql, array($id));
        $res = $res->fetchAll(PDO::FETCH_ASSOC);

        return isset($res[0]) ? $res[0] : array();
    }
}
