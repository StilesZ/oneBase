
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

print('value=',value)
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

--如果mysql传过来的为0 那么就将队列与库存全部重置！ 小于0 不做任何操作 标识不更改库存
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