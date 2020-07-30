<?php

namespace app\admin\model\goods;

use think\Model;
use traits\model\SoftDelete;

class Rolepublic extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'role_public';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'remaintime_text'
    ];
    

    



    public function getRemaintimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['remaintime']) ? $data['remaintime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setRemaintimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
