<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 10:25
 */

namespace app\common\task;

/**
 * redis 基础类
 * Trait Base
 * @package redis
 */
abstract class Base
{
    protected $options=[
        'host'       => '127.0.0.1',
        'port'       => '6379',
        'password'   => '',
        'select'     => 1,
        'timeout'    =>  0,
        'expire'     =>  0,
        'persistent' =>  true,
        'prefix'     =>  '',
    ];


    protected $handler;

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->handler = new \Redis();
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 清空整个redis服务器
     */
    public function clearAll(){
        $this->handler->flushAll();
    }

    /**
     * 删除集合
     * @param string $key
     * @return int
     */
    public function delete(string $key){
        $result = $this->handler->del($key);
        return $result;
    }

    /**
     * 返回句柄
     * @return \Redis
     */
    public function handle(){
        return $this->handler;
    }

    /**
     * 检查给定的KEY是否存在
     * @param string $key
     * @return bool
     */
    public function exists(string $key){
        return $this->handler->exists($key);
    }

    /**
     * 设置过期时间
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    public function expire(string $key,int $timeout){
        return $this->handler->expire($key,$timeout);
    }

    /**
     * 查找所有符合给定模式 pattern 的 key
     * @param string $pattern
     * @return array
     */
    public function keys(string $pattern){
        return $this->handler->keys($pattern);
    }

    /**
     * 将key移动到指定的库
     * @param string $key
     * @param int $select
     * @return bool
     */
    public function move(string $key,int $select){
        return $this->handler->move($key,$select);
    }

    /**
     * 重命名key
     * @param string $old_key
     * @param string $new_key
     * @return bool
     */
    public function reName(string $old_key,string $new_key){
        return $this->handler->rename($old_key,$new_key);
    }


}