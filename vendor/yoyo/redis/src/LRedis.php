<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 10:32
 */

namespace redis;

/**
 * 基于redis List
 * Class LRedis
 * @package redis
 */
class LRedis
{
    use Base;

    /***
     * 将一个值或多个值插入列表头部
     * @param string $key
     * @param string $value
     * @param string|null $value2
     * @param string|null $valueN
     * @return bool|int
     */
    public function lPush(string $key,string $value,string $value2=null,string $valueN=null){
        return  $this->handler->lPush($key,$value,$value2,$valueN);
    }

    /**
     * 将值插入已存在列表头部，列表不存在时返回0
     * @param string $key
     * @param string $value1
     * @return int
     */
    public function lPushX(string $key,string $value1){
        return  $this->handler->lPushx($key,$value1);
    }

    /**
     * 将一个或多个值插入列表尾部
     * @param string $key
     * @param string $value
     * @param string|null $value2
     * @param string|null $valueN
     * @return bool|int
     */
    public function rPush(string $key,string $value,string $value2=null,string $valueN=null){
        return $this->handler->rPush($key,$value,$value2,$valueN);
    }

    /**
     * 将值插入已存在列表的尾部，列表不存在则返回0
     * @param string $key
     * @param string $value
     * @return int
     */
    public function rPushX(string $key,string $value){
        return  $this->handler->rPushx($key,$value);
    }

    /**
     * 移除并返回列表第一个元素
     * @param string $key
     * @return string
     */
    public function lPop(string $key){
        return $this->handler->lPop($key);
    }

    /**
     * 移除并返回列表最后一个元素
     * @param string $key
     * @return string
     */
    public function rPop(string $key){
        return $this->handler->rPop($key);
    }

    /**
     * 移除并返回列表第一个元素 timeout=0时 表示无限延长
     * 阻塞操作
     * @param string $key
     * @param int $timeout
     * @return array
     */
    public function blPop(string $key,int $timeout=10){
        return $this->handler->blPop($key,$timeout);
    }

    /**
     * 移除并返回列表最后一个元素 timeout=0时 表示无限延长
     * 阻塞操作
     * @param string $key
     * @param int $timeout
     * @return array
     */
    public function brPop(string $key,int $timeout=10){
        return $this->handler->brPop($key,$timeout);
    }

    /**
     * 返回列表长度
     * @param string $key
     * @return int
     */
    public function lLen(string $key){
        return $this->handler->lLen($key);
    }

    /**
     * 返回列表指定区间的元素，
     * 下标为负数时表示从尾部开始
     * @param string $key
     * @param int $start
     * @param int $end
     * @return mixed
     */
    public function lRange(string $key,int $start=0,int $end=-1){
        return $this->lRange($key,$start,$end);
    }

    /**
     * 移除列表中与value相同的值
     * count > 0 : 从表头开始向表尾搜索，移除与 VALUE 相等的元素，数量为 COUNT 。
     * count > 0 : 从表头开始向表尾搜索，移除与 VALUE 相等的元素，数量为 COUNT 。
     * count = 0 : 移除表中所有与 VALUE 相等的值。
     * @param string $key
     * @param string $value
     * @param int $count
     * @return int
     */
    public function lRem(string $key,string $value,int $count=0){
        return $this->handler->lRem($key,$value,$count);
    }

    /**
     * 通过索引来设置值
     * @param string $key
     * @param int $index
     * @param string $value
     * @return bool
     */
    public function lSet(string $key,int $index,string $value){
        return $this->handler->lSet($key,$index,$value);
    }

    /**
     * 通过索引获取值
     * @param string $key
     * @param int $index
     * @return String
     */
    public function lIndex(string $key,int $index){
        return $this->handler->lIndex($key,$index);
    }

    /**
     * 将列表最后一个元素移动到另一个列表，并返回
     * 如果 key==key2 则进行尾旋转操作
     * @param string $key
     * @param string $key2
     * @return string
     */
    public function rPopLPush(string $key,string $key2){
        return $this->handler->rpoplpush($key,$key2);
    }

    /**
     *将列表最后一个元素移动到另一个列表，并返回,这是一个阻塞操作
     * 超时参数timeout接受一个以秒为单位的数字作为值。超时参数设为0表示阻塞时间可以无限期延长
     * 如果 key==key2 则进行尾旋转操作
     * @param string $key
     * @param string $key2
     * @param int $timeout
     * @return string
     */
    public function brpoplpush(string $key,string $key2,int $timeout=10){
        return $this->handler->brpoplpush($key,$key2,$timeout);
    }


}