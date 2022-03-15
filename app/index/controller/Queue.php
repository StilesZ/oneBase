<?php


namespace app\index\controller;


use PhpAmqpLib\Message\AMQPMessage;
use rabbit\RabbitMQ;

class Queue extends IndexBase
{

    // 延时结算  RabbitMQ
    public function message(){
        // 延时结算
        $rabbitmq           = RabbitMQ::instance();
        $queue_name         = 'xxx';
        $exchange           = $queue_name . '.exchange';
        $queue              = $queue_name . '.queue';
        $routing_key        = $queue_name . '.routing_key';

        $dlx_exchange       = 'delay.' . $exchange;
        $dlx_queue          = 'delay.' . $queue;
        $dlx_routing_key    = 'delay.' . $routing_key;

        // 死信交换器
        $rabbitmq->createExchange($dlx_exchange, 'direct');
        // 死信队列
        $rabbitmq->createQueue($dlx_queue, false, true);
        // 绑定交换机、队列、路由key
        $rabbitmq->bindQueue($dlx_queue, $dlx_exchange, $dlx_routing_key);

        // 延迟交换机器
        $rabbitmq->createExchange($exchange, 'direct');

        $arguments = [
            'x-message-ttl'             => 1000,// 延迟时间（毫秒）队列中的消息1秒之后过期
            'x-dead-letter-exchange'    => $dlx_exchange,// 延迟结束后指向交换机（死信收容交换机）
            'x-dead-letter-routing-key' => $dlx_routing_key,// 延迟结束后指向队列（死信收容队列），可直接设置queue name也可以设置routing-key
        ];

        // 延迟队列
        $rabbitmq->createQueue($queue, false, true, false, false, false, $arguments);
        // 绑定交换机、队列、路由
        $rabbitmq->bindQueue($queue, $exchange, $routing_key);

        // 消息入队
        $rabbitmq->producerMessage(
            json_encode('message', JSON_UNESCAPED_UNICODE),
            $exchange,
            $routing_key,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT],
            ['retry_nums' => 1]// 设置重试次数
        );
    }
}