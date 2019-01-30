<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/29
 * Time: 11:08
 */

namespace app\common\task;

use GuzzleHttp\Client;
use QL\Ext\CurlMulti;
use QL\QueryList;


class Book extends Base
{
    /***
     * 启动前预处理操作
     * @var null
     */
    public $on_start = null;
    /**
     * 入口数据操作
     * @var null
     */
    public $on_scan = null;
    /**
     * 中间处理数据操作
     * @var null
     */
    public $on_run = null;
    /**
     * 抓取单页前操作
     * @var null
     */
    public $on_page = null;
    /***
     * 同时处理多少列表
     * @var null
     */
    public $scan_pop = null;
    /**最大并发数
     * @var int
     */
    protected $maxThread = 10;
    /****
     * 错误重试上限
     * @var int
     */
    protected $maxTry = 3;
    /**
     * 保存前操作
     * @var null
     */
    public $on_save = null;
    /**
     * 入口url
     * @var null
     */
    public $scan_url = null;

    /**
     * 配置项
     * @var array
     */
    public static $config = [];
    /**
     * 轮询时间 秒
     * 在此期间的列表不会被再次爬取
     * @var int
     */
    public $poll_timeout = 18000;


    public function __construct(array $options = [])
    {
        /** Redis **/
        if (isset($options['REDIS'])) {
            parent::__construct($options['REDIS']);
        } else {
            parent::__construct();
        }
        self::$config['name'] = isset($options['name']) ? $options['name'] : 'task_' . time();
        self::$config['title'] = isset($options['title']) ? $options['title'] : '';
        self::$config['fields'] = isset($options['fields']) ? $options['fields'] : [];
        self::$config['table'] = isset($options['table']) ? $options['table'] : null;
        $this->scan_url = isset($options['scan_url']) ? $options['scan_url'] : [];
    }

    public function start()
    {
        if ($this->on_start) {
            call_user_func($this->on_start, $this);
        }
//        if(!is_array($this->scan_url)||count($this->scan_url)==0) return 'Error:请输入起始url....';
//        $this->add_scan_url($this->scan_url);
//        $this->do_collect_list();
        $arr = $this->do_collect_page();
       foreach ($arr as $v){
           var_dump($v);
       }
    }

    public function for_one_task(){
        for ($i=0;$i<200;$i++){
            yield $this->do_collect_page();
        }
    }

    /**
     * 入口文件处理
     * @param array $url
     */
    public function add_scan_url($url)
    {
        $ql = QueryList::getInstance();
        $html = $ql->get($url)
            ->removeHead()
            ->encoding('utf-8', 'gb2312')
            ->rules(self::$config['fields']['scan'])
            ->query()->getData();
        $list = $html->all();
        foreach ($list as $k => $v) {
            if ($this->handler->sIsMember(self::$config['name'] . '_scan', trim($v['name']))) continue;
            /**数据中间层处理**/
            if ($this->on_scan) {
                $v = call_user_func($this->on_scan, $v);
            }
            $v['time'] = 0;
            $this->handler->sAdd(self::$config['name'] . '_scan_list', json_encode($v));
            $this->handler->sAdd(self::$config['name'] . '_scan', trim($v['name']));
        }
//        $ql->use(CurlMulti::class, 'curlMulti')
//            ->removeHead()
//            ->encoding('utf-8', 'gb2312')
//            ->rules(self::$config['fields']['scan'])
//            ->curlMulti($url[0])
//            ->success(function (QueryList $queryList,CurlMulti $multi) {
//                $data = $queryList->query()
//                    ->getData();
//                $list = $data->all();
//                var_dump($multi);
//                var_dump($data);die();
////                $queryList->destruct();
//                /***投递下次任务**/
//                foreach ($list as $k => $v) {
//                    if($this->handler->sIsMember(self::$config['name'] . '_scan',trim($v['name']))) continue;
//                    /**数据中间层处理**/
//                    if ($this->on_scan) {
//                        $v = call_user_func($this->on_scan, $v);
//                    }
//                    $this->handler->sAdd(self::$config['name'] . '_scan_list', json_encode($v));
//                    $this->handler->sAdd(self::$config['name'] . '_scan',trim($v['name']));
//                }
//            })
//            ->start([
//                // 最大并发数，这个值可以运行中动态改变。
//                'maxThread' => $this->maxThread,
//                // 触发curl错误或用户错误之前最大重试次数，超过次数$error指定的回调会被调用。
//                'maxTry' => $this->maxTry,
//                // 全局CURLOPT_*
//                'opt' => [CURLOPT_TIMEOUT => 10,
//                    CURLOPT_CONNECTTIMEOUT => 1,
//                    CURLOPT_RETURNTRANSFER => true
//                ],
//            ]);
    }

    /***
     * 采集章节列表url,并加入队列任务
     * @return bool
     */
    public function do_collect_list()
    {
        $name_list = $this->handler->sMembers(self::$config['name'] . '_scan_list');
        $time = time();
        $timeout = $time - $this->poll_timeout;
        foreach ($name_list as $k => $v) {
            $data = json_decode($v, true);
            if ($data['time'] > $timeout) {
                continue;
            }
            $ql = QueryList::getInstance();
            $list = $ql->get($data['url'])
                ->removeHead()
                ->encoding('utf-8', 'gb2312')
                ->rules(self::$config['fields']['list'])
                ->query()
                ->getData()
                ->all();
            if (self::is_win()) {
                foreach ($list as $key => $val) {
                    $val['pid'] = $data['id'];
                    if ($this->handler->sIsMember(self::$config['name'] . '_chapter_' . $data['id'], trim($val['name']))) continue;
                    if ($this->on_run) {
                        $val = call_user_func($this->on_run, $val);
                    }
                    $coach = [
                        'list' => $val['url'],
                        'pid' => $data['id'],
                        'name' => $data['name']
                    ];
                    /****投递下次任务***/
                    $this->handler->rPush(self::$config['name'] . '_do_chapter', json_encode($coach));
                }
                $ql->destruct();
            } else {
                $result = [];
                foreach ($list as $key => $val) {
                    $val['pid'] = $data['id'];
                    if ($this->handler->sIsMember(self::$config['name'] . '_chapter_' . $data['id'], trim($val['name']))) continue;
                    if ($this->on_run) {
                        $val = call_user_func($this->on_run, $val);
                    }
                    $result[] = $val['url'];
                }
                $coach = [
                    'list' => $result,
                    'pid' => $data['id'],
                    'name' => $data['name']
                ];
                /****投递下次任务***/
                $this->handler->rPush(self::$config['name'] . '_do_chapter', json_encode($coach));
                $ql->destruct();
            }
            $data['time'] = $time;
            $this->handler->sRem(self::$config['name'] . '_scan_list', $v);
            $this->handler->sAdd(self::$config['name'] . '_scan_list', json_encode($data));
//            $this->handler->rPush(self::$config['name'].'_do_chapter_'.$data['id'],json_encode($coach));
        }
        return true;
    }

    /**
     * @return \Generator
     */
    public function do_collect_page()
    {
        while ($this->handler->lSize(self::$config['name'] . '_do_chapter')) {
            $task = $this->handler->lPop(self::$config['name'] . '_do_chapter');
            $tasks = json_decode($task, true);
             $this->collect_page($tasks['list'], ['pid' => $tasks['pid'], 'name' => $tasks['name']]);
        }
    }


    /**
     * 采集单页
     * @param $url
     * @param array $extend
     * @return int
     */
    public function collect_page($url, $extend = [])
    {
        $ql = QueryList::getInstance();
        $ip=mt_rand(11, 191).".".mt_rand(0, 240).".".mt_rand(1, 240).".".mt_rand(1, 240);
        $agentarry=[
            "safari 5.1 – MAC"=>"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
            "safari 5.1 – Windows"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50",
            "Firefox 38esr"=>"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
            "IE 11"=>"Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.3; rv:11.0) like Gecko",
            "IE 9.0"=>"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0",
            "IE 8.0"=>"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)",
            "IE 7.0"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)",
            "IE 6.0"=>"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
            "Firefox 4.0.1 – MAC"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Firefox 4.0.1 – Windows"=>"Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Opera 11.11 – MAC"=>"Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; en) Presto/2.8.131 Version/11.11",
            "Opera 11.11 – Windows"=>"Opera/9.80 (Windows NT 6.1; U; en) Presto/2.8.131 Version/11.11",
            "Chrome 17.0 – MAC"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
            "傲游（Maxthon）"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Maxthon 2.0)",
            "腾讯TT"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; TencentTraveler 4.0)",
            "世界之窗（The World） 2.x"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
            "世界之窗（The World） 3.x"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; The World)",
            "360浏览器"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; 360SE)",
            "搜狗浏览器 1.x"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SE 2.X MetaSr 1.0; SE 2.X MetaSr 1.0; .NET CLR 2.0.50727; SE 2.X MetaSr 1.0)",
            "Avant"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Avant Browser)",
            "Green Browser"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
            //移动端口
            "safari iOS 4.33 – iPhone"=>"Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5",
            "safari iOS 4.33 – iPod Touch"=>"Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5",
            "safari iOS 4.33 – iPad"=>"Mozilla/5.0 (iPad; U; CPU OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5",
            "Android N1"=>"Mozilla/5.0 (Linux; U; Android 2.3.7; en-us; Nexus One Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Android QQ浏览器 For android"=>"MQQBrowser/26 Mozilla/5.0 (Linux; U; Android 2.3.7; zh-cn; MB200 Build/GRJ22; CyanogenMod-7) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Android Opera Mobile"=>"Opera/9.80 (Android 2.3.4; Linux; Opera Mobi/build-1107180945; U; en-GB) Presto/2.8.149 Version/11.10",
            "Android Pad Moto Xoom"=>"Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13",
            "BlackBerry"=>"Mozilla/5.0 (BlackBerry; U; BlackBerry 9800; en) AppleWebKit/534.1+ (KHTML, like Gecko) Version/6.0.0.337 Mobile Safari/534.1+",
            "WebOS HP Touchpad"=>"Mozilla/5.0 (hp-tablet; Linux; hpwOS/3.0.0; U; en-US) AppleWebKit/534.6 (KHTML, like Gecko) wOSBrowser/233.70 Safari/534.6 TouchPad/1.0",
            "UC标准"=>"NOKIA5700/ UCWEB7.0.2.37/28/999",
            "UCOpenwave"=>"Openwave/ UCWEB7.0.2.37/28/999",
            "UC Opera"=>"Mozilla/4.0 (compatible; MSIE 6.0; ) Opera/UCWEB7.0.2.37/28/999",
            "微信内置浏览器"=>"Mozilla/5.0 (Linux; Android 6.0; 1503-M02 Build/MRA58K) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/37.0.0.0 Mobile MQQBrowser/6.2 TBS/036558 Safari/537.36 MicroMessenger/6.3.25.861 NetType/WIFI Language/zh_CN",
        ];

        if (self::is_win()) {
            $data = $ql->get($url,[],[
                'useragent'=>$agentarry[array_rand($agentarry)],
                'headers'=>[
                    'CLIENT-IP'=>$ip,
                    'X-FORWARDED-FOR'=>$ip,
                ]
            ])
                ->removeHead()
                ->encoding('utf-8', 'gb2312')
                ->rules(self::$config['fields']['page'])
                ->query()
                ->getData();
            $list = $data->all();
            /***投递下次任务**/
            foreach ($list as $k => $v) {
                if ($this->handler->sIsMember(self::$config['name'] . '_chapter_' . $extend['pid'], trim($v['name']))) continue;
                /**数据中间层处理**/
                if ($this->on_page) {
                    $v = call_user_func($this->on_page, $v, $extend);
                }
                $this->handler->sAdd(self::$config['name'] . '_chapter_list', json_encode($v));
                $this->handler->sAdd(self::$config['name'] . '_chapter_' . $extend['pid'], trim($v['name']));
            }
            $ql->destruct();
        } else {
            $ql->use(CurlMulti::class)
                ->rules(self::$config['fields']['page'])
                ->curlMulti($url)
                ->success(function (QueryList $queryList, CurlMulti $curlMulti) use ($extend) {
                    $data = $queryList->query()->getData();
                    $list = $data->all();
                    /***投递下次任务**/
                    foreach ($list as $k => $v) {
                        if ($this->handler->sIsMember(self::$config['name'] . '_chapter_' . $extend['pid'], trim($v['name']))) continue;
                        /**数据中间层处理**/
                        if ($this->on_page) {
                            $v = call_user_func($this->on_page, $v);
                        }
                        $this->handler->sAdd(self::$config['name'] . '_chapter_list', json_encode($v));
                        $this->handler->sAdd(self::$config['name'] . '_chapter_' . $extend['pid'], trim($v['name']));
                    }
                    $queryList->destruct();
                })
                ->error(function (QueryList $queryList) {
                    var_dump('error');
                })
                ->start([
                    // 最大并发数，这个值可以运行中动态改变。
                    'maxThread' => 5,
                    // 触发curl错误或用户错误之前最大重试次数，超过次数$error指定的回调会被调用。
                    'maxTry' => $this->maxTry,
                    'opt' => [CURLOPT_TIMEOUT => 10,
                        CURLOPT_CONNECTTIMEOUT => 1,
                        CURLOPT_RETURNTRANSFER => true
                    ],
                ]);
        }
        return 1;
    }

    /**
     * 是否是win
     * @return bool
     */
    public static function is_win()
    {
        return true;
        return strtoupper(substr(PHP_OS, 0, 3)) === "WIN";
    }

    public function save()
    {
        $model = new Model();

    }


}