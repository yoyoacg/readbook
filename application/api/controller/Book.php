<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 16:20
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\model\GameEval;
use app\common\model\Novel;
use app\common\task\Task;

class Book extends Api
{
    protected $noNeedLogin = '*';

    public function start()
    {
        $url = 'https://book.qidian.com/ajax/book/category?_csrfToken=wS1oW2OPyD46rofjrPdaIFU1YwFew9QR6u3If61j&bookId=3607314';
        $title = '神道丹尊';
        $main_url = 'https://';
        $task = new Task();
        $result = $task->add_task($url,$title);
        $result = $task->for_one_task();
        var_dump($result);
    }

    public function game_eval_title_run()
    {
        $config = [
            'name' => 'yxpc',
            'title' => '游戏评测',
            'fields' => [
                'list' => [
                    'name' => ['.t3_l_one .one_l_con .one_l_con_tit a', 'text'],
                    'link' => ['.t3_l_one .one_l_con .one_l_con_tit a', 'href'],
                    'cover' => ['.t3_l_one .one_l_pic a img', 'src'],
                    'time' => ['.t3_l_one .one_l_con .one_l_con_tag', 'text'],
                    'desc' => ['.t3_l_one .one_l_con .one_l_con_con', 'text'],
                    'tag' => ['.t3_l_one .one_l_con .one_l_con_key', 'html'],
                    'source' => ['.t3_l_one .t3_l_one_r .pcdf7 span', 'text'],
                ]
            ],
            'table' => 'game_eval'
        ];
        $task = new Task($config);
        $task->on_start = function ($task) {
            for ($i = 1; $i < 83; $i++) {
                if ($i == 1) {
                    $url = 'http://www.ali213.net/news/pingce/';
                } else {
                    $url = 'http://www.ali213.net/news/pingce/index_' . $i . '.html';
                }
                $task->add_scan_url($url);
            }
        };
        $task->on_page = function ($task) {
            $task->sub_page_url('link');
        };
        $task->on_save = function ($data) {
            if (empty($data['page_url'])) return false;
            $result = [
                'name' => $data['name'],
                'cover' => $data['cover'],
                'desc' => $data['desc'],
                'source' => $data['source'] ?? '',
                'link' => $data['link']
            ];
            preg_match_all('/\d{4}-\d{2}-\d{2}/', $data['time'], $time);
            $result['release_time'] = $time[0][0];
            $result['create_time'] = date('Y-m-d H:i:s');
            $result['page_url'] = implode(',', $data['page_url']);
            $result['tag'] = implode(',', array_filter(explode(',', preg_replace('/<.*?>/', ',', $data['tag']))));
            return $result;
        };
        $task->start();
        $result = $task->save();
        var_dump($result);
    }

    public function game_eval_detail_run()
    {
        $config = [
            'name' => 'yxpc_detail',
            'title' => '游戏评测详情',
            'fields' => [
                'list' => [
                    'sub_title' => ['.zbcon .hover', 'text'],
                    'content' => ['#Content', 'html', '.n_show_m,span,font,p:last'],
                ]
            ],
            'table' => 'game_eval_detail'
        ];
        $GameEval = new GameEval();
        $task = new Task($config);
        $task->on_run = function ($data) {
            $parent = '/(<FIELDSET.*?>|<div.*?>|<iframe.*?>|<script.*?>|<strong.*?>)[\s\S]*?(<\/FIELDSET>|<\/div>|<\/iframe>|<\/script>|<\/strong>)/';
            $res = preg_replace($parent, '', $data['content']);
            $res = strip_tags($res,'<p><img>');
            if (mb_strstr($res, '更多相关资讯') !== false) {
                $res = mb_substr($res, 0, mb_strpos($res, '更多相关资讯'));
            }
            if (mb_strstr($data['sub_title'], 'No.1') !== false) {
                $data['sub_title'] ='No.1 游戏介绍';
            }
            $data['content'] = $res;
            return $data;
        };

        $task->on_save=function ($data){
            $data['create_time']=date('Y-m-d H:i:s');
            return $data;
        };

        $list = $GameEval->column('id,name,page_url');
        foreach ($list as $k => $v) {
            $url = explode(',', $v['page_url']);
            foreach ($url as $key=>$item) {
                 $task->collect_page($item,['pid'=>$v['id'],'sort'=>$key,'link'=>$item]);
            }
        }
        $task->save();
        var_dump(true);
    }

