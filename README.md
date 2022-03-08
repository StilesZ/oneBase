# LuckyBase
php开发框架

# 队列
创建 -> 推送 -> 消费 -> 删除

启动消息队列
> php think queue:work --queue test_job_queue

启动监听处理
> php think queue:listen --queue test_job_queue