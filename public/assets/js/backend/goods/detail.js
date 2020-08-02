define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'goods/detail/index' + location.search,
                    add_url: 'goods/detail/add',
                    edit_url: 'goods/detail/edit',
                    del_url: 'goods/detail/del',
                    multi_url: 'goods/detail/multi',
                    table: 'role_detail',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'role_public_id', title: __('Role_public_id')},
                        {field: 'role_selling_id', title: __('Role_selling_id')},
                        {field: 'serial_num', title: __('Serial_num')},
                        {field: 'name', title: __('Name')},
                        {field: 'level', title: __('Level')},
                        {field: 'sex', title: __('Sex')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'profession_id', title: __('Profession_id')},
                        {field: 'max_hp', title: __('Max_hp')},
                        {field: 'max_mp', title: __('Max_mp')},
                        {field: 'str', title: __('Str')},
                        {field: 'spr', title: __('Spr')},
                        {field: 'con', title: __('Con')},
                        {field: 'com', title: __('Com')},
                        {field: 'dex', title: __('Dex')},
                        {field: 'qian_neng', title: __('Qian_neng')},
                        {field: 'phy_attack', title: __('Phy_attack')},
                        {field: 'mag_attack', title: __('Mag_attack')},
                        {field: 'phy_def', title: __('Phy_def')},
                        {field: 'mag_def', title: __('Mag_def')},
                        {field: 'hit', title: __('Hit')},
                        {field: 'miss', title: __('Miss')},
                        {field: 'critical_att', title: __('Critical_att')},
                        {field: 'critical_def', title: __('Critical_def')},
                        {field: 'all_jiaozi', title: __('All_jiaozi')},
                        {field: 'all_gold', title: __('All_gold')},
                        {field: 'all_yuanbao', title: __('All_yuanbao')},
                        {field: 'all_bind_yuanbao', title: __('All_bind_yuanbao')},
                        {field: 'all_tongbao', title: __('All_tongbao')},
                        {field: 'cold_att', title: __('Cold_att')},
                        {field: 'cold_def', title: __('Cold_def')},
                        {field: 'resist_cold_def', title: __('Resist_cold_def')},
                        {field: 'resist_cold_def_limit', title: __('Resist_cold_def_limit')},
                        {field: 'fire_att', title: __('Fire_att')},
                        {field: 'fire_def', title: __('Fire_def')},
                        {field: 'resist_fire_def', title: __('Resist_fire_def')},
                        {field: 'resist_fire_def_limit', title: __('Resist_fire_def_limit')},
                        {field: 'light_att', title: __('Light_att')},
                        {field: 'light_def', title: __('Light_def')},
                        {field: 'resist_light_def', title: __('Resist_light_def')},
                        {field: 'resist_light_def_limit', title: __('Resist_light_def_limit')},
                        {field: 'postion_att', title: __('Postion_att')},
                        {field: 'postion_def', title: __('Postion_def')},
                        {field: 'resist_postion_def', title: __('Resist_postion_def')},
                        {field: 'resist_postion_def_limit', title: __('Resist_postion_def_limit')},
                        {field: 'equip_score', title: __('Equip_score')},
                        {field: 'equip_score_hh', title: __('Equip_score_hh')},
                        {field: 'title', title: __('Title')},
                        {field: 'gem_xiu_lian_score', title: __('Gem_xiu_lian_score')},
                        {field: 'gem_jin_jie_score', title: __('Gem_jin_jie_score')},
                        {field: 'xin_fa_score', title: __('Xin_fa_score')},
                        {field: 'xiu_lian_score', title: __('Xiu_lian_score')},
                        {field: 'upgrade_score', title: __('Upgrade_score')},
                        {field: 'chuan_ci_jian_mian', title: __('Chuan_ci_jian_mian')},
                        {field: 'chuan_ci_shang_hai', title: __('Chuan_ci_shang_hai')},
                        {field: 'gem_num_3', title: __('Gem_num_3')},
                        {field: 'gem_num_4', title: __('Gem_num_4')},
                        {field: 'gem_num_5', title: __('Gem_num_5')},
                        {field: 'gem_num_6', title: __('Gem_num_6')},
                        {field: 'gem_num_7', title: __('Gem_num_7')},
                        {field: 'gem_num_8', title: __('Gem_num_8')},
                        {field: 'gem_num_9', title: __('Gem_num_9')},
                        {field: 'mining', title: __('Mining')},
                        {field: 'plant', title: __('Plant')},
                        {field: 'drug', title: __('Drug')},
                        {field: 'cooking', title: __('Cooking')},
                        {field: 'pharmacy', title: __('Pharmacy')},
                        {field: 'fishing', title: __('Fishing')},
                        {field: 'status', title: __('Status')},
                        {field: 'remaintime', title: __('Remaintime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'goods/detail/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'goods/detail/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'goods/detail/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});