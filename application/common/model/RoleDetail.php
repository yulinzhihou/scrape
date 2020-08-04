<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

/**
 * 商品详情模型
 */
class RoleDetail extends Model
{

    use SoftDelete;
    protected $name = 'role_detail';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $deleteTime = 'deletetime';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}
