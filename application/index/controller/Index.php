<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use GuzzleHttp\Client;
use OviDigital\JsObjectToJson\JsConverter;
use QL\Ext\PhantomJs;
use QL\QueryList;
use app\admin\model\server\Serverlist as ServerListModel;
use app\admin\model\server\Combine as ServerCombineModel;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $serverListModel = null;
    protected $serverCombineModel = null;


    //模拟请求客户端
    protected $GzClient = null;
    //模拟请求的请求数据
    protected $reqRes = null;
    //请求回来的html 结构体
    protected $html = null;
    //请求的uri
    protected $requestUri = 'http://tl.cyg.changyou.com';
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

    }

    /**
     * 合区信息
     */
    public function serverCombine()
    {
        //游戏区服信息
        $serverList = $this->serverListModel->column('name','world_id');
        //循环找分区名与合区信息

        $serverCombineData = $this->dynamicJs->browser($this->requestUri)->find('.server-info')->texts()->all();
        $newServeCombineData = [];


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
        //构建数据格式
        $newWorldData = [];//大区数据
        $newServerData = [];//服务器数据
        foreach ($server as $value) {
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

        //入库
        //$this->serverListModel->isUpdate(false)->saveAll($newWorldData);
        //$this->serverListModel->isUpdate(false)->saveAll($newServerData);

    }

    /**
     * 动态获取页面
     */
    public function dynamicGet()
    {


        dd($data->all());
    }


    public function index(QueryList $queryList,JsConverter $jsConverter)
    {
        $client = new Client();
        $res = $client->request('GET', 'http://tl.cyg.changyou.com', [
//            'query' => ['wd' => 'QueryList'],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ]);

        $html = (string)$res->getBody();



        //门派数据
        $menPaiName = $queryList->html($html)->find('.group-detail-item>a[data-key="profession"]')->attrs('data-value')->toArray();
        $menPaiNum = $queryList->html($html)->find('.group-detail-item>a[data-key="profession"]')->texts()->toArray();
        $menPai = array_combine($menPaiName,$menPaiNum);

        //等级
        $levelNum = $queryList->html($html)->find('.group-detail-item>a[data-key="level"]')->attrs('data-value')
            ->toArray();
        $levelName = $queryList->html($html)->find('.group-detail-item>a[data-key="level"]')->attrs('data-value')
            ->toArray();
        $level = array_combine($levelNum,$levelName);

        //取分页数据
        $text = $queryList->html($html)->find('.ui-pagination a')->texts()->toArray();
        $url = $queryList->html($html)->find('.ui-pagination a')->attrs('href')->toArray();
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


        // 0 1 2    0   3*0
        // 3 4 5    1   3*1
        // 6 7 8    2   3*2
        // 9 10 11  3   3*3  3*3 +1 3*3+2

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



}
