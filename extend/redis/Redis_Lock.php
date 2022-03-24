<?php

/**
 * 如果队列削峰的话请注意 如果本redis队列为空 但是 持久化存储的库存未及时更改依然会造成数据不一致 一定要注意 如果你依赖其他消息队列那么就只能改动本代码队列相关代码实现了 例如队列不为空不給修改库存 毕竟队列消费完成就会落库更改库存
 * 【支持超卖】成功进行回收请求
 * 【支持超卖】失败回收库存 会自动回收请求
 * 支持单独上锁【lock与unlock】 例如操作同一个数据库数据时防止死锁或用来约束秒杀的部分逻辑控制
 * 【注意】key名无论什么时候都要考虑锁粒度问题 对于持久层数据并发上的控制我们只要控制在行级别即可 否则过粗影响并发量 过细可能无效
 */
class RedisLock
{

    /**
     * @var int 锁最大超时时间
     */
    private const LockTimeOut = 1 * 60 * 60;
    /**
     * redis key前缀
     */
    private const REDIS_LOCK_KEY_PREFIX = 'redis:lock:stock:';
    /**
     * @var null
     */
    private static $self = null;
    /**
     * @var Redis
     */
    private $redisObject = null;
    /**
     * @var int 请求队列过期时间 默认10s
     */
    private $reqTimeOut = 10;
    /**
     * @var array
     */
    private $lockedNames = [];
    /**
     * @var array
     */
    private $servers = [];


    private function __construct()
    {
    }

    /**
     * 获取锁对象
     *
     * @param array $servers
     *
     * @return null|RedisLock
     */
    public static function getInstance(array $servers)
    {
        if (!(self::$self instanceof self)) {
            $self = new self();
//            $self->redisObject = $redisObject;
            $self->servers = $servers;
            self::$self = $self;
        }
        return self::$self;
    }

    /**
     * 初始化连接
     *
     * @return null|RedisLock
     */
    private function initInstances()
    {
        if (empty($self->redisObject)) {
            foreach ($this->servers as $server) {
                list($host, $port, $timeout) = $server;
                $redis = new \Redis();
                $redis->connect($host, $port, $timeout);
                $redis->auth('quantred');
//                $this->redisObject[] = $redis;
                $this->redisObject = $redis;
            }
        }
    }

    public function getReqId($key, $count, $stock = -1)
    {
        $clientId = $this->getClientId();

        $this->lock('listQueTimeOut:' . $key);
        $this->listQueTimeOut('list:req:lock:' . $key . ':lock:lua');
        $this->unlock('listQueTimeOut:' . $key);


        //生成请求标识
        $reqId = (string)md5(uniqid(md5(microtime(true)), true)) . ':'
            . $clientId;

        $reqTimeOut = $this->reqTimeOut;

        $script = <<<LUA
			local key = KEYS[1]	--标识
            local value = ARGV[1] --购买的数量
            --设置库存key名
			key = key..':lock:lua'
			value = tonumber(value)
			local stock = ARGV[2] --更新的库存数量
            local keyStock = 0
            
            --定义请求队列数量key名
			local listReqKey = 'list:req:lock:'..key
            
			
			if(stock == nil or stock == '') then
				stock = -1
			else
				stock = tonumber(stock)
			end
			
			--获取当前库存 小于0不在继续 并重置为0
			--[[if(redis.call('exists', listReqKey) == 1)
			then
				if(tonumber(redis.call('get', key)) < 0) then
					redis.call('set', key,0)
					return 0
				else
					keyStock = tonumber(redis.call('get', key))
				end
				
			end]]
			
			
			
			
			local reqId = ARGV[3] --请求id
			local reqTimeOut = ARGV[4] --队列超时时间单位s
			reqTimeOut = tonumber(reqTimeOut)
			
			if(value <= 0)
			then
			    return 0
            end
			
			--先清理过期队列 最多保留24小时 避免永久key存在【修改6.24】
			if(redis.call('exists', listReqKey) == 1)
			then
				redis.call('ZREMRANGEBYSCORE', listReqKey,0,tonumber(redis.call('time')[1]-(24*60*60)))
			end
		
			--【新增续期结束】
			
			
			--获取清理后的队列大小 如果为0则可以更新库存
			local reqListCount =  redis.call('ZCARD', listReqKey)
            
            
            --todo:
            if(redis.call('exists', key) == 1)
			then
				keyStock = tonumber(redis.call('get', key))
			end
			
            if(stock > 0 and reqListCount == 0)
            --if(stock > 0 and (redis.call('exists', key) ~= 1 or keyStock <= 0) and reqListCount == 0)
			then
				--更新库存
				redis.call('set', key,stock)
			end
			
			--如果mysql传过来的为0 那么久将队列与库存全部重置！ 小于0 不做任何操作 标识不更改库存
			if(stock == 0)
			then
				--更新redis库存为0
				redis.call('set', key,stock)
				--直接删除队列
				redis.call('del', listReqKey)
				return 0
			end	
				
			
			
			--判断库存是否充足
			if(redis.call('DECRBY', key,value) < 0)
			then
				--库存不足加回去
				redis.call('INCRBY', key,value)
				return 0
			else
				
				--再次校验 如果小于0返回失败 并 重置redis中的数量为0
				--[[local checkStock = redis.call('get', key)
				if(checkStock == nil or checkStock == '') then
					redis.call('set', key,0)
					return 0
				else
					if(tonumber(checkStock) < 0) then
						redis.call('set', key,0)
						return 0
					end
				end]]
				
				
				--往队列中放入标识
				redis.call('ZADD', listReqKey,tonumber(redis.call('time')[1])+reqTimeOut,reqId)
				--库存充足
				return reqId
			end
			
LUA;
        return $this->retrunClass($count, $this->execLuaScript($script,
            [$key, $count, $stock, $reqId, $reqTimeOut]), $key);
    }

