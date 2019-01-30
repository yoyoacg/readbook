<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 14:58
 */

namespace redis;


/**
 * 基于redis string
 * Class StrRedis
 * @package redis
 */
class StrRedis
{
    use Base;

    /**
     * 设置给定 key 的值。
     * 如果 key 已经存储其他值， SET 就覆写旧值，且无视类型
     * @param string $key
     * @param string $value
     * @param int $timeout
     * @return bool
     */
    public function set(string $key,string $value,int $timeout=0){
        return $this->handler->set($key,$value,$timeout);
    }

    /**
     * 指定的 key 不存在时，为 key 设置指定的值
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setnx(string $key,string $value){
        return $this->handler->setnx($key,$value);
    }

    /**
     * 为指定的 key 设置值及其过期时间。
     * 如果 key 已经存在， SETEX 命令将会替换旧的值。
     * @param string $key
     * @param string $value
     * @param int $timeout
     * @return bool
     */
    public function setex(string $key,string $value,int $timeout=10){
        return $this->handler->setex($key,$timeout,$value);
    }

    /**
     * 用指定的字符串覆盖给定 key 所储存的字符串值，覆盖的位置从偏移量 offset 开始
     * @param string $key
     * @param int $offset
     * @param string $value
     * @return string
     */
    public function setRange(string $key,int $offset,string $value){
        return $this->handler->setRange($key,$offset,$value);
    }

    /**
     * 同时设置一个或多个 key-value 对。
     * 同名的key存在时，MSET会用新值覆盖旧值
     * 原子性操作
     * @param array $value
     * @return bool
     */
    public function mset(array $value){
        return $this->handler->mset($value);
    }

    /**
     * 所有给定 key 都不存在时，同时设置一个或多个 key-value 对
     * 即使只有一个key已存在，MSETNX也会拒绝所有传入key的设置操作
     * MSETNX是原子性的，所有字段要么全被设置，要么全不被设置。
     * @param array $value
     * @return int
     */
    public function msetnx(array $value){
        return $this->handler->msetnx($value);
    }

    /**
     * 为指定的 key 追加值
     * 如果 key 已经存在并且是一个字符串， APPEND 命令将 value 追加到 key 原来的值的末尾。
     * 如果 key 不存在， APPEND 就简单地将给定 key 设为 value ，就像执行 SET key value 一样
     * @param string $key
     * @param string $value
     * @return int
     */
    public function append(string $key,string $value){
        return $this->handler->append($key,$value);
    }

    /**
     * 获取给定key的值
     * @param string $key
     * @return bool|string
     */
    public function get(string $key){
        return $this->handler->get($key);
    }

    /**
     * 返回key 区间的值 相当于字符串截取
     * @param string $key
     * @param int $start
     * @param int $end
     * @return string
     */
    public function getRange(string $key,int $start,int $end){
        return $this->handler->getRange($key,$start,$end);
    }

    /**
     * 设置指定 key 的值，并返回 key 旧的值。
     * 当 key 没有旧值时，即 key 不存在时，返回 nil 。
     *当 key 存在但不是字符串类型时，返回一个错误
     * @param string $key
     * @param string $value
     * @return string
     */
    public function getSet(string $key,string $value){
        return $this->handler->getSet($key,$value);
    }

    /**
     * 自增
     * @param string $key
     * @return int
     */
    public function inc(string $key){
        return $this->handler->incr($key);
    }

    /**
     * 自减
     * @param string $key
     * @return int
     */
    public function dec(string $key){
        return $this->handler->decr($key);
    }

    /**
     *  key 中储存的数字加上指定的增量值
     * @param string $key
     * @param int $number
     * @return int
     */
    public function incBy(string $key,int $number){
        return $this->handler->incrBy($key,$number);
    }

    /**
     * 将 key 所储存的值减去指定的减量值
     * @param string $key
     * @param int $number
     * @return int
     */
    public function decBy(string $key,int $number){
        return $this->handler->decrBy($key,$number);
    }


}