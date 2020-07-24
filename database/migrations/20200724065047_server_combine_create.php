<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ServerCombineCreate extends Migrator
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
        $table = $this->table('server_combine',['primary key'=>'id','auto_increment'=>true,'engine'=>'innodb',
            'comment'=>'合区信息'])->addIndex('id');
        $table
            ->addColumn('name','text',['null'=>false,'comment'=>'服务器名'])
            ->addColumn('combinetime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'合区时间'])
            ->addColumn('createtime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'增加时间'])
            ->addColumn('updatetime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'更新时间'])
            ->addColumn('deletetime','integer',['limit'=>10,'signed'=>false,'null'=>true,'comment'=>'删除时间'])
            ->create();
    }
}
