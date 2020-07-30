<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class ServerCombineAddStatus extends Migrator
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
     *    addColumnA "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('server_combine');
        $table
            ->addColumn('status','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'after'=>'combinetime','null'=>false,'default'=>1,'comment'=>'状态，0=禁用，1=可用'])
            ->update();
    }
}
