<?php


namespace app\command;


use ErrorException;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use rabbit\RabbitMQ;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class rabbitMessage extends Command
{
    protected function configure()
    {
        // 指令配置
        $this
            // 命令名称
            ->setName('xxx')
            // 运行 "php think list" 时的简短描述
            ->setDescription('xxx 消费队列');
    }

    /**
     * @param Input  $input
     * @param Output $output
     * @return int|void|null
     * @throws ErrorException
     */
    protected function execute(Input $input, Output $output)
    {
        $rabbitmq   = RabbitMQ::instance();
        $queue_name = 'xxx';

        $dlx_exchange    = 'delay.' . $queue_name . '.exchange';
        $dlx_queue       = 'delay.' . $queue_name . '.queue';
        $dlx_routing_key = 'delay.' . $queue_name . '.routing_key';

        // 创建死信交换机
        $rabbitmq->createExchange($dlx_exchange, 'direct');
        // 创建死信队列
        $rabbitmq->createQueue($dlx_queue, false, true);
        // 绑定交换机、队列、路由
        $rabbitmq->bindQueue($dlx_queue, $dlx_exchange, $dlx_routing_key);
        // 消费消息
        $rabbitmq->consumeMessage($dlx_queue, [$this, 'onReceived']);
    }

    /**
     * 消费者回调方法
     * @param mixed $message 生产者发来的数据
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function onReceived($message)
    {
        $output      = new Output();
        $rabbitmq    = RabbitMQ::instance();
        $queue_name  = 'xxx';
        $exchange    = $queue_name . '.exchange';
        $routing_key = $queue_name . '.routing_key';

        $date_time = date('Y-m-d H:i:s');
        $output->writeln($date_time . ' Received ' . $message->body);

        //确认消息处理完成
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $message_data = json_decode($message->body, true);
//        $status = $device_commands['status'];
//        $error  = $device_commands['request_error'];
        $retry_nums  = 3;
        $msg_headers = $message->get('application_headers')->getNativeData();

        // 重试次数超过指定次数次，则超时
        if (intval($msg_headers['retry_nums']) >= $retry_nums) {
            $output->writeln($date_time . ' xxx下发的指令超时');

        } else {
            $msg = [];
            // 创建重试交换机
            $rabbitmq->createExchange($exchange, 'direct');
            // 放回重试队列
            $rabbitmq->producerMessage(
                $message->body,
                $exchange,
                $routing_key,
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT],
                ['retry_nums' => intval($msg_headers['retry_nums']) + 1]// 重试次数加1
            );
        }
    }
}