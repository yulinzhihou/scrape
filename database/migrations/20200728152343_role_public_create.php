<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class RolePublicCreate extends Migrator
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
        $table = $this->table('role_public',['primary key'=>'id','auto_increment'=>true,'engine'=>'innodb','comment'=>'公示角色'])->addIndex('id');
        $table
            ->addColumn('server_list_id','integer',['signed'=>false,'after'=>'id','limit'=>10,'default'=>0,'comment'=>'服务器ID'])
            ->addColumn('server_combine_id','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'合区ID'])
            ->addColumn('name','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'角色名'])
            ->addColumn('level','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'等级'])
            ->addColumn('url','string',['limit'=>255,'null'=>false,'default'=>'','comment'=>'角色链接'])
            ->addColumn('equip_point','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'装备评分'])
            ->addColumn('practice_point','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'修炼评分'])
            ->addColumn('advance_point','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'进阶评分'])
            ->addColumn('profession_id','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'门派ID'])
            ->addColumn('sex','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'性别,0=女，1=男'])
            ->addColumn('price','decimal',['precision'=>10,'scale'=>2,'signed'=>false,'null'=>false,'default'=>0.00,'comment'=>'价格'])
            ->addColumn('is_public','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'是否完成公示'])
            ->addColumn('remaintime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'剩余时间'])
            ->addColumn('createtime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'增加时间'])
            ->addColumn('updatetime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'更新时间'])
            ->addColumn('deletetime','integer',['limit'=>10,'signed'=>false,'null'=>true,'comment'=>'删除时间'])
            ->create();
    }
}