    /**
     * 获取客户端id
     *
     * @return mixed
     *
     */
    public function getClientId()
    {
        return $this->redisObject->client('id');
    }

    /**
     * 上锁
     *
     * @param string $name       锁名字
     * @param int    $expire     锁有效期 秒 / 最大续期时间/程序最大运行时长 默认1小时
     * @param int    $retryTimes 重试次数
     * @param int    $sleep      重试休息微秒
     *
     * @return mixed
     */
    public function lock(
        string $name,
        int $expire = self::LockTimeOut,
        int $retryTimes = 10,
        $sleep = 10000
    ) {
        $clientId = $this->getClientId();
        $oj8k = false;
        $retryTimes = max($retryTimes, 1);
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        while ($retryTimes-- > 0) {
            $kVal = microtime(true) + $expire;
            $kVal = (string)$kVal . ':' . $clientId;
            $oj8k = $this->getLock($key, $expire, $kVal);//上锁
            if ($oj8k) {
                $this->lockedNames[$key] = $kVal;
                break;
            }
            usleep($sleep);
        }
        return $oj8k;
    }

    /**
     * 获取锁
     *
     * @param $key
     * @param $expire
     * @param $value
     *
     * @return mixed
     */
    private function getLock($key, $expire, $value)
    {
        $valueR = $this->redisObject->GET($key);
        if (!empty($valueR)) {
            $clientId = explode(':', $valueR)[1];
            if ($this->getClientIsConn((int)$clientId) == false) {
                $this->redisObject->del($key);
            } else {
                $ttlKey = $this->redisObject->ttl($key);
                if ($ttlKey > 0) {
                    $this->redisObject->expire($key, self::LockTimeOut);
                }
            }
        }

        $script = <<<LUA
            local key = KEYS[1]
            local value = ARGV[1]
            local ttl = ARGV[2]
            if (redis.call('setnx', key, value) == 1) then
                return redis.call('expire', key, ttl)
            elseif (redis.call('ttl', key) == -1) then
                return redis.call('expire', key, ttl)
            end
            
            return 0
LUA;
        return $this->execLuaScript($script, [$key, $value, $expire]);
    }

