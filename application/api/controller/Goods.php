<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\RolePublic as RolePublicModel;
use app\common\model\RoleDetail as RoleDetailModel;
use app\common\model\RoleSelling as RoleSellingModel;


/**
 * 示例接口
 */
class Goods extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['publicList','baseInfo','sellingList','roleInfo'];

    protected $rolePublicModel = null;
    protected $roleDetailModel = null;
    protected $roleSellingModel = null;
    // 角色基础数据字段
    protected $baseDataFields = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->rolePublicModel = new RolePublicModel();
        $this->roleDetailModel = new RoleDetailModel();
        $this->roleSellingModel = new RoleSellingModel();
        $this->baseDataFields =  ['id', 'role_public_id','role_selling_id','serial_num','name','level','sex','price','profession_id','max_hp','max_mp','str','spr','con','com',
            'dex','qian_neng','phy_attack','mag_attack','phy_def','mag_def','hit','miss','critical_att','critical_def','all_jiaozi','all_gold','all_yuanbao',
            'all_bind_yuanbao','all_tongbao','cold_att','cold_def','resist_cold_def','resist_cold_def_limit','fire_att','fire_def','resist_fire_def','resist_fire_def_limit',
            'light_att','light_def','resist_light_def','resist_light_def_limit','postion_att','postion_def','resist_postion_def','resist_postion_def_limit','xin_fa_score',
            'xiu_lian_score','upgrade_score','chuan_ci_jian_mian','chuan_ci_shang_hai','gem_num_3','gem_num_4','gem_num_5','gem_num_6','gem_num_7','gem_num_8','gem_num_9',
            'mining','plant','drug','cooking','pharmacy','fishing','status','remaintime','createtime','updatetime','deletetime',
        ];
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

    /**
     * 公示商品列表
     */
    public function publicList()
    {
        //验证接口token 是否有效
        $user = $this->auth->getUser();
        $params = $this->request->param();
        $result = $this->rolePublicModel->limit($params['page_num'])->page($params['page'])->select();
        $this->success('请求成功', $result);
    }

    /**
     * 人物基础数据
     */
    public function baseInfo()
    {
        $user = $this->auth->getUser();
        $params = $this->request->param();
        if ($params['type'] == "pub") {
            $result1 = $this->roleDetailModel->where(['serial_num'=>$params['serial_num'],'status'=>0])->field('special_item_info')->find()->toArray();
            $result1['base_data'] = $this->roleDetailModel->where('serial_num',$params['serial_num'])->field($this->baseDataFields)->find();
            $result1['api_image_url'] = 'http://image.cyg.changyou.com/tl/small/';
            $result1['api_version'] = '20140806';
            $result1['special_item_info'] = json_decode($result1['special_item_info'],true);
        } else {
            $result1 = $this->roleSellingModel->where(['serial_num'=>$params['serial_num'],'status'=>1])->field('special_item_info')->find()->toArray();
            $result1['base_data'] = $this->roleDetailModel->where('serial_num',$params['serial_num'])->field($this->baseDataFields)->find();
            $result1['api_image_url'] = 'http://image.cyg.changyou.com/tl/small/';
            $result1['api_version'] = '20140806';
            $result1['special_item_info'] = json_decode($result1['special_item_info'],true);
        }

        $this->success('请求成功',$result1);
    }


    /**
     * 交易区商品列表
     */
    public function sellingList()
    {
        $user = $this->auth->getUser();
        $params = $this->request->param();
        $result = $this->roleSellingModel->limit($params['page_num'])->page($params['page'])->select();
        $this->success('请求成功', $result);
    }


    /**
     * 公示区商品角色详情
     */
    public function roleInfo()
    {
        $user = $this->auth->getUser();

        $params = $this->request->param();
        if (isset($params['type']) && $params['type'] = 'pub') {
            //表示是公示区
            $result = $this->roleDetailModel->where(['serial_num'=>$params['serial_num'],'status'=>0])->field('base_info')->find()->toArray();
            $result['base_data'] = $this->roleDetailModel->where(['serial_num'=>$params['serial_num'],'status'=>0])->field($this->baseDataFields)->find()->toArray();
        } else  {
            //表示是交易区
            $result = $this->roleDetailModel->where(['serial_num'=>$params['serial_num'],'status'=>1])->field('base_info')->find()->toArray();
            $result['base_data'] = $this->roleDetailModel->where(['serial_num'=>$params['serial_num'],'status'=>1])->field($this->baseDataFields)->find()->toArray();
        }
        $result['base_info'] = json_decode($result['base_info'],true);
        $result['api_image_url'] = 'http://image.cyg.changyou.com/tl/small/';
        $result['api_version'] = '20140806';
        $this->success('请求成功',$result);
    }
}
