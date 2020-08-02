<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class RoleDetailAddFields extends Migrator
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
        $table = $this->table('role_detail');
        $table
            ->addColumn('equip_score','integer',['signed'=>false,'after'=>'resist_postion_def_limit','limit'=>10,'null'=>false,'default'=>0,'comment'=>'心法评分'])
            ->addColumn('equip_score_hh','integer',['signed'=>false,'after'=>'equip_score','limit'=>10,'null'=>false,'default'=>0,'comment'=>'心法评分'])
            ->addColumn('title','string',['after'=>'equip_score_hh','limit'=>50,'default'=>'','null'=>false,'comment'=>'心法评分'])
            ->addColumn('gem_xiu_lian_score','integer',['signed'=>false,'after'=>'title','limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'null'=>false,'comment'=>'心法评分'])
            ->addColumn('gem_jin_jie_score','integer',['signed'=>false,'after'=>'gem_xiu_lian_score','limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'null'=>false,'comment'=>'心法评分'])
            ->addColumn('special_item_info','text',['null'=>false,'after'=>'talent_info','comment'=>'特色物品信息'])
            ->addColumn('special_pets_info','text',['null'=>false,'after'=>'special_item_info','comment'=>'特色宝宝信息'])
            ->addColumn('special_equips_info','text',['null'=>false,'after'=>'special_pets_info','comment'=>'特色装备信息'])
            ->update();
    }
}
