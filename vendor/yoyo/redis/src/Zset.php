<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 15:47
 */

namespace redis;

/***
 * 基于redis Zset 的排序
 * Class Zset
 * @package redis
 */
class Zset
{
    use Base;

    /**
     * 向名称为key的zset中添加元素member，score用于排序。
     * 如果该元素已经存在，则根据score更新该元素的顺序。
     * @param string $key
     * @param float $score
     * @param string $member
     * @return int
     */
    public function zadd(string $key,float $score,string $member){
        return $this->handler->zAdd($key,$score,$member);
    }

    /**
     * 删除名称为key的zset中的元素member
     * @param string $key
     * @param string $member
     * @return int
     */
    public function zRem(string $key,string $member){
        return $this->handler->zRem($key,$member);
    }

    /**
     * 如果在名称为key的zset中已经存在元素member，则该元素的score增加increment；
     * 否则向集合中添加该元素，其score的值为increment
     * @param string $key
     * @param int $increment
     * @param string $member
     * @return float
     */
    public function zIncrBy(string $key,int $increment,string $member){
        return $this->handler->zIncrBy($key,$increment,$member);
    }

    /**
     * 返回名称为key的zset（元素已按score从小到大排序）中member元素的rank（即index，从0开始），
     * 若没有member元素，返回“nil”
     * @param string $key
     * @param string $member
     * @return int
     */
    public function zRank(string $key,string $member){
        return $this->handler->zRank($key,$member);
    }

    /**
     *返回名称为key的zset（元素已按score从大到小排序）中member元素的rank（即index，从0开始），
     * 若没有member元素，返回“nil”
     * @param string $key
     * @param string $member
     * @return int
     */
    public function zRevRank(string $key,string $member){
        return $this->handler->zRevRank($key,$member);
    }

    /**
     * 返回名称为key的zset（元素已按score从小到大排序）
     * 中的index从start到end的所有元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array
     */
    public function zRange(string $key,int $start,int $end){
        return $this->handler->zRange($key,$start,$end);
    }
    /**
     * 返回名称为key的zset（元素已按score从大到小排序）
     * 中的index从start到end的所有元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array
     */
    public function zRevRange(string $key,int $start,int $end){
        return $this->handler->zRevRange($key,$start,$end);
    }

    /**
     * 返回名称为key的zset中score >= min且score <= max的所有元素
     * @param string $key
     * @param int $min
     * @param int $max
     * @param null $option
     * @return array
     */
    public function zRangByScore(string $key,int $min,int $max,$option=null){
        return $this->handler->zRangeByScore($key,$min,$max,$option);
    }

    /**
     * 返回名称为key的zset的基数
     * @param string $key
     * @return int
     */
    public function zCard(string $key){
        return $this->handler->zCard($key);
    }

    /**
     * 返回名称为key的zset中元素element的score
     * @param string $key
     * @param string $member
     * @return float
     */
    public function zScore(string $key,string $member){
        return $this->handler->zScore($key,$member);
    }

    /**
     * 删除名称为key的zset中rank >= min且rank <= max的所有元素
     * @param string $key
     * @param int $min
     * @param int $max
     * @return int
     */
    public function zRemRangeByRank(string $key,int $min,int $max){
        return $this->handler->zRemRangeByRank($key,$min,$max);
    }
    /**
     * 删除名称为key的zset中score >= min且score <= max的所有元素
     * @param string $key
     * @param int $min
     * @param int $max
     * @return int
     */
    public function zRemRangeByScore(string $key,int $min,int $max){
        return $this->handler->zRemRangeByScore($key,$min,$max);
    }


}