<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14
 * Time: 17:59
 */
namespace redis;

/**基于redis set
 * Class SRdeis
 * @package redis
 */
class SRdeis
{
   use Base;

    /**
     * 添加元素到集合
     * @param string $key
     * @param string $value
     */
    public function sAdd($key = '',$value=''){
        $this->handler->sAdd($key,$value);
    }

    /**
     * 移除单个元素
     * @param string $key
     * @param string $value
     */
    public function sRem($key='',$value=''){
        $this->handler->sRem($key,$value);
    }

    /**
     * 查看集合所有元素
     * @param string $key
     * @return array
     */
    public function sMembers($key=''){
        $data = $this->handler->sMembers($key);
        return $data;
    }

    /**
     * 判断是否是集合元素
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function sHas($key='',$value=''){
        $result = $this->handler->sIsMember($key,$value);
        return $result?true:false;
    }

    /**
     * 取出集合差集
     * @param $key1
     * @param $key2
     * @param null $keyN
     * @return array
     */
    public function sDiff($key1,$key2,$keyN=null){
        $result = $this->handler->sDiff($key1,$key2,$keyN);
        return $result;
    }

    /**
     * 返回集合数量
     * @param $key
     * @return int
     */
    public function sCard($key){
        $result = $this->handler->sCard($key);
        return $result;
    }

    /**
     * 将成员从集合1移动到集合2
     * @param $value
     * @param $key1
     * @param $key2
     * @return bool
     */
    public function sMove($value,$key1,$key2){
        $result = $this->handler->sMove($key1,$key2,$value);
        return $result;
    }

    /**
     * 移除并返回一个随机元素
     * @param $key
     * @return string
     */
    public function sPop($key){
        $result = $this->handler->sPop($key);
        return $result;
    }

    /**
     * 随机返回元素，默认返回1
     * @param $key
     * @param int $count
     * @return array|string
     */
    public function sRandMember($key,$count=1){
        $result = $this->handler->sRandMember($key,$count);
        return $result;
    }

    /**
     * 返回集合的交集
     * @param $key
     * @param $key1
     * @param null $keyN
     * @return array
     */
    public function sInter($key,$key1,$keyN=null){
        $result = $this->handler->sInter($key,$key1,$keyN);
        return $result;
    }

    /**
     * 将交集存储于新的集合$dstKey
     * @param $dstKey
     * @param $key
     * @param $key1
     * @param null $keyN
     * @return int
     */
    public function sInterStore($dstKey,$key,$key1,$keyN=null){
        $result = $this->handler->sInterStore($dstKey,$key,$key1,$keyN);
        return $result;
    }

    /**
     * 返回集合并集
     * @param $key
     * @param $key1
     * @param null $keyN
     * @return array
     */
    public function sUnion($key,$key1,$keyN=null){
        $result = $this->handler->sUnion($key,$key1,$keyN);
        return $result;
    }







}