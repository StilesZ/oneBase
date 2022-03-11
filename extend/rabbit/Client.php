<?php

namespace rabbit;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use think\Config;

class Client
{
    /**
     * User: yuzhao
     * @var
     * Description:
     */
    private $channel;

    private $mqConf;

    /**
     * RabbitMQTool constructor.
     * @param $mqName
     */
    public function __construct($mqName= 'test')
    {
        // 获取rabbitmq所有配置
        $rabbitMqConf = Config::get('rabbitmq.rabbit_mq');
        if (!isset($rabbitMqConf['rabbit_mq_queue'])) {
            die('没有定义Source.rabbit_mq');
        }
        //建立生产者与mq之间的连接
        $this->conn = new AMQPStreamConnection(
            $rabbitMqConf['host'], $rabbitMqConf['port'], $rabbitMqConf['user'], $rabbitMqConf['pwd'], $rabbitMqConf['vhost']
        );
        $channal = $this->conn->channel();
        if (!isset($rabbitMqConf['rabbit_mq_queue'][$mqName])) {
            die('没有定义'.$mqName);
        }
        // 获取具体mq信息
        $mqConf = $rabbitMqConf['rabbit_mq_queue'][$mqName];
        $this->mqConf = $mqConf;
        // 声明初始化交换机
        $channal->exchange_declare($mqConf['exchange_name'], 'direct', false, true, false);
        // 声明初始化一条队列
        $channal->queue_declare($mqConf['queue_name'], false, true, false, false);
        // 交换机队列绑定
        $channal->queue_bind($mqConf['queue_name'], $mqConf['exchange_name']);
        $this->channel = $channal;
    }

    /**
     * User: yuzhao
     * @param $mqName
     * @return Client
     * Description: 返回当前实例
     */
    public static function instance($mqName) {
        return new Client($mqName);
    }

    /**
     * User: yuzhao
     * @param $data
     * Description: 写mq
     * @return bool
     */
    public function wMq($data) {
        try {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $msg = new AMQPMessage($data, ['content_type' => 'text/plain', 'delivery_mode' => 2]);
            $this->channel->basic_publish($msg, $this->mqConf['exchange_name']);
        } catch (\Throwable $e) {
            $this->closeConn();
            return false;
        }
        $this->closeConn();
        return true;
    }

    /**
     * User: yuzhao
     * @param int $num
     * @return array
     * Description:
     * @throws \ErrorException
     */
    public function rMq($num=1) {
        $rData = [];
        $callBack = function ($msg) use (&$rData){
            $rData[] = json_decode($msg->body, true);
        };
        for ($i=0;$i<$num;$i++) {
            // 一个消费者只能获取一个消息队列中的消息，保持消息的完整性和安全性。防止一个消费者获取多个消息，在处理的过程中宕机导致数据丢失
//            $this->channel->basic_qos(null, 1, null);
            $this->channel->basic_consume($this->mqConf['queue_name'], '', false, true, false, false, $callBack);
        }
        $this->channel->wait();
        $this->closeConn();
        return $rData;
    }

    /**
     * User: yuzhao
     * Description: 关闭连接
     */
    public function closeConn() {
        $this->channel->close();
        $this->conn->close();
    }

}