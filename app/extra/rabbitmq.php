<?php
// rabbitmq配置
return [

    'rabbit_mq' => [
        'host' => 'localhost',
        'port' => 5672,
        'user' => 'guest',
        'pwd' => 'guest',
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
    ]

];