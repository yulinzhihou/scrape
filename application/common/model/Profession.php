<?php

namespace app\common\model;

use think\Model;

/**
 * 门派类
 */
class Profession extends Model
{

    protected $name = 'profession';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



}
