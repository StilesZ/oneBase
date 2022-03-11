# LuckyBase
php开发框架

# 队列
创建 -> 推送 -> 消费 -> 删除

启动消息队列
> php think queue:work --queue test_job_queue
```
php think queue:work \ 
 --daemon            //是否循环执行，如果不加该参数，则该命令处理完下一个消息就退出
 --queue  helloJobQueue  //要处理的队列的名称
 --delay  0 \        //如果本次任务执行抛出异常且任务未被删除时，设置其下次执行前延迟多少秒,默认为0
 --force  \          //系统处于维护状态时是否仍然处理任务，并未找到相关说明
 --memory 128 \      //该进程允许使用的内存上限，以 M 为单位
 --sleep  3 \        //如果队列中无任务，则sleep多少秒后重新检查(work+daemon模式)或者退出(listen或非daemon模式)
 --tries  2          //如果任务已经超过尝试次数上限，则触发‘任务尝试次数超限’事件，默认为0

```

启动监听处理
> php think queue:listen --queue test_job_queue
 ```
 php think queue:listen \
  --queue  helloJobQueue \   //监听的队列的名称
  --delay  0 \         //如果本次任务执行抛出异常且任务未被删除时，设置其下次执行前延迟多少秒,默认为0
  --memory 128 \       //该进程允许使用的内存上限，以 M 为单位
  --sleep  3 \         //如果队列中无任务，则多长时间后重新检查，daemon模式下有效
  --tries  0 \         //如果任务已经超过重发次数上限，则进入失败处理逻辑，默认为0
  --timeout 60         //创建的work子进程的允许执行的最长时间，以秒为单位
```
