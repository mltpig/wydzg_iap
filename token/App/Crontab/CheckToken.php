<?php
namespace App\Crontab;
use EasySwoole\EasySwoole\Task\TaskManager;
use \EasySwoole\Crontab\JobInterface;
use App\Api\Service\WeixinService;

class CheckToken implements JobInterface
{
    public function crontabRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        return '* * * * *';
    }

    public function jobName(): string
    {
        // 定时任务的名称
        return 'token有效期坚持';
    }

    public function run()
    {
        //开发者可投递给task异步处理
        TaskManager::getInstance()->async(function (){
            WeixinService::getInstance()->checkToken();
        });
        
    }

    public function onException(\Throwable $throwable)
    {
        // 捕获run方法内所抛出的异常
    }
}