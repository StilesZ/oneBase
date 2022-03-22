<?php

namespace redis;

use think\exception\ThrowableError;

class RedisClient
{

    private $handler = null;
    private static $_instance = null;

    private $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    private function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->handler = new \Redis;
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
    * @return RedisClient|null 对象
    */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
    * 禁止外部克隆
    */
    private function __clone()
    {
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }


    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return (bool)$this->handler->exists($this->getCacheKey($name));
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }

        try {
            $result = 0 === strpos($value, 'think_serialize:') ? unserialize(substr($value, 16)) : $value;
        } catch (\Exception $e) {
            $result = $default;
        }

        return $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name 缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        $key = $this->getCacheKey($name);
        $value = is_scalar($value) ? $value : 'think_serialize:' . serialize($value);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string    $name 缓存变量名
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flushDB();
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function lpop($name, $default = false)
    {
        $value = $this->handler->lpop($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }

        try {
            $result = 0 === strpos($value, 'think_serialize:') ? unserialize(substr($value, 16)) : $value;
        } catch (\Exception $e) {
            $result = $default;
        }

        return $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name 缓存变量名
     * @param mixed             $value  存储数据
     * @return boolean
     */
    public function lpush($name, $value)
    {

        $key = $this->getCacheKey($name);

        $result = $this->handler->lpush($key, $value);

        return $result;
    }

    /**
     * 获取锁
     * @param  String  $key    锁标识
     * @param  Int     $expire 锁过期时间
     * @param  Int     $num    重试次数
     * @return Boolean
     */
    public function lock($key, $expire = 5, $num = 0, $sleep = 1000000)
    {

        $is_lock = $this->handler->setnx($key, time() + $expire);

        if (!$is_lock) {
            //获取锁失败则重试{$num}次
            for ($i = 0; $i < $num; $i++) {

                $is_lock = $this->handler->setnx($key, time() + $expire);

                if ($is_lock) {
                    break;
                }
                sleep(1);
//                usleep(100000);
            }
        }

        // 不能获取锁
        if (!$is_lock) {

            // 判断锁是否过期
            $lock_time = $this->handler->get($key);

            // 锁已过期，删除锁，重新获取
            if (time() > $lock_time) {
                $this->unlock($key);
                $is_lock = $this->handler->setnx($key, time() + $expire);
            }
        }

        return $is_lock ? true : false;

//        if (strlen($key) === 0) {
//
//            // 项目抛异常方法
//            return ThrowableError(500, '缓存KEY没有设置');
//
//        }

//        $lock_key = 'LOCK_PREFIX' . $key;
        // $lock_key
//        $start = self::getMicroTime();
//
//        do {
//            // [1] 锁的 KEY 不存在时设置其值并把过期时间设置为指定的时间。锁的值并不重要。重要的是利用 Redis 的特性。
//            $acquired = $this->handler->setnx($lock_key, 1);
//            if ($acquired) {
//                break;
//            }
//            if ($num === 0) {
//                break;
//            }
//            usleep($sleep);
//        } while (!is_numeric($num) || (self::getMicroTime()) < ($start + ($expire * 1000000)));
//
        // 防止死锁
//        if($this->handler->ttl($lock_key) == -1){
//            $this->handler->expire($lock_key, 5);
//        }
//        return $acquired ? true : false;
    }

    /**
     * 释放锁
     *
     * @param mixed $key 被加锁的KEY。
     * @return void
     */

    public function unlock($key)
    {

        $this->handler->del($key);

    }

    public function release($key)
    {

        if (strlen($key) === 0) {

            // 项目抛异常方法

        }

        $this->handler->del($key);

    }

    public static function getMicroTime()
    {
        return bcmul(microtime(true), 1000000);
    }
}