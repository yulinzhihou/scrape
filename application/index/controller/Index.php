<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use GuzzleHttp\Client;
use OviDigital\JsObjectToJson\JsConverter;
use QL\Ext\CurlMulti;
use QL\Ext\PhantomJs;
use QL\QueryList;
use app\admin\model\server\Serverlist as ServerListModel;
use app\admin\model\server\Combine as ServerCombineModel;
use app\admin\model\common\Profession as ProfessionModel;
use app\admin\model\goods\Rolepublic as RolePublicModel;
use app\admin\model\goods\Detail as RoleDetailModel;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $serverListModel = null;
    protected $serverCombineModel = null;
    protected $professionModel = null;
    protected $rolePublicModel = null;
    protected $roleDetailModel = null;


    //模拟请求客户端
    protected $GzClient = null;
    //CURL多纯种采集
    protected $MultiCurl = null;
    //模拟请求的请求数据
    protected $reqRes = null;
    //请求回来的html 结构体
    protected $html = null;
    //请求的uri
    protected $requestUri = 'http://tl.cyg.changyou.com';
    //图标请求接口
    protected $imgCygApiUrl = 'http://image.cyg.changyou.com/tl';
    //请求方式
    protected $method = 'GET';
    //请求代理设置
    protected $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';
    //
    protected $accEncoding = 'gzip, deflate, br';

    //动态获取JS 写入的信息
    protected $dynamicJs = null;

    //交易中心
    protected $selling = 'http://tl.cyg.changyou.com/goods/selling';
    //公示商品
    protected $protectGoods = 'http://tl.cyg.changyou.com/goods/public';
    //交易区
    protected $sellingUrl = 'http://tl.cyg.changyou.com/goods/selling?world_id={}&area_name={}&world_name={}&profession={}&have_chosen=profession*0&page_num={}#goodsTag';

    /**
     * 初始化方法
     * Index constructor.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->serverListModel = new ServerListModel();
        $this->serverCombineModel = new ServerCombineModel();
        $this->professionModel = new ProfessionModel();
        $this->rolePublicModel = new RolePublicModel();
        $this->roleDetailModel = new RoleDetailModel();

        $this->GzClient = new Client();
        $this->reqRes = $this->GzClient->request($this->method, $this->requestUri, [
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept-Encoding' => $this->accEncoding,
            ]
        ]);

        $this->html = (string)$this->reqRes->getBody();

        //动态获取实例
        $this->dynamicJs = QueryList::getInstance();
        // 安装时需要设置PhantomJS二进制文件路径,需要官方下载插件
//        $this->dynamicJs->use(PhantomJs::class,'/usr/local/bin/phantomjs');
        //or Custom function name
        $this->dynamicJs->use(PhantomJs::class, '/usr/local/bin/phantomjs', 'browser');

        //curl多线程采集
        $this->MultiCurl = QueryList::getInstance();
        $this->MultiCurl->use(CurlMulti::class);
        //or Custom function name
//        $this->MultiCurl->use(CurlMulti::class,'curlMulti');

    }

    /**
     * 合区信息
     */
    public function serverCombine()
    {
        //游戏区服信息
        $serverList = collection($this->serverListModel->where('world_pid','>',0)->select())->toArray();
        $worldIdToServerListId = $this->serverListModel->column('id','world_id');

        //循环找分区名与合区信息
        foreach ($serverList as $value) {
            //循环查找区对应的合区信息
            $nameStr = trim($this->dynamicJs->browser($this->selling.'?world_id='.$value['world_id'])->find('.server-info')->texts()->first());
            //爬取的数据与数据库里面的数据，有误差，如果原数据基础上增加了一个分区合区数据。那么，就需要对比了。将新数据与老数据对比。
            $isExitsName = $this->serverCombineModel->where(['name'=>$nameStr])->find();
//            dump($isExitsName);die;
            if (!$isExitsName) {
                //表示合区信息不存在，需要新写入。
                $serverCombineData = [
                    'id'   => null,
                    'server_list_id'    => $worldIdToServerListId[$value['world_id']],
                    'world_id'  => $value['world_id'],
                    'world_pid' => $value['world_pid'],
                    'name' => $nameStr,
                ];
                $this->serverCombineModel->isUpdate(false)->save($serverCombineData);
                $this->serverListModel->isUpdate(true)
                    ->where(['id'=>$value['id']])
                    ->update(['server_combine_id' => $this->serverCombineModel->id]);
            } else {
                //表示合区信息已经存在。
                $this->serverListModel->isUpdate(true)
                    ->where(['id'=>$value['id']])
                    ->update(['server_combine_id' => $isExitsName['id']]);
            }

        }

    }

    /**
     * 处理角色数据
     * @param $name string 角色名称  [天山 女 119级] ＂白富美ゝ
     * @return array
     */
    private function getRoleParseData($name)
    {
        $nameTemp = ltrim($name,'\[');
        $arr = explode(']',$nameTemp);
        $arr[0] = explode(' ',$arr[0]);
        $arr[1] = trim($arr[1]);
        //解析具体数据出来
        $professionData = $this->professionModel->column('name','id');
        return [
            'name'          => $arr[1],
            'profession_id' => array_search($arr[0][0],$professionData),
            'sex'           => $arr[0][1] == '女' ? 0 : 1,
            'level'         => intval($arr[0][2])
        ];

    }


    /**
     * 解析当前剩余时间为时间戳，
     * @comment 剩余时间：04天11小时56分钟
     * @comment 剩余时间：01小时01分钟05秒
     * @param $timeString
     * @return float|int
     */
    private function parseRemainTimeToTimestamps($timeString)
    {
        //分两种格式。剩余时间：04天11小时56分钟
        $timezoneStamps = 8*3600;

        if (strrpos($timeString,'天') !== false) {
            //表示还有几天的那种情况

            $restHourMinuteSecond = explode('天',$timeString);
            $dayTimeStamps = intval($restHourMinuteSecond[0]) * 86400;

            $restMinutesSeconds = explode('小时',$restHourMinuteSecond[1]);
            $hourTimeStamps = intval($restMinutesSeconds[0]) * 3600;

            $restSeconds = explode('分钟',$restMinutesSeconds[1]);
            $minutesTimeStamps = intval($restSeconds[0]) * 60;
            //天的时间戳+小时+分钟+0 秒的时间戳。误差范围在1 分钟之内 、
            return time()+$dayTimeStamps+$hourTimeStamps+$minutesTimeStamps+$timezoneStamps;
        } else {
            //表示不足一天的情况
            $restHourMinuteSecond = explode('小时',$timeString);
            $hourTimeStamps = intval($restHourMinuteSecond[0]) * 3600;

            $restMinutesSeconds = explode('分钟',$restHourMinuteSecond[1]);
            $minutesTimeStamps = intval($restMinutesSeconds[0]) * 60;

            $restSeconds = explode('秒',$restMinutesSeconds[1]);
            $secondsTimeStamps = intval($restSeconds[0]);
            //天的时间戳+小时+分钟+0 秒的时间戳。误差范围在1 分钟之内 、
            return time()+$hourTimeStamps+$minutesTimeStamps+$secondsTimeStamps+$timezoneStamps;
        }
    }

    /**
     * 游戏区对应预售区上架角色
     * @param QueryList $queryList
     */
    public function gamePublicRoleList(QueryList $queryList)
    {
        //查询全服公示产品
        $this->requestUri = 'http://tl.cyg.changyou.com/goods/public?world_id=0&order_by=remaintime-desc#goodsTag';
        $this->reqRes = $this->GzClient->request($this->method, $this->requestUri, [
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept-Encoding' => $this->accEncoding,
            ]
        ]);

        $this->html = (string)$this->reqRes->getBody();
        //取分页数据
        $text = $queryList->html($this->html)->find('.ui-pagination a')->texts()->toArray();
        $url = $queryList->html($this->html)->find('.ui-pagination a')->attrs('href')->toArray();
        //组合成一个新数组
        $newData = array_combine($text,$url);
        //查数组最大长度
        if (count($newData) >= 4){
            //表示至少有2 页，否则不会出现翻页
            //去掉首尾
            unset($newData['上一页']);
            unset($newData['下一页']);
            $maxPage = array_keys($newData);
        } else {
            //表示没有分页
            $maxPage = 0;
        }
        //获取最大页码值
        $maxPage = $maxPage[count($maxPage)-1];
        /**
         * 原理，爬虫需要记录当前页码的生产时间和上架时间。
         * 页码每时每分会增加，如何判断页码的不断层
         * 账号会随着时间的，需要心跳链接进行时间核实
         * 爬取剩余时间升序的列表，
         * 根据角色的区服，存入对应的ID 值
         *
         * http://tl.cyg.changyou.com/goods/public?world_id=0&order_by=remaintime-desc#goodsTag
         */

        //获取当前列表页面用户数据
        $currentListRoleData = $queryList->html($this->html)->find('.jGoodsList dt')->texts();
        //角色详情URL
        $currentListRoleUrl = $queryList->html($this->html)->find('.jGoodsList dt a')->attrs('href');
        //角色修炼评分，进阶评分，装备评分
        $currentListRoleDetail = $queryList->html($this->html)->find('dd.detail span')->texts();
        //角色价格
        $currentListRolePrice = $queryList->html($this->html)->find('.jGoodsList .price')->texts();
        //角色剩余时间
        $currentListRoleTime = $queryList->html($this->html)->find('.jGoodsList .time')->texts();
        //分区信息 html 数据
        $currentListRoleZone = $queryList->rules(['content' => ['.server-and-time','html']])->html($this->html)->query()->getData();
        //解析HTML 字符串为DOM对象，然后取出自定义属性的值
        $htmlDom = new \DOMDocument('1.0','UTF8');
        $htmlDom->preserveWhiteSpace = FALSE;
        @$htmlDom->loadHTML($currentListRoleZone['content']);
        //分区信息ID 对照表
        $serverListData = $this->serverListModel->column('world_id','id');

        //构建当前列表用户数据结构
        $currentListRoleDataAttr = [];

        foreach ($currentListRoleData as $key => $value) {
            $span = $htmlDom->getElementsByTagName('span')->item($key);
            $worldId = $span->attributes->item(1)->nodeValue;
            $roleInfo = $this->getRoleParseData($currentListRoleData[$key]);
            $currentListRoleDataAttr[] = [
                'serial_num'        => trim(explode('serial_num=',$currentListRoleUrl[$key])[1]),
                'server_list_id'    => array_search($worldId,$serverListData),
                'server_combine_id' => $this->serverListModel->where('world_id',$worldId)->find()['server_combine_id'],
                'name'  => $roleInfo['name'],
                'sex'   => $roleInfo['sex'],
                'level' => $roleInfo['level'],
                'url'   => $currentListRoleUrl[$key],
                'equip_point'   => explode('装备评分：',$currentListRoleDetail[$key*3])[1],
                'practice_point'=> explode('修炼评分：',$currentListRoleDetail[$key*3+1])[1],
                'advance_point' => explode('进阶评分：',$currentListRoleDetail[$key*3+2])[1],
                'profession_id' => $roleInfo['profession_id'],
                'is_public'     => 1,
                'price'         => substr(trim($currentListRolePrice[$key]),3),
                'remaintime'    => $this->parseRemainTimeToTimestamps(explode('剩余时间：',$currentListRoleTime[$key])[1]),
            ];
        }

        $this->rolePublicModel->isUpdate(false)->saveAll($currentListRoleDataAttr);

    }


    /**
     * 预售区角色详情页
     * @param QueryList $queryList
     * @param JsConverter $jsConverter
     */
    public function gamePublicRoleDetail(QueryList $queryList,JsConverter $jsConverter)
    {
        $roleListData = $this->rolePublicModel->column('url,price,server_list_id,server_combine_id,remaintime','id');
        foreach ($roleListData as $roleListDatum) {
            //查询全服公示产品
            $this->requestUri = $roleListDatum['url'];
//            $this->requestUri = 'http://tl.cyg.changyou.com/goods/char_detail?serial_num=20200728856352762';
            $this->reqRes = $this->GzClient->request($this->method, $this->requestUri, [
                'headers' => [
                    'User-Agent' => $this->userAgent,
                    'Accept-Encoding' => $this->accEncoding,
                ]
            ]);

            $this->html = (string)$this->reqRes->getBody();
            //爬取角色页数据
            $roleInfoData = $queryList->html($this->html)->find('#tlTRLevelTpl')->next()->texts();
            $roleInfoData = explode(';',trim(explode('=',$roleInfoData[0])[2]));
            $roleInfoDataJson = $jsConverter->convertToJson(trim($roleInfoData[0]));
            //将数据全部解析出来
            $roleInfoDataArr = json_decode($roleInfoDataJson,true);
//            dump($roleInfoDataArr);die;
            //构建数据库数据结构
            $roleInfo = $this->roleDetailGeneralData($roleInfoDataArr,$roleListDatum,0);
            //前 19 件装备信息
            $nineteenEquips = $this->roleDetailIconFix($roleInfoDataArr['items'],'equip','gemAttr');
            foreach ($nineteenEquips as $key => $value) {
                if ($key > 18) {
                    unset($nineteenEquips[$key]);
                }
            }
            //整合角色首页基本信息入库
            $roleInfo['base_info'] = json_encode($nineteenEquips);
            //技能页面
            $xinFaData = $this->roleDetailIconFix($roleInfoDataArr,'xinFaList');
            $shengHuoSkillData = $this->roleDetailIconFix($roleInfoDataArr,'shengHuoSkillList');
            $roleInfo['skill_info'] = json_encode(['menpai'=>$xinFaData,'shenghuo'=>$shengHuoSkillData]);
            //秘籍页面
            $bookData = $this->roleDetailIconFix($roleInfoDataArr['miJi'],'miJiInfo','duanShi');
            $roleInfo['book_info'] = json_encode($bookData);
            //珍兽页面
            $petsData = $this->roleDetailIconFix($roleInfoDataArr,'petList','petSkillList');
            $roleInfo['pet_info'] = json_encode($petsData);
            //仓库页面
            $bankItemData = $this->roleDetailIconFix($roleInfoDataArr['items'],'commonItem');
            $bankNewItemData = $specialItemInfo =  [];
            foreach ($bankItemData as $key => $value) {
                if ($value['isBind'] === 1) {
                    array_push($bankNewItemData,$value);
                } else {
                    array_push($specialItemInfo,$value);
                }
            }
            //仓库物品。
            $roleInfo['bag_item_info'] = json_encode($bankItemData);
            $roleInfo['special_item_info'] = json_encode($specialItemInfo);
            //仓库装备
            $bankEquipData = $this->roleDetailIconFix($roleInfoDataArr['items'],'equip');
            $bankNewEquipData = $specialEquipInfo = [];
            foreach ($bankEquipData as $key => $value) {
                if ($value['isBind'] === 1) {
                    array_push($bankNewEquipData,$value);
                } else {
                    array_push($specialEquipInfo,$value);
                }
            }
            $roleInfo['bag_equip_info'] = json_encode($bankEquipData);
            $roleInfo['special_equips_info'] = json_encode($specialEquipInfo);
            //仓库宝宝装备
            $bankPetsEquipData = $this->roleDetailIconFix($roleInfoDataArr['items'],'petEquip');
            $bankNewPetsEquipData = $specialPetsEquipInfo = [];
            foreach ($bankPetsEquipData as $key => $value) {
                if ($value['isBind'] == 1) {
                    array_push($bankNewPetsEquipData,$value);
                } else {
                    array_push($specialPetsEquipInfo,$value);
                }
            }
            $roleInfo['bag_pet_equip_info'] = json_encode($bankPetsEquipData);
            $roleInfo['special_pets_info'] = json_encode($specialPetsEquipInfo);
            //仓库子女时装
            $bankInfantsData = $this->roleDetailIconFix($roleInfoDataArr['items'],'card');
            $roleInfo['bag_infants_info'] = json_encode($bankInfantsData);
            //外观页面数据----时装，幻饰武器都属于仓库装备数据里面
            $appearanceClothData = $appearanceWeaponData = [];
            foreach ($bankEquipData as $bankEquipDatum) {
                if ($bankEquipDatum['typeDesc'] == '时装') {
                    array_push($appearanceClothData,$bankEquipDatum);
                }

                if ($bankEquipDatum['typeDesc'] == '幻饰武器') {
                    array_push($appearanceWeaponData,$bankEquipDatum);
                }
            }
            $roleInfo['cloth_info'] = json_encode(['cloth'=>$appearanceClothData,'weapon'=>$appearanceWeaponData]);
            //武魂页面
            if (isset($nineteenEquips[15])) {
                $roleInfo['wuhun_info'] = json_encode($nineteenEquips[15]);
            } else {
                $roleInfo['wuhun_info'] = '';
            }
            //修炼|经脉页面数据
            $roleXiuLianData = $this->roleDetailIconFix($roleInfoDataArr,'xiuLianList','xiuLianXiangList');
            $roleJingMaiData = $this->roleDetailIconFix($roleInfoDataArr,'miFaList');
//            dump($roleInfoDataArr);die;

            $roleInfo['xiulian_info'] = json_encode(['xiulian'=>$roleXiuLianData,'jingmai'=>$roleJingMaiData]);
            //真元数据页面
            $roleZYData = $this->roleDetailIconFix($roleInfoDataArr,'zhenYuanList');
            $roleInfo['zhenyuan_info'] = json_encode($roleZYData);
            //子女页面数据
//            $roleInfants = $this->roleDetailIconFix($roleInfoDataArr['infants'],0,'cardList');
            if (isset($roleInfoDataArr['infants'][0])) {
                $roleInfants = $roleInfoDataArr['infants'][0];

                foreach ($roleInfants['cardList'] as $key => &$value) {
                    if ($value['icon'] != '') {
                        $arr = explode('_',$value['icon']);
                        if (count($arr) == 2) {
                            $value['icon'] = ['image'=>$arr[0].'.jpg','index'=>$arr[1]];
                        } else {
                            $value['icon'] = ['image'=>$arr[0].'_'.$arr[1].'.jpg','index'=>$arr[2]];
                        }
                    }
                }
                foreach ($roleInfants['skills'] as $key => &$value) {
                    if ($value['iconName'] != '') {
                        $arr = explode('_',$value['iconName']);
                        if (count($arr) == 2) {
                            $value['iconName'] = ['image'=>$arr[0].'.jpg','index'=>$arr[1]];
                        } else {
                            $value['iconName'] = ['image'=>$arr[0].'_'.$arr[1].'.jpg','index'=>$arr[2]];
                        }
                    }
                }

                $roleInfo['infants_info'] = json_encode($roleInfants);
            } else {
                $roleInfo['infants_info'] = '';
            }

            //神鼎页面数据
            $roleInfo['shending_info'] = json_encode($roleInfoDataArr['shenDing']);
            //侠印页面数据
            if (isset($nineteenEquips[18])) {
                $roleInfo['hxy_info'] = json_encode($nineteenEquips[18]);
            } else {
                $roleInfo['hxy_info'] = '';
            }
            //宝鉴页面数据
            $roleInfo['fiveElements_info'] = json_encode($roleInfoDataArr['fiveElements']);
            //武意页面数据
            $roleInfo['talent_info'] = json_encode(['martialDB'=>$roleInfoDataArr['martialDB'],'talentDB'=>$roleInfoDataArr['talentDB']]);

            //直接存库
            $serialNum = explode('serial_num=',$roleListDatum['url'])[1];
            $result = $this->roleDetailModel->where('serial_num',$serialNum)->find();
            if (!$result) {
                $this->roleDetailModel->isUpdate(false)->save($roleInfo);
            }
        }

    }

    /**
     * 爬取服务器大区和服务器列表
     * @param QueryList $queryList  爬虫软件
     * @param JsConverter $jsConverter
     */
    public function serverList(QueryList $queryList,JsConverter $jsConverter)
    {
        //服务器-分区列表
        $script = $queryList->html($this->html)->find('script')->texts()[4];
        $script = explode(';',trim(explode('=',$script)[1]))[0];
        $json = $jsConverter->convertToJson($script);
        $server = json_decode($json,true);
        $serverList = $this->serverListModel->column('name','id');
        //构建数据格式
        $newWorldData = [];//大区数据
        $newServerData = [];//服务器数据
        foreach ($server as $value) {
            if (!empty($serverList) && array_search($value['name'],$serverList) == false) {
                //表示存在老数据，需要判断
                if (is_array($value)) {
                    $newWorldData[] = [
                        'world_id'  =>  $value['id'],
                        'name'      =>  $value['name'],
                        'world_pid' =>  0,
                        'server_combine_id' => 0,
                    ];
                }
                foreach ($value['server'] as $k1 => $v1) {
                    $newServerData[] = [
                        'world_id'  =>  $v1['id'],
                        'name'      =>  $v1['name'],
                        'world_pid' =>  $value['id'],
                        'server_combine_id' => $this->getServerCombineId($v1['name'])
                    ];

                }
            } else {
                //全新爬取
                if (is_array($value)) {
                    $newWorldData[] = [
                        'world_id'  =>  $value['id'],
                        'name'      =>  $value['name'],
                        'world_pid' =>  0,
                    ];
                }
                foreach ($value['server'] as $k1 => $v1) {
                    $newServerData[] = [
                        'world_id'  =>  $v1['id'],
                        'name'      =>  $v1['name'],
                        'world_pid' =>  $value['id'],
                    ];

                }
            }

        }

        //入库
        //$this->serverListModel->isUpdate(false)->saveAll($newWorldData);

    }


    /**
     * 动态获取页面
     */
    public function dynamicGet()
    {


    }


    /**
     * 门派信息入库
     * @param QueryList $queryList
     */
    public function getProfession(QueryList $queryList)
    {
        //门派数据
        $menPaiName = $queryList->html($this->html)->find('.group-detail-item>a[data-key="profession"]')->attrs('data-value')->toArray();
        $menPaiNum = $queryList->html($this->html)->find('.group-detail-item>a[data-key="profession"]')->texts()->toArray();
        $professionData = [];
        foreach ($menPaiNum as $key => $value) {
            $professionData[] = [
                'id'    => null,
                'key'   => $menPaiName[$key],
                'name'  => $value
            ];
        }

        if (!$this->professionModel->find(1)) {
            $this->professionModel->isUpdate(false)->saveAll($professionData);
        } else {
            dump('已经入库了！');
        }

    }


    /**
     * demo 方法
     * @param QueryList $queryList
     * @param JsConverter $jsConverter
     */
    public function index(QueryList $queryList,JsConverter $jsConverter)
    {
        $client = new Client();
        $res = $client->request('GET', 'http://tl.cyg.changyou.com', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ]);

        $html = (string)$res->getBody();



        //门派数据
//        $menPaiName = $queryList->html($html)->find('.group-detail-item>a[data-key="profession"]')->attrs('data-value')->toArray();
//        $menPaiNum = $queryList->html($html)->find('.group-detail-item>a[data-key="profession"]')->texts()->toArray();
//        $menPai = array_combine($menPaiName,$menPaiNum);
//        dump($menPai);die;

        //等级范围
//        $levelNum = $queryList->html($html)->find('.group-detail-item>a[data-key="level"]')->attrs('data-value')
//            ->toArray();
//        $levelName = $queryList->html($html)->find('.group-detail-item>a[data-key="level"]')->attrs('data-value')
//            ->toArray();
//        $level = array_combine($levelNum,$levelName);

        //取分页数据
//        $text = $queryList->html($html)->find('.ui-pagination a')->texts()->toArray();
//        $url = $queryList->html($html)->find('.ui-pagination a')->attrs('href')->toArray();
//        //组合成一个新数组
//        $newData = array_combine($text,$url);
//        //查数组最大长度
//        if (count($newData) >= 4){
//            //表示至少有2 页，否则不会出现翻页
//            //去掉首尾
//            unset($newData['上一页']);
//            unset($newData['下一页']);
//            $maxPage = array_keys($newData);
//        } else {
//            //表示没有分页
//            $maxPage = 0;
//        }
//        dump($maxPage);die;


        //新鲜上架的角色
        $newRole = $queryList->html($html)->find('.list-new-good span')->texts()->toArray();
        $newRoleUrl= $queryList->html($html)->find('.list-new-good span>a')->attrs('href')->toArray();
        $newRoleData = [];
        foreach ($newRole as $key => $value) {
            if ($key % 3 == 0 && $key != 0 && $key / 3 != 1) {
                $newRoleData[] = [
                    'role'      => $newRole[$key-3],
                    'mei_pai'   => $newRole[$key-2],
                    'up_time'   => $newRole[$key-1],
                    'url'       => $newRoleUrl[$key/3-2],// 0 6/3 -2   1  9/3 -2
                ];
            }
        }

        //获取当前列表页面用户数据
        $currentListRoleData = $queryList->html($html)->find('.jGoodsList dt')->texts();
        $currentListRoleUrl = $queryList->html($html)->find('.jGoodsList dt a')->attrs('href');
        $currentListRoleDetail = $queryList->html($html)->find('dd.detail span')->texts();
        $currentListRolePrice = $queryList->html($html)->find('.jGoodsList .price')->texts();
        $currentListRoleTime = $queryList->html($html)->find('.jGoodsList .time')->texts();
        $currentListRoleServer = $queryList->html($html)->find('.jGoodsList .time')->texts();
        $currentListRoleZone = $queryList->html($html)->find('.server-info')->attrs('data-wordId');

        //构建当前列表用户数据结构
        $currentListRoleDataAttr = [];

        foreach ($currentListRoleData as $key => $value) {
            $currentListRoleDataAttr[] = [
                'name'  => $currentListRoleData[$key],
                'url'   => $currentListRoleUrl[$key],
                'server'   => '',
                'zone'      => '',
                'attr'  => $currentListRoleDetail[$key*3].' | ' . $currentListRoleDetail[$key*3+1] . ' | ' .
                    $currentListRoleDetail[$key*3+2],
                'price' => substr(trim($currentListRolePrice[$key]),3),
                'rest_time' =>$currentListRoleTime[$key],
            ];
        }


        dump($newRoleData);
        dump($currentListRoleData);
        dump($currentListRoleUrl);
        dump($currentListRoleDetail);
        dump($currentListRolePrice);
        dump($currentListRoleZone);
        dump($currentListRoleDataAttr);

    }


    /**
     * 采集角色主页面
     * @param QueryList $queryList
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function role(QueryList $queryList)
    {
        $client = new Client();
        $res = $client->request('GET', 'tl.cyg.changyou.com/goods/char_detail?serial_num=202007072046530591', [
//            'query' => ['wd' => 'QueryList'],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ]);

        $html = (string)$res->getBody();

        //力量 灵气 定力
        $attr = $queryList->html($html)->find('.row2')->texts();
        dump($attr);
        dd($html);
    }


    /**
     * 通过爬取的数据找到合区ID
     * @param $data
     * @return int|string
     */
    private function getServerCombineId($data)
    {
        $serverCombineData = $this->serverCombineModel->column('name','id');
        foreach ($serverCombineData as $key => $serverCombineDatum) {
            if (strrpos($data,$serverCombineDatum['name']) !== false) {
                //表示存在合区编号
                return $key;
            } else {
                return $key+1;
            }
        }

        return 0;
    }


    /**
     * 处理角色详情页角色基础数据
     * @param $roleInfoDataArr  array   角色数据
     * @param $roleListDatum    array   公示区角色列表
     * @param $roleSellingId    integer 交易区ID
     * @return string[]
     */
    private function roleDetailGeneralData($roleInfoDataArr,$roleListDatum,$roleSellingId)
    {
        $serialNum = explode('serial_num=',$roleListDatum['url'])[1];

        return [
            'id'                =>  null,
            "role_public_id"    =>  $roleListDatum['id'],
            "role_selling_id"   =>  $roleSellingId,
            "serial_num"        =>  $serialNum,
            "name"              =>  $roleInfoDataArr['charName'],
            "level"             =>  $roleInfoDataArr['level'],
            "sex"               =>  $roleInfoDataArr['sex'],
            "price"             =>  $roleListDatum['price'],
            "profession_id"     =>  $roleInfoDataArr['menpai'],
            "max_hp"            =>  $roleInfoDataArr['maxHp'],
            "max_mp"            =>  $roleInfoDataArr['maxMp'],
            "str"               =>  $roleInfoDataArr['str'],
            "spr"               =>  $roleInfoDataArr['spr'],
            "con"               =>  $roleInfoDataArr['con'],
            "com"               =>  $roleInfoDataArr['com'],
            "dex"               =>  $roleInfoDataArr['dex'],
            "qian_neng"         =>  $roleInfoDataArr['qianNeng'],
            "phy_attack"        =>  $roleInfoDataArr['phyAttack'],
            "mag_attack"        =>  $roleInfoDataArr['magAttack'],
            "phy_def"           =>  $roleInfoDataArr['phyDef'],
            "mag_def"           =>  $roleInfoDataArr['magDef'],
            "hit"               =>  $roleInfoDataArr['hit'],
            "miss"              =>  $roleInfoDataArr['miss'],
            "critical_att"      =>  $roleInfoDataArr['criticalAtt'],
            "critical_def"      =>  $roleInfoDataArr['criticalDef'],
            "all_jiaozi"        =>  $roleInfoDataArr['bkBgBaseInfo']['jiaoZi'],
            "all_gold"          =>  $roleInfoDataArr['bkBgBaseInfo']['gold'] + $roleInfoDataArr['bkBgBaseInfo']['bankGold'],
            "all_yuanbao"       =>  $roleInfoDataArr['bkBgBaseInfo']['yuanBao'],
            "all_bind_yuanbao"  =>  $roleInfoDataArr['bkBgBaseInfo']['bindYuanBao'],
            "all_tongbao"       =>  $roleInfoDataArr['bkBgBaseInfo']['tongBao'],
            "cold_att"          =>  $roleInfoDataArr['coldAtt'],
            "cold_def"          =>  $roleInfoDataArr['coldDef'],
            "resist_cold_def"   =>  $roleInfoDataArr['resistColdDef'],
            "resist_cold_def_limit" =>  $roleInfoDataArr['resistColdDefLimit'],
            "fire_att"          =>  $roleInfoDataArr['fireAtt'],
            "fire_def"          =>  $roleInfoDataArr['fireDef'],
            "resist_fire_def"   =>  $roleInfoDataArr['resistFireDef'],
            "resist_fire_def_limit" =>  $roleInfoDataArr['resistFireDefLimit'],
            "light_att"         =>  $roleInfoDataArr['lightAtt'],
            "light_def"         =>  $roleInfoDataArr['lightDef'],
            "resist_light_def"  =>  $roleInfoDataArr['resistLightDef'],
            "resist_light_def_limit"    =>  $roleInfoDataArr['resistLightDefLimit'],
            "postion_att"       =>  $roleInfoDataArr['postionAtt'],
            "postion_def"       =>  $roleInfoDataArr['postionDef'],
            "resist_postion_def"=>  $roleInfoDataArr['resistPostionDef'],
            "resist_postion_def_limit"  =>  $roleInfoDataArr['resistPostionDefLimit'],
            "xin_fa_score"      =>  $roleInfoDataArr['xinFaScore'],
            "xiu_lian_score"    =>  $roleInfoDataArr['xiuLianScore'],
            "upgrade_score"     =>  $roleInfoDataArr['upgradeScore'],
            "chuan_ci_jian_mian"=>  $roleInfoDataArr['chuanCiJianMian'],
            "chuan_ci_shang_hai"=>  $roleInfoDataArr['chuanCiShangHai'],
            "gem_num_3"         =>  $roleInfoDataArr['gemNum3'],
            "gem_num_4"         =>  $roleInfoDataArr['gemNum4'],
            "gem_num_5"         =>  $roleInfoDataArr['gemNum5'],
            "gem_num_6"         =>  $roleInfoDataArr['gemNum6'],
            "gem_num_7"         =>  $roleInfoDataArr['gemNum7'],
            "gem_num_8"         =>  $roleInfoDataArr['gemNum8'],
            "gem_num_9"         =>  $roleInfoDataArr['gemNum9'],
            "mining"            =>  $roleInfoDataArr['shengHuoSkillList'][0]['level'],
            "plant"             =>  $roleInfoDataArr['shengHuoSkillList'][2]['level'],
            "drug"              =>  $roleInfoDataArr['shengHuoSkillList'][1]['level'],
            "cooking"           =>  $roleInfoDataArr['shengHuoSkillList'][3]['level'],
            "pharmacy"          =>  $roleInfoDataArr['shengHuoSkillList'][4]['level'],
            "fishing"           =>  $roleInfoDataArr['shengHuoSkillList'][5]['level'],
            'equip_score'       =>  $roleInfoDataArr['equipScore'],
            'equip_score_hh'    =>  $roleInfoDataArr['equipScoreHH'],
            'title'             =>  $roleInfoDataArr['title'],
            'gem_xiu_lian_score'=>  $roleInfoDataArr['gemXiuLianScore'],
            'gem_jin_jie_score' =>  $roleInfoDataArr['gemJinJieScore'],
            "remaintime"        =>  $roleListDatum['remaintime'],
        ];
    }


    /**
     * 通用图标数据处理方法
     * @param $roleInfoDataArr  array   角色数据
     * @param $name             string  要处理的数组标识（数组键名标识）
     * @param $nameItem         string  需要处理的数组二级标识（此数组位于 $name 数组内的一个数组键名）
     * @return array
     */
    private function roleDetailIconFix($roleInfoDataArr,$name,$nameItem='')
    {
        $tempArr = $roleInfoDataArr[$name];
        foreach ($tempArr as $key => &$value) {
            if ($value['icon'] != '') {
                $arr = explode('_',$value['icon']);
                if (count($arr) == 2) {
                    $value['icon'] = ['image'=>$arr[0].'.jpg','index'=>$arr[1]];
                } else {
                    $value['icon'] = ['image'=>$arr[0].'_'.$arr[1].'.jpg','index'=>$arr[2]];
                }
            }


            if ($nameItem != '' && $name != 'infants') {
                foreach ($value[$nameItem] as &$v1) {
                    if ($v1['icon'] != '') {
                        $arr1 = explode('_',$v1['icon']);
                        if (count($arr1) == 2) {
                            $v1['icon'] = ['image'=>$arr1[0].'.jpg','index'=>$arr1[1]];
                        } else {
                            $v1['icon'] = ['image'=>$arr1[0].'_'.$arr1[1].'.jpg','index'=>$arr1[2]];
                        }
                    }
                }
            }
        }

        return $tempArr;
    }

}
