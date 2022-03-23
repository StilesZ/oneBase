<?php


namespace app\index\controller;


use redis\RedisClient;

class Redis
{

    public function index()
    {
        $redis = new RedisClient();
        $lock = $redis->lock('index', 5, 0);
        if($lock){

        }
    }
}