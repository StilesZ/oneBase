<?php
// rabbitmq配置
return [

    'rabbit_mq' => [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'test',
        'pwd' => '123',
        'vhost' => '/',
        'heartbeat' => 30,                  //心跳
        'rabbit_mq_queue' => [
            'test' => [
                'exchange_name' => 'ex_test', // 交换机名称
                'queue_name' => 'que_test', // 队列名称
                'process_num' => 3, // 默认单台机器的进程数量
                'deal_num' => '50', // 单次处理数量
                'consumer' => 'DealTest' // 消费地址
            ]
        ]
    ],

    'localhost' => [                                //连接名称
        "conn" => [
            'host' => '127.0.0.1',             //连接地址
            'vhost' => '/',                     //管理，这玩意儿相当于mysql的数据库
            'port' => 5672,                      //端口
            'login' => 'guest',                //账号
            'password' => 'guest',               //密码
            'heartbeat' => 30,                  //心跳
        ],
        "queue"=>[
            "collectorder.route"=>[                     //路由 key
                'exchange'=>'collectorder.exchange',  //交换区名称
                'queue'=>'collectorder.queue',         //队列名
                'exchangeType'=>'direct',  //交换机类型
                'exchangeFlags'=>2,         //交换机标志
                'queueFlags'=>2             //队列标志
            ]
        ]
    ]

];