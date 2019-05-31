<?php

/**
 * 创建初始的分表
 *
 * @package    Action
 * @author     baojun <baojun4545@sina.com>

 */
class ActionCtable extends Yaf_Action_Abstract {

    public function execute() {
        //仅允许调试环境下执行此脚本
        if (!Helper_Debug::isDebug()) {
            throw new Exception_Msg(100001, 'Can not run this script in no debug mode.');
        }

        $this->sql_pk();
    }

    //创建回复索引表
    protected function sql_pk() {
        //创建索引表
        $sql = <<<EOT
DROP TABLE IF EXISTS `%s`;
CREATE  TABLE IF NOT EXISTS %s (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tab_id` INT UNSIGNED NOT NULL COMMENT '标签ID' ,
  `mid` BIGINT UNSIGNED NOT NULL COMMENT '帖子ID' ,
  `create_time` INT UNSIGNED NOT NULL COMMENT '数据写入时间' ,
  INDEX `idx_tab_create` (`tab_id` ASC, `create_time` ASC) ,
  PRIMARY KEY (`id`)
)ENGINE = InnoDB CHARSET=utf8 COMMENT = 'PK回复表';

EOT;

        for ($i = 1; $i <= 256; $i++) {
            $table_name = Comm_Db::tSubHash('pk_reply_a', $i);
            $create_sql = sprintf($sql, $table_name, $table_name);
            Comm_Db::d(Comm_Db::DB_TEXT)->query($create_sql);
            echo $table_name . "\r\n";
        }
        
        for ($i = 1; $i <= 256; $i++) {
            $table_name = Comm_Db::tSubHash('pk_reply_b', $i);
            $create_sql = sprintf($sql, $table_name, $table_name);
            Comm_Db::d(Comm_Db::DB_TEXT)->query($create_sql);
            echo $table_name . "\r\n";
        }
        
        
        $sql = <<<EOT
        DROP TABLE IF EXISTS `%s`;
        CREATE  TABLE IF NOT EXISTS %s (
        `tab_id` INT UNSIGNED NOT NULL COMMENT '标签ID' ,
        `uid` BIGINT UNSIGNED NOT NULL COMMENT '用户ID' ,
        `side` TINYINT NOT NULL COMMENT '支持方（正1，反2）' ,
        `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间' ,
        PRIMARY KEY (`tab_id`, `uid`))
        ENGINE = InnoDB
        COMMENT = 'PK支持表';
EOT;
        
        for ($i = 1; $i <= 256; $i++) {
            $table_name = Comm_Db::tSubHash('pk_spt', $i);
            $create_sql = sprintf($sql, $table_name, $table_name);
            Comm_Db::d(Comm_Db::DB_TEXT)->query($create_sql);
            echo $table_name . "\r\n";
        }
    }


}
