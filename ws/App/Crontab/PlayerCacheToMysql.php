<?php
namespace App\Crontab;
use EasySwoole\EasySwoole\Task\TaskManager;
use \EasySwoole\Crontab\JobInterface;

use App\Api\Model\Player;
use EasySwoole\Redis\Redis;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Pool\Manager as PoolManager;

class PlayerCacheToMysql implements JobInterface
{
    public function crontabRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        return '0 0 * * *';
    }

    public function jobName(): string
    {
        // 定时任务的名称
        return '玩家数据入库';
    }

    public function run()
    {
        //开发者可投递给task异步处理
        TaskManager::getInstance()->async(function (){
            PoolManager::getInstance()->get('redis')->invoke(function(Redis $redis) {
                $num   = 0;
                $fail  = array('not_found' => array(),'update_fail'=>array());
                while ($playerKey = $redis->spop(USER_SET)) 
                {
                    
                    if($userInfo = $redis->hgetall($playerKey))
                    {
                        $user = json_decode($userInfo['user'],true);
                        $userInfo['nickname'] = $user['nickname'];
                        if(Player::create()->update($userInfo,['roleid' => $userInfo['roleid']]))
                        {
                            $num++;
                            $redis->unlink($playerKey);
                        }else{
                            $fail['update_fail'][] = $playerKey;
                        }
                    }else{
                        $fail['not_found'][] = $playerKey;
                    }
    
                }
                Logger::getInstance()->waring(' 成功更新日活 ： '.$num.' === 失败 ： '.json_encode($fail));
            });
        });

    }

    public function onException(\Throwable $throwable)
    {
        // 捕获run方法内所抛出的异常
    }
}