<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;


use EasySwoole\EasySwoole\Config as GlobalConfig;

use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Config as DbConfig;
use EasySwoole\ORM\Db\Connection;

use EasySwoole\Pool\Config as PoolConfig;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Redis\Config\RedisConfig;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;

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

    }
}