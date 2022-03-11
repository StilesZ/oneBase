## RabbitMQ安装

进入安装目录 /sbin 输入命令 rabbitmq-plugins enable rabbitmq_management 命令进行安装

输入 rabbitmqctl status 查看状态

打开sbin目录，双击rabbitmq-server.bat

等几秒钟看到这个界面后，访问http://localhost:15672

## 安装 amqp拓展
http://pecl.php.net/package/amqp/1.11.0/windows

复制php_amqp.dll到php/ext 开启拓展extension

复制rabbitmq.4.dll到php目录

composer require php-amqplib/php-amqplib