<?php

namespace app\admin\model\server;

use think\Model;
use traits\model\SoftDelete;

class Combine extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'server_combine';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'combinetime_text'
    ];
    

    



    public function getCombinetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['combinetime']) ? $data['combinetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCombinetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