    public function biquge_task(){
        set_time_limit(3600);
        $config=[
            'name'=>'biquge',
            'title'=>'笔趣阁小说网',
            'scan_url'=>'http://www.biqiuge.com/paihangbang/',
            'fields'=>[
                'scan'=>[
                    'name'=>['.wrap ul li a','text'],
                    'url'=>['.block ul li a','href'],
                    'category'=>['.block ul li span','text'],
                ],
                'list'=>[
                    'name'=>['.listmain dd a','text'],
                    'url'=>['.listmain dd a','href'],
                    'cover'=>['.info .cover img','src'],
                    'author'=>['.info .small span:first','text'],
                    'status'=>['.info .small span:eq(2)','text'],
                    'font_all'=>['.info .small span:eq(3)','text'],
                    'update_time'=>['.info .small span:eq(4)','text'],
                    'new_last'=>['.info .small span:last','text'],
                    'desc'=>['.info .intro','html'],
                ],
                'page'=>[
                    'name'=>['.content h1','text'],
                    'content'=>['.content #content','html'],
                ]
            ],
            'REDIS'=>[
                'select'=>6
            ]
        ];
        $task = new \app\common\task\Book($config);
        $NovelModel = new Novel();
        $task->on_scan=function ($data)use ($NovelModel){
            $data['url']='http://www.biqiuge.com'.$data['url'];
            $id = $NovelModel->where('name',$data['name'])->value('id');
            if($id){
                $data['id']=$id;
                return $data;
            }else{
                $data['create_time']=date('Y-m-d H:i:s');
                $data['update_time']=date('Y-m-d H:i:s');
                $result = Novel::create($data);
                $data['id']=$result['id'];
                return $data;
            }
        };
        $task->on_run=function ($data){
            $main_url = 'http://www.biqiuge.com';
            $data['url'] = $main_url.$data['url'];
            $update =[];
            if(isset($data['cover'])){
                $update['cover'] = $main_url.$data['cover'];
            }
            if(isset($data['author'])){
                $update['author'] = mb_substr($data['author'],(mb_strpos($data['author'],'：')+1));
            }
            if(isset($data['status'])){
                $update['status'] = mb_substr($data['status'],(mb_strpos($data['status'],'：')+1));
            }
            if(isset($data['font_all'])){
                $update['font_all'] = mb_substr($data['font_all'],(mb_strpos($data['font_all'],'：')+1));
            }
            if(isset($data['new_last'])){
                $update['new_last'] = mb_substr($data['new_last'],(mb_strpos($data['new_last'],'：')+1));
            }
            if(isset($data['desc'])){
                $desc = mb_substr($data['desc'],0,mb_strpos($data['desc'],'<br>'));
                $desc = preg_replace('/\<span\>[\s\S]*?<\/span>/','',$desc);
               $update['desc'] = $desc;
            }
            if(!empty($update)){
                $update['id']=$data['pid'];
                $update['update_time']=date('Y-m-d H:i:s');
                Novel::update($update);
            }
            return $data;
        };
        $task->on_page = function ($data,$extend){
            if (mb_strstr($data['content'], 'https://www.biqiuge.com/') !== false) {
                $data['content'] = mb_substr($data['content'], 0, mb_strpos($data['content'], 'https://www.biqiuge.com'));
            }
            if (mb_strstr($data['content'], '请记住本书首发域名') !== false) {
                $data['content'] = mb_substr($data['content'], 0, mb_strpos($data['content'], '请记住本书首发域名'));
            }
            $dir = ROOT_PATH . '/public/book/'.$extend['name'].'/';
            $save_name = $data['name'].'.txt';
            if(!is_dir($dir)){
                mkdir($dir,0755,true);
            }
            file_put_contents($dir.$save_name,$data['content']);
            $result = [
                'name'=>$data['name'],
                'save_path'=>$dir,
                'pid'=>$extend['pid'],
                'main_name'=>$extend['name']
            ];
            return $result;
        };
        $task->start();

    }

}