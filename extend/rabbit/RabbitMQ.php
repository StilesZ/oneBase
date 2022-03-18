<?php
declare(strict_types=1); // 设置强类型

namespace rabbit;


use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQ
{

    static private $instance;

    private $host = '127.0.0.1';
    private $port = 5672;
    private $user = 'guest';
    private $password = 'guest';
    private $vhost = '/';
    protected $connection;
    protected $channel;

    /**
     * RabbitMQ constructor.
     * @param array $config
     */
    private function __construct(array $config = [])
    {
        !empty($config['host']) && $this->host = $config['host'];
        !empty($config['port']) && $this->host = $config['port'];
        !empty($config['user']) && $this->host = $config['user'];
        !empty($config['password']) && $this->host = $config['password'];
        !empty($config['vhost']) && $this->host = $config['vhost'];

        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password,
            $this->vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * 实例化
     * @param array $config
     * @return RabbitMQ
     */
    public static function instance(array $config = [])
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 防止被外部复制
     */
    private function __clone()
    {
    }

    /**
     * 获取信道
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * 监听 confirm
     * @return AMQPChannel
     */
    public function setConfirm()
    {
        //推送成功
        $this->channel->set_ack_handler(
            function (AMQPMessage $message) {
                echo 'Message acked ' . $message->body . PHP_EOL;
            }
        );

        //推送失败
        $this->channel->set_nack_handler(
            function (AMQPMessage $message) {
                echo 'Message nacked ' . $message->body . PHP_EOL;
            }
        );

        $this->channel->confirm_select();
    }

    /**
     * 声明一个交换器
     * @param string $exchangeName 名称
     * @param string $type 交换机类型 ：直连交换机（direct） 主题交换机 （topic） 头交换机（headers） 扇交换机（fanout）
     * @param bool $pasive 是否检测同名队列
     * @param bool $durable 是否开启队列持久化
     * @param bool $autoDelete 通道关闭后是否删除队列
     */
    public function createExchange(
        string $exchangeName,
        string $type,
        bool $pasive = false,
        bool $durable = false,
        bool $autoDelete = false
    ) {
        $this->channel->exchange_declare($exchangeName, $type, $pasive, $durable, $autoDelete);
    }

    /**
     * 创建队列
     * @param string $queueName
     * @param bool $pasive
     * @param bool $durable
     * @param bool $exlusive
     * @param bool $autoDelete
     * @param bool $noWait
     * @param array $arguments
     * @return mixed|null
     */
    public function createQueue(
        string $queueName,
        bool $pasive = false,
        bool $durable = false,
        bool $exlusive = false,
        bool $autoDelete = false,
        bool $noWait = false,
        array $arguments = []
    ) {
        $args = [];
        if (!empty($arguments)) {
            $args = new AMQPTable();
            foreach ($arguments as $key => $value) {
                $args->set($key, $value);
            }
        }

        return $this->channel->queue_declare($queueName, $pasive, $durable, $exlusive, $autoDelete, $noWait, $args);
    }

    /**
     * 绑定队列
     * @param string $queue
     * @param string $exchangeName
     * @param string $routeKey
     * @param bool $noWait
     * @param array $arguments
     * @param int|null $ticket
     */
    public function bindQueue(
        string $queue,
        string $exchangeName,
        string $routeKey = '',
        bool $noWait = false,
        array $arguments = [],
        $ticket = null
    ) {
        $this->channel->queue_bind($queue, $exchangeName, $routeKey, $noWait, $arguments, $ticket);
    }

    /**
     * 生成信息
     * @param string $message 消息体
     * @param string $exchange 交换机
     * @param string $routeKey 路由key
     * @param array $properties 属性
     * @param array $headers
     */
    public function producerMessage(
        string $message,
        string $exchange,
        string $routeKey,
        array $properties = [],
        array $headers = []
    ) {
        $data = new AMQPMessage($message, $properties);

        if (!empty($headers)) {
            $application_headers = new AMQPTable($headers);
            $data->set('application_headers', $application_headers);
        }

        $this->channel->basic_publish($data, $exchange, $routeKey);
    }

    /**
     * 消费消息
     * @param string $queueName
     * @param        $callback
     * @param string $tag
     * @param bool $noLocal
     * @param bool $noAck
     * @param bool $exclusive
     * @param bool $noWait
     * @throws ErrorException
     */
    public function consumeMessage(
        string $queueName,
        $callback,
        string $tag = '',
        bool $noLocal = false,
        bool $noAck = false,
        bool $exclusive = false,
        bool $noWait = false
    ) {
        //只有consumer已经处理并确认了上一条message时queue才分派新的message给它
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, $tag, $noLocal, $noAck, $exclusive, $noWait, $callback);
//        while ($this->channel->is_consuming()) {
        $this->channel->wait();
//        }
    }

    /**
     * 关闭通道及链接
     * @throws Exception
     */
    public function __destruct()
    {
        //关闭通道
        $this->channel->close();
        //关闭链接
        $this->connection->close();
    }
}