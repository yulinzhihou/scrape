<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 门派模块类
 */
class Profession extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['getList'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [''];

    protected $model = null;

    public function _initialize()
    {
        $this->model = new \app\common\model\Profession();
        parent::_initialize();
    }

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function getList()
    {
        $user = $this->auth->getUser();
        $headImg = 'http://public.cyg.changyou.com/tl/themes/img/cartoon/';
        //验证接口token 是否有效
        $result = $this->model->column('name,key','id');
        foreach ($result as $key => &$value) {
            if ($value['key'] == 0 || $value['key'] == 6) {
                $value['head_img'] = $headImg.'pro1.jpg';
            } elseif ($value['key'] == 4 || $value['key'] == 7) {
                $value['head_img'] = $headImg.'pro2.jpg';
            } elseif ($value['key'] == 2 || $value['key'] == 3) {
                $value['head_img'] = $headImg.'pro3.jpg';
            } elseif ($value['key'] == 1 || $value['key'] == 8) {
                $value['head_img'] = $headImg.'pro4.jpg';
            } elseif ($value['key'] == 10 || $value['key'] == 5) {
                $value['head_img'] = $headImg.'pro5.jpg';
            } elseif ($value['key'] == 11) {
                $value['head_img'] = $headImg.'pro6.jpg';
            } elseif ($value['key'] == 12 ) {
                $value['head_img'] = $headImg.'pro12.jpg';
            } elseif ($value['key'] == 13 ) {
                $value['head_img'] = $headImg.'pro13.jpg';
            }
        }

        $this->success('请求成功', $result);
    }

}
