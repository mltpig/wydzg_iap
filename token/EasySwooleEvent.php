<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;


use EasySwoole\EasySwoole\Config as GlobalConfig;

use EasySwoole\Pool\Config as PoolConfig;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Redis\Config\RedisConfig;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;

use App\Crontab\CheckToken;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {   
        //测试服
        //redis
        $config      = new PoolConfig();
        $redisConfig = new RedisConfig(GlobalConfig::getInstance()->getConf('REDIS'));
        PoolManager::getInstance()->register(new \App\Pool\RedisPool($config,$redisConfig),'redis');

        //cosr
        Di::getInstance()->set(SysConst::HTTP_GLOBAL_ON_REQUEST,function (Request $request, Response $response){
            $response->withHeader('Access-Control-Allow-Origin', '*');
            $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            if ($request->getMethod() === 'OPTIONS') {
                $response->withStatus(\EasySwoole\Http\Message\Status::CODE_OK);
                return false;
            }
            return true;
        });

    }

    public static function mainServerCreate(EventRegister $register)
    {
        
        // 配置定时任务
        $crontabConfig = new \EasySwoole\Crontab\Config();
        // 1.设置执行定时任务的 socket 服务的 socket 文件存放的位置，默认值为 当前文件所在目录
        // 这里设置为框架的 Temp 目录
        $crontabConfig->setTempDir(EASYSWOOLE_TEMP_DIR);
        // 2.设置执行定时任务的 socket 服务的名称，默认值为 'EasySwoole'
        $crontabConfig->setServerName('EasySwoole');
        // 3.设置用来执行定时任务的 worker 进程数，默认值为 3
        $crontabConfig->setWorkerNum(3);
        // 4.设置定时任务执行出现异常的异常捕获回调
        $crontabConfig->setOnException(function (\Throwable $throwable) {
            // 定时任务执行发生异常时触发（如果未在定时任务类的 onException 中进行捕获异常则会触发此异常回调）
        });
        // 创建定时任务实例
        $crontab = \EasySwoole\EasySwoole\Crontab\Crontab::getInstance($crontabConfig);
        // 注册定时任务
        $crontab->register(new CheckToken());

    }
}