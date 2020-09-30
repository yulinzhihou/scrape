<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class KeywordsForBbs extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('keywords_bbs',['primary key'=>'id','auto_increment'=>true,'engine'=>'innodb','comment'=>'关键词论坛'])->addIndex('id');
        $table
            ->addColumn('keywords_id','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'关键字ID'])
            ->addColumn('search_engine_id','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'搜索引擎ID'])
            ->addColumn('url','string',['limit'=>255,'null'=>false,'default'=>'','comment'=>'论坛域名'])
            ->addColumn('username','string',['limit'=>50,'null'=>false,'default'=>'','comment'=>'论坛账号'])
            ->addColumn('password','string',['limit'=>32,'null'=>false,'default'=>'','comment'=>'论坛密码'])
            ->addColumn('article_url','string',['limit'=>255,'null'=>false,'default'=>'','comment'=>'发贴链接'])
            ->addColumn('comment','string',['limit'=>255,'null'=>false,'default'=>'','comment'=>'备注'])
            ->addColumn('createtime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'增加时间'])
            ->addColumn('updatetime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'更新时间'])
            ->addColumn('deletetime','integer',['limit'=>10,'signed'=>false,'null'=>true,'comment'=>'删除时间'])
            ->create();
    }
}
