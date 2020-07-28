<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ServerCombineAddFields extends Migrator
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
        $table = $this->table('server_combine');
        $table
            ->addColumn('server_list_id','integer',['signed'=>false,'after'=>'id','limit'=>10,'default'=>0,'comment'=>'服务器ID'])
            ->addColumn('world_id','integer',['limit'=>10,'signed'=>false,'null'=>false,'after'=>'server_list_id','default'=>0,'comment'=>'区ID'])
            ->addColumn('world_pid','integer',['limit'=>10,'signed'=>false,'null'=>false,'after'=>'world_id','default'=>0,'comment'=>'区父ID'])
            ->update();
    }
}
