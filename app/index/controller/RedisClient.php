<?php

namespace app\index\controller;

use think\cache\driver\Redis;

class RedisClient extends IndexBase
{

    public function init(){
        $redis = new Redis([
            'host'      => '127.0.0.1',
            'port'      => 6379,
            'password'  => 'quantred',
        ]);

        $redis->lpush('dasd','dasdas');

//        $nx = $redis->handler()->setnx('dasdaaa',30);
        $hhh = $redis->handler()->lpop('dasd');
        echo $hhh;
    }
}