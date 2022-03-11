<?php


namespace app\command;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use rabbit\Client;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Receive extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('receive')
            ->setDescription('rabbitmq 消费队列');
    }
    protected function execute(Input $input, Output $output) {
        // 指令输出
        $output->writeln("RabbitMQ 消费队列开始启动……\n");
        $mqData = Client::instance('test')->rMq(1);

        $output->writeln("RabbitMQ 消费队列完成……\n");
        dump($mqData) ;
    }
}