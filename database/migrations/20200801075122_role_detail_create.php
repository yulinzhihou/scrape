<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class RoleDetailCreate extends Migrator
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
        $table = $this->table('role_detail',['primary key'=>'id','auto_increment'=>true,'engine'=>'innodb','comment'=>'角色详情'])->addIndex('id');
        $table
            ->addColumn('role_public_id','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'公示区ID'])
            ->addColumn('role_selling_id','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'交易区ID'])
            ->addColumn('serial_num','string',['limit'=>32,'null'=>false,'default'=>'','comment'=>'商品编码'])
            ->addColumn('name','string',['limit'=>20,'null'=>false,'default'=>'','comment'=>'角色名'])
            ->addColumn('level','string',['limit'=>MysqlAdapter::INT_TINY,'null'=>false,'default'=>'','comment'=>'等级'])
            ->addColumn('sex','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'性别,0=女，1=男'])
            ->addColumn('price','decimal',['precision'=>10,'scale'=>2,'signed'=>false,'null'=>false,'default'=>0.00,'comment'=>'价格'])
            ->addColumn('profession_id','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'门派ID'])
            ->addColumn('max_hp','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'血上限'])
            ->addColumn('max_mp','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'气上限'])
            ->addColumn('str','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_SMALL,'null'=>false,'default'=>0,'comment'=>'力量'])
            ->addColumn('spr','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_SMALL,'null'=>false,'default'=>0,'comment'=>'灵气'])
            ->addColumn('con','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_SMALL,'null'=>false,'default'=>0,'comment'=>'体力'])
            ->addColumn('com','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_SMALL,'null'=>false,'default'=>0,'comment'=>'定力'])
            ->addColumn('dex','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_SMALL,'null'=>false,'default'=>0,'comment'=>'身法'])
            ->addColumn('qian_neng','integer',['signed'=>false,'limit'=>MysqlAdapter::INT_TINY,'null'=>false,'default'=>0,'comment'=>'潜能'])
            ->addColumn('phy_attack','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'外功攻击'])
            ->addColumn('mag_attack','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'内功攻击'])
            ->addColumn('phy_def','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'外功防御'])
            ->addColumn('mag_def','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'内功防御'])
            ->addColumn('hit','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'命中'])
            ->addColumn('miss','integer',['signed'=>false,'limit'=>10,'null'=>false,'default'=>0,'comment'=>'闪避'])
            ->addColumn('critical_att','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'会心攻击'])
            ->addColumn('critical_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'会心防御'])
            ->addColumn('all_jiaozi','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'所有交子'])
            ->addColumn('all_gold','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'所有金币'])
            ->addColumn('all_yuanbao','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'所有元宝'])
            ->addColumn('all_bind_yuanbao','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'绑定元宝'])
            ->addColumn('all_tongbao','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'红利'])
            ->addColumn('cold_att','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'冰攻'])
            ->addColumn('cold_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'冰抗'])
            ->addColumn('resist_cold_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'减冰抗'])
            ->addColumn('resist_cold_def_limit','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'减冰抗下限'])
            ->addColumn('fire_att','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'火攻'])
            ->addColumn('fire_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'火抗'])
            ->addColumn('resist_fire_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'减火抗'])
            ->addColumn('resist_fire_def_limit','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'减火抗下限'])
            ->addColumn('light_att','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'玄攻'])
            ->addColumn('light_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'玄抗'])
            ->addColumn('resist_light_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'减玄抗'])
            ->addColumn('resist_light_def_limit','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'减玄抗下限'])
            ->addColumn('postion_att','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'毒攻'])
            ->addColumn('postion_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'毒抗'])
            ->addColumn('resist_postion_def','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_SMALL,'default'=>0,'comment'=>'减毒抗'])
            ->addColumn('resist_postion_def_limit','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'减毒抗下限'])
            ->addColumn('xin_fa_score','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'心法评分'])
            ->addColumn('xiu_lian_score','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'修炼评分'])
            ->addColumn('upgrade_score','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'进阶评分'])
            ->addColumn('chuan_ci_jian_mian','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'穿刺减免'])
            ->addColumn('chuan_ci_shang_hai','integer',['signed'=>false,'null'=>false,'limit'=>10,'default'=>0,'comment'=>'穿刺伤害'])
            ->addColumn('gem_num_3','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'3级宝石数量'])
            ->addColumn('gem_num_4','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'4级宝石数量'])
            ->addColumn('gem_num_5','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'5级宝石数量'])
            ->addColumn('gem_num_6','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'6级宝石数量'])
            ->addColumn('gem_num_7','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'7级宝石数量'])
            ->addColumn('gem_num_8','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'8级宝石数量'])
            ->addColumn('gem_num_9','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'9级宝石数量'])
            ->addColumn('mining','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'采矿'])
            ->addColumn('plant','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'种植'])
            ->addColumn('drug','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'采药'])
            ->addColumn('cooking','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'烹饪'])
            ->addColumn('pharmacy','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'制药'])
            ->addColumn('fishing','integer',['signed'=>false,'null'=>false,'limit'=>MysqlAdapter::INT_TINY,'default'=>0,'comment'=>'钓鱼'])
            ->addColumn('item_unbind_info','text',['null'=>false,'comment'=>'不绑定物品信息'])
            ->addColumn('equip_unbind_info','text',['null'=>false,'comment'=>'不绑定装备信息'])
            ->addColumn('pet_appendage_info','text',['null'=>false,'comment'=>'附体宝宝信息'])
            ->addColumn('base_info','text',['null'=>false,'comment'=>'基本页信息'])
            ->addColumn('skill_info','text',['null'=>false,'comment'=>'技能页信息'])
            ->addColumn('book_info','text',['null'=>false,'comment'=>'秘籍页信息'])
            ->addColumn('pet_info','text',['null'=>false,'comment'=>'珍兽页信息'])
            ->addColumn('bag_item_info','text',['null'=>false,'comment'=>'仓库物品信息'])
            ->addColumn('bag_equip_info','text',['null'=>false,'comment'=>'仓库装备信息'])
            ->addColumn('bag_pet_equip_info','text',['null'=>false,'comment'=>'仓库珍兽装备信息'])
            ->addColumn('bag_infants_info','text',['null'=>false,'comment'=>'仓库子女时装信息'])
            ->addColumn('cloth_info','text',['null'=>false,'comment'=>'时装外观信息'])
            ->addColumn('wuhun_info','text',['null'=>false,'comment'=>'武魂信息'])
            ->addColumn('xiulian_info','text',['null'=>false,'comment'=>'经脉修炼信息'])
            ->addColumn('zhenyuan_info','text',['null'=>false,'comment'=>'真元信息'])
            ->addColumn('infants_info','text',['null'=>false,'comment'=>'子女信息'])
            ->addColumn('shending_info','text',['null'=>false,'comment'=>'神鼎信息'])
            ->addColumn('hxy_info','text',['null'=>false,'comment'=>'侠印信息'])
            ->addColumn('fiveElements_info','text',['null'=>false,'comment'=>'宝鉴信息'])
            ->addColumn('talent_info','text',['null'=>false,'comment'=>'武意信息'])
            ->addColumn('status','integer',['limit'=>MysqlAdapter::INT_TINY,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'是否可用，0=正常，1=已下单，2=已下架'])
            ->addColumn('remaintime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'剩余时间'])
            ->addColumn('createtime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'增加时间'])
            ->addColumn('updatetime','integer',['limit'=>10,'signed'=>false,'null'=>false,'default'=>0,'comment'=>'更新时间'])
            ->addColumn('deletetime','integer',['limit'=>10,'signed'=>false,'null'=>true,'comment'=>'删除时间'])
            ->create();
    }
}
