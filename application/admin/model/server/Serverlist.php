<?php

namespace app\admin\model\server;

use think\Model;
use traits\model\SoftDelete;

class Serverlist extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'server_list';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'opentime_text'
    ];
    

    



    public function getOpentimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['opentime']) ? $data['opentime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setOpentimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