    /**
     *  获取指定客户端id是否存在
     *
     * @param $clientId
     *
     * @return bool
     */
    public function getClientIsConn($clientId)
    {
        if ((int)$this->redisObject->rawCommand('CLIENT', 'TRACKING', 'off',
                'REDIRECT', (int)$clientId) == 1
        ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 执行lua脚本
     *
     * @param string $script
     * @param array  $params
     * @param int    $keyNum
     *
     * @return mixed
     */
    private function execLuaScript($script, array $params, $keyNum = 1)
    {
        $hash = $this->redisObject->script('load', $script);
        return $this->redisObject->evalSha($hash, $params, $keyNum);
        //return $this->redisObject->eval($script,$params, $keyNum);
    }


    /**
     * 获取指定时间内过期的队列并进行链接活跃校验以及清理断开的链接
     *
     * @param $key
     */
    public function listQueTimeOut($key)
    {
        $reqTimeOutList = $this->redisObject->ZRANGEBYSCORE($key, 0, time());
        foreach ($reqTimeOutList as $item) {
            $clientId = explode(':', $item)[1];
            if ($this->getClientIsConn((int)$clientId) == false) {
                $this->redisObject->ZREM($key, $item);
            } else {
                //对值过期但是连接依然存在的情况处理 可能因为连接池或被重复分配id 异常未回收可能会被断开没问题
                //【可能过期需要续期【业务超期执行中】、可能连接池复用（能被复用回去应该是正常执行结束 问题不大）、可能重复分配相同id（一般redis实例重启可能会出现重复）TODO:这种特殊情况一般可以考虑做个最大续期计数避免无限续期】

                //业务超时进行续期 避免每次都在这里处理一次
                $this->redisObject->ZADD($key, time() + $this->reqTimeOut,
                    $item);
            }
        }
    }

    //设置连接名称

    //获取链接名称


    /**
     * 解锁
     *
     * @param string $name
     *
     * @return mixed
     */
    public function unlock(string $name)
    {
        $script = <<<LUA
            local key = KEYS[1]
            local value = ARGV[1]
            if (redis.call('exists', key) == 1 and redis.call('get', key) == value) 
            then
                return redis.call('del', key)
            end
            return 0
LUA;
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        if (isset($this->lockedNames[$key])) {
            $val = $this->lockedNames[$key];
            return $this->execLuaScript($script, [$key, $val]);
        }
        return false;
    }


    /**
     * @param $count
     * @param $reqId
     * @param $key  string 标识 count购买的数量 stock更新库  更新库存频率自行上锁控制
     *
     * @return RedisLock|__anonymous@8648|null
     * 获取请求id >0 成功就满足 不成功就是库存不足
     * 可自动更新库存
     *
     */
    private function retrunClass($count, $reqId, $key)
    {
        if (empty($reqId)) {
            return null;
        }
        $key = $key . ':lock:lua';

        return new class($this->redisObject, $count, $reqId, $key) extends
            RedisLock {
            private $redisObject;//redis对象
            private $count;//购买的数量
            private $reqId;//请求id
            private $key;//标识

            public function __construct($redisObject, $count, $reqId, $key)
            {
                $this->redisObject = $redisObject;
                $this->count = $count;
                $this->reqId = $reqId;
                $this->key = $key;
            }

            /**
             * 执行lua脚本
             *
             * @param string $script
             * @param array  $params
             * @param int    $keyNum
             *
             * @return mixed
             */
            private function execLuaScript($script, array $params, $keyNum = 1)
            {
                $hash = $this->redisObject->script('load', $script);
                return $this->redisObject->evalSha($hash, $params, $keyNum);
            }


            //回收请求
            public function recoveryReqId()
            {
                return $this->redisObject->zRem("list:req:lock:" . $this->key,
                    $this->reqId);
            }

            //回收库存并自动回收请求【安全回收库存】

            /**
             * 失败返回0 成功返回当前库存数量
             */
            public function recoveryStock()
            {
                //如果在redis更新库存的时候回滚了 会造成多的 那么怎么解决？
                //答：通过lua脚本 判断是否存在请求 存在 在回滚 否则不进行回滚 上面lua脚本在redis库存为0的时候 队列为空的时候 会更新库存 并清空队列 那么这里必须保证 存在队列
                //1.如果该请求被更新库存的时候清除了 那么表示库存已经标准了 那么回滚不在有意义 所以不用回滚库存
                //2.如果存在请求 进行回滚 表示需要回滚当前库存 因为这一波库存属于该请求的 它才可以被回滚
                //结论：某一个请求回滚库存时判断当前redis库存是否属于自己的 才有权利回滚库存 如果该请求超时 那么也不允许其进行更新库存了因为被超时剔除了
                //就算多次调用 也只会回滚一次保证安全
                $script = <<<LUA
            local key = KEYS[1]
            local reqId = ARGV[1]
            local count = ARGV[2]
            --设置库存key名
			--key = key..':lock:lua'
            --定义队列key
			local listReqKey = 'list:req:lock:'..key
            if (redis.call('exists', listReqKey) == 1) 
            then
                --如果请求队列是否存在  尝试删除请求id 删除成功就回滚库存 删除失败就是不存在 不进行回滚库存
                if(redis.call('zrem',listReqKey,reqId) == 1)
                then
                    return redis.call('INCRBY', key,count)
                else
                    return 0
                end
            end
            return 0
LUA;

                return $this->execLuaScript($script,
                    [$this->key, $this->reqId, $this->count]);
            }

        };
    }

    public function releasBatchReq(array $reqObj)
    {
        foreach ($reqObj as $v) {
            if (gettype($v) == 'object') {
                if (method_exists($v, 'recoveryReqId')) {
                    $v->recoveryReqId();
                }
            }
        }
    }



    //手动释放请求 根据请求id [不建议使用 该方法]
    // public function releaseReqList($key,$reqId){
    //     $this->redisObject->zRem("list:req:lock:".$key,$reqId);
    // }

    //批量回收请求

    public function releasBatchStock(array $reqObj)
    {
        foreach ($reqObj as $v) {
            if (gettype($v) == 'object') {
                if (method_exists($v, 'recoveryStock')) {
                    $v->recoveryStock();
                }
            }
        }
    }

    //批量回收库存 会自动回收库存 【安全回收库存】

    /**
     * 获取锁并执行
     *
     * @param callable $func
     * @param string   $name
     * @param int      $expire
     * @param int      $retryTimes
     * @param int      $sleep
     *
     * @return bool
     * @throws \Exception
     */
    public function run(
        callable $func,
        string $name,
        int $expire = 5,
        int $retryTimes = 10,
        $sleep = 10000
    ) {
        if ($this->lock($name, $expire, $retryTimes, $sleep)) {
            try {
                call_user_func($func);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $this->unlock($name);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    private function __clone()
    {
    }


}


/**
 * 用法示例
 */

//$redis_p = Cache::store('redis')->handler();
//$redisLock = \RedisLock::getInstance($redis_p);
//
//$pdo = new PDO('mysql:host=127.0.0.1;dbname=testredis', 'testredis', 'S3hCHcpJZHxFe8AH');
//
//
//
//$goodsId = $_GET['goodsId'];//产品id
//$key = 'goods:'.$goodsId;
//$count = $_GET['count'];//购买量
//
//
// 设置库存 判断库存锁 坐等超时即可 不用解锁 限制更新频率
//$oj8k = $redisLock->lock($key, 5,10);
//$number=-1;
//if ($oj8k) {
//   //允许设置库存 进行获取库存
//    $sql="select `number` from  storage where goodsId={$goodsId} limit 1";
//
//    $res = $pdo->query($sql)->fetch();
//    $number = $res['number'];
//}
//
//获取请求id
//$reqid = $redisLock->getReqId($key,$count,$number);
//if(empty($reqid)){
//	exit('库存不足');
//}

//----------------------业务代码-------------------------
//查看库存
//$sql="select `number` from  storage where goodsId={$goodsId} limit 1";
//$res = $pdo->query($sql)->fetch();
//$number = $res['number'];
//if($number>0)
//{
//
//$createTime = date('Y-m-d H:i:s');
//    $sql ="insert into `order`  VALUES ('',$number,'{$createTime}')";
//    $order_id = $pdo->query($sql);
//    if($order_id)
//    {
//        $sql="update storage set `number`=`number`-$count WHERE goodsId={$goodsId}";
//        if($pdo->query($sql)){
//            var_dump($reqid->recoveryReqId());//手动回收请求
//        }else{
//
//            var_dump($reqid->recoveryStock());//手动回收库存
//        }
//    }
//
//
//
//
//    // var_dump($reqid->recoveryStock());//手动回收库存
//    // var_dump($reqid->recoveryReqId());//手动回收请求
//
//
//    //批量回收请求
//    // $reqid->releasBatchReq([$reqid]);
//
//    //批量回库存
//    // $reqid->releasBatchStock([$reqid]);
//
//
//
//
//
//
//
//
//    echo 'done';
//}