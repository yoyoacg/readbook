<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 15:39
 */

namespace app\common\task;

use QL\Ext\CurlMulti;
use QL\QueryList;
use think\Db;
use think\Exception;


class Task extends Base
{
    /***
     * 启动前预处理操作
     * @var null
     */
    public $on_start = null;
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
    public $on_save=null;

    /**
     * 配置项
     * @var array
     */
    public static $config = [];


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
        self::$config['table'] = isset($options['table'])?$options['table']:null;
    }

    /**
     * 启动
     */
    public function start()
    {
        /**
         * 启动前预操作
         */
        if ($this->on_start) {
            call_user_func($this->on_start, $this);
        }
        while ($this->handler->lSize(self::$config['name'] . '_scan')) {
            $list = $this->add_list_url();
            $this->do_list_url($list);
        }
        /**抓取页面数据前操作**/
        if ($this->on_page) {
            call_user_func($this->on_page, $this);
        }
        $this->save();
        return true;
    }

    /***
     * 添加入口文件
     * @param $url
     */
    public function add_scan_url($url = null)
    {
        if($this->handler->sIsMember(self::$config['name'] . '_url_list',$url)) return true;
        $this->handler->lPush(self::$config['name'] . '_scan', $url);
        $this->handler->sAdd(self::$config['name'] . '_url_list',$url);
    }

    /**
     * 转换页码url
     * @return array
     */
    public function add_list_url()
    {
        $result = [];
        while ($this->handler->lSize(self::$config['name'] . '_scan')) {
            $result[] = $this->handler->lPop(self::$config['name'] . '_scan');
            if ($this->scan_pop && count($result) >= $this->scan_pop) {
                return $result;
                break;
            }
        }
        return $result;
    }

    /**
     * 处理列表数据
     * @param $url
     */
    public function do_list_url($url)
    {
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class, 'curlMulti')
            ->rules(self::$config['fields']['list'])
            ->curlMulti($url)
            ->success(function (QueryList $queryList, CurlMulti $curlMulti) {
                $data = $queryList->query()->getData();
                $list = $data->all();
                $queryList->destruct();
                /**数据中间层处理**/
                if ($this->on_run) {
                    $list = call_user_func($this->on_run, $list);
                }
                /***投递下次任务**/
                foreach ($list as $k => $v) {
                    if($this->handler->sIsMember(self::$config['name'] . '_url_list',$v['link'])) continue;
                    $this->handler->rPush(self::$config['name'] . '_list', json_encode($v));
                    $this->handler->sAdd(self::$config['name'] . '_url_list',$v['link']);
                }
            })
            ->error(function (QueryList $queryList){
                var_dump('error');
            })
            ->start([
                // 最大并发数，这个值可以运行中动态改变。
                'maxThread' => $this->maxThread,
                // 触发curl错误或用户错误之前最大重试次数，超过次数$error指定的回调会被调用。
                'maxTry' => $this->maxTry,
                // 全局CURLOPT_*
                'opt' => [CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 1,
                    CURLOPT_RETURNTRANSFER => true
                ],
            ]);
    }

    /**
     * 获取子页面url
     * @param $url_tag
     */
    public function sub_page_url($url_tag)
    {
        while ($this->handler->lSize(self::$config['name'] . '_list')) {
            $pop = $this->handler->lPop(self::$config['name'] . '_list');
            $data = json_decode($pop, true);
            $ql = QueryList::getInstance();
            $list = $ql->get($data[$url_tag])->rules([
                'url' => ['.zbcon a', 'href'],
            ])->query()->getData()->all();
            $http = str_replace(strrchr($data[$url_tag], '/'), '', $data[$url_tag]);
            foreach ($list as $k => $v) {
                $data['page_url'][] = $http . '/' . $v['url'];
            }
            $ql->destruct();
            $this->handler->rPush(self::$config['name'] . '_page', json_encode($data));
        }
    }

    /**
     * 页面采集
     * @param string $url
     * @param array $extend
     * @return bool
     */
    public function collect_page($url='',$extend=[]){
        if($this->handler->sIsMember(self::$config['name']. '_url_list',$url)){
           return true;
        }
        $ql = QueryList::getInstance();
        $html = $ql->get($url)
            ->rules(self::$config['fields']['list'])
            ->query()->getData();
        $list = $html->all();
       foreach ($list as $v){
           if ($this->on_run){
               $data = call_user_func($this->on_run,$v);
           }else{
               $data = $v;
           }
           $result = array_merge($data,$extend);
           $this->handler->rPush(self::$config['name'] . '_page',json_encode($result));
       }
       $ql->destruct();
       $this->handler->sAdd(self::$config['name']. '_url_list',$url);
       return true;
    }

    /**
     * 采集页面
     */
    public function do_collect_page()
    {
        $ql = QueryList::getInstance();
        if ($this->handler->lLen(self::$config['name'] . '_page')) {
            while ($this->handler->lSize(self::$config['name'] . '_page')) {
                $page = $this->handler->lPop(self::$config['name'] . '_page');
                $page_data = json_decode($page, true);
                if (empty($page_data)) continue;
                if (!empty($page_data['page_url'])) {
                    $this->handler->sAdd(self::$config['name'] . '_data', json_encode($page_data));
                    try{
                        $ql->use(CurlMulti::class, 'curlMulti')
                            ->rules(self::$config['fields']['page'])
                            ->curlMulti($page_data['page_url'])
                            ->success(function (QueryList $queryList) use ($page_data) {
                                $data = $queryList->query()->getData();
                                $list = $data->all();
                                $queryList->destruct();
                                foreach ($list as $k=>$v){
                                    $v['title']= $page_data['name'];
                                    $this->handler->sAdd(self::$config['name'] . '_data_list', json_encode($v));
                                }
                            })
                            ->error(function ($errinfo){
                                echo "Current url: \r\n";
                                print_r($errinfo);
                            })
                            ->start([
                                // 最大并发数，这个值可以运行中动态改变。
                                'maxThread' => $this->maxThread,
                                // 触发curl错误或用户错误之前最大重试次数，超过次数$error指定的回调会被调用。
                                'maxTry' => $this->maxTry,
                                // 全局CURLOPT_*
                                'opt' => [CURLOPT_TIMEOUT => 10,
                                    CURLOPT_CONNECTTIMEOUT => 1,
                                    CURLOPT_RETURNTRANSFER => true
                                ],
                            ]);
                        echo $page_data['name']."<br>\r\n";
                    }catch (\Exception $exception){
                        print_r('Think error......');
                        continue;
                    }
                }
            }
        }
        if ($this->handler->lLen(self::$config['name'] . '_list')) {
            while ($this->handler->lSize(self::$config['name'] . '_list')) {
                $page = $this->handler->lPop(self::$config['name'] . '_list');
                $page_data = json_decode($page, true);
                if (empty($page_data)) continue;
                if (isset($page_data['link'])) {
                    $html = $ql->get($page_data['link'])
                        ->removeHead()
                        ->encoding('utf-8', 'gb2312');
                    $sub_title = $html->find('#p1')->text();
                    $content = $html->find('#Content')->html();
                    $page_data['page_list'][] = [
                        'sub_title' => $sub_title,
                        'content' => $content
                    ];
                    $this->handler->sAdd(self::$config['name'] . '_data', json_encode($page_data));
                    $ql->destruct();
                }
            }
        }
    }


    /**
     * @param $url
     * @param null $data
     * @param array $header
     * @return bool|string
     */
    public function http_request($url, $data = null, $header = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, $header);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
//    $res = json_decode($output,true);
        return $output;
    }

    public function save(){
        while ($this->handler->lSize(self::$config['name'] . '_page')){
            $data = $this->handler->lPop(self::$config['name'] . '_page');
            $data = json_decode($data,true);
            if($this->on_save){
                $data = call_user_func($this->on_save,$data);
                if($data===false){
                    continue;
                }
            }
            if(self::$config['table']==null){
                return false;
                break;
            }
            Db::table(self::$config['table'])->insert($data);
        }
        return true;
//        $data = $this->handler->sRandMember(self::$config['name'] . '_data_list');
//        $result = json_decode($data,true);
/*        $parent = '/(<FIELDSET.*?>|<div.*?>|<iframe.*?>|<script.*?>|<strong.*?>)[\s\S]*?(<\/FIELDSET>|<\/div>|<\/iframe>|<\/script>|<\/strong>)/';*/
//        $res = preg_replace($parent,'',$result['content']);
//        $ql = QueryList::getInstance();
//        $res = '<div id="content">'.$res.'</div>';
//        $html = $ql->setHtml($res)->find('#content');
//        $html->find('.n_show_m,span,font,p:last')->remove();
//        $res = $html->html();
//        if(mb_strstr($res,'更多相关资讯')!==false){
//            $content = mb_substr($res,0,mb_strpos($res,'更多相关资讯'));
//        }
//        var_dump(strip_tags($res,'<p><img>'));
    }



}