<?php

//use redis\RedisLock;

//require_once __DIR__ . '/../extend/redis/RedisLock.php';
require_once __DIR__ . '/../extend/redis/Redis_Lock.php';

$servers = [
    ['127.0.0.1', 6379, 0.01],
//    ['127.0.0.1', 6389, 0.01],
//    ['127.0.0.1', 6399, 0.01],
];

//$redLock = new RedisLock($servers);
//
//$lock = $redLock->lock('test', 10000);
//
//if ($lock) {
//    print_r($lock);
////        sleep(1);
////        $redLock->unlock($lock);
//} else {
//    print "Lock not acquired\n";
//}

//use think\Cache;

//$redis_p = Cache::store('redis')->handler();
$redisLock = \RedisLock::getInstance($servers);

$pdo = new PDO('mysql:host=127.0.0.1;dbname=testredis', 'root', '123456');

$goodsId = $_GET['goodsId'];//产品id
$key = 'goods:'.$goodsId;
$count = $_GET['count'];//购买量


// 设置库存 判断库存锁 坐等超时即可 不用解锁 限制更新频率
$oj8k = $redisLock->lock($key, 5,10);
$number=-1;
if ($oj8k) {
   //允许设置库存 进行获取库存
    $sql="select `number` from  storage where goodsId={$goodsId} limit 1";

    $res = $pdo->query($sql)->fetch();
    $number = $res['number'];
}

//获取请求id
$reqid = $redisLock->getReqId($key,$count,$number);
if(empty($reqid)){
	exit('库存不足');
}

//----------------------业务代码-------------------------
//查看库存
$sql="select `number` from  storage where goodsId={$goodsId} limit 1";
$res = $pdo->query($sql)->fetch();
$number = $res['number'];
if($number>0)
{

$createTime = date('Y-m-d H:i:s');
    $sql ="insert into `order`  VALUES ('',$number,'{$createTime}')";
    $order_id = $pdo->query($sql);
    if($order_id)
    {
        $sql="update storage set `number`=`number`-$count WHERE goodsId={$goodsId}";
        if($pdo->query($sql)){
            var_dump($reqid->recoveryReqId());//手动回收请求
        }else{

            var_dump($reqid->recoveryStock());//手动回收库存
        }
    }

    // var_dump($reqid->recoveryStock());//手动回收库存
    // var_dump($reqid->recoveryReqId());//手动回收请求


    //批量回收请求
    // $reqid->releasBatchReq([$reqid]);

    //批量回库存
    // $reqid->releasBatchStock([$reqid]);

    echo 'done';
}