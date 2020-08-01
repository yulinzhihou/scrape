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

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $serverListModel = null;
    protected $serverCombineModel = null;
    protected $professionModel = null;
    protected $rolePublicModel = null;


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
        $roleListData = $this->rolePublicModel->column('url','id');

        foreach ($roleListData as $roleListDatum) {
            //查询全服公示产品
//            $this->requestUri = $roleListDatum;
            $this->requestUri = 'http://tl.cyg.changyou.com/goods/char_detail?serial_num=20200728856352762';
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
            $server = json_decode($roleInfoDataJson,true);
            //构建数据库数据结构

            dump($server);
            die;
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

}
