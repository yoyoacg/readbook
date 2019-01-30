<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 14:00
 */

namespace redis;

/**
 * 基于redis Hash
 * Class Hash
 * @package redis
 */
class Hash
{
    use Base;

    /**
     * 哈希表字段赋值
     * 如果哈希表不存在，一个新的哈希表被创建并进行 HSET 操作。
     * 如果字段已经存在于哈希表中，旧值将被覆盖
     * @param string $key
     * @param string $field
     * @param string $value
     * @return bool|int
     */
    public function hset(string $key,string $field,string $value){
        return $this->handler->hSet($key,$field,$value);
    }

    /**
     * 为哈希表中不存在的的字段赋值 。
     * 如果哈希表不存在，一个新的哈希表被创建并进行 HSET 操作
     * 如果字段已经存在于哈希表中，操作无效
     * @param string $key
     * @param string $field
     * @param string $value
     * @return bool
     */
    public function hSetNx(string $key,string $field,string $value){
        return $this->handler->hSetNx($key,$field,$value);
    }

    /**
     * 同时将多个 field-value (字段-值)对设置到哈希表中。
     * 会覆盖已有字段
     * 如果哈希表不存在，会创建一个空哈希表，并执行 HMSET 操作。
     * @param string $key
     * @param array $value
     * @return bool
     */
    public function hMset(string $key,array $value){
        return $this->handler->hMSet($key,$value);
    }

    /**
     * 返回哈希表中指定字段的值
     * @param string $key
     * @param string $field
     * @return string
     */
    public function hGet(string $key,string $field){
        return $this->handler->hGet($key,$field);
    }

}