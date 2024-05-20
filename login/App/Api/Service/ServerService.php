<?php

namespace App\Api\Service;
use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class ServerService
{
    use CoroutineSingleTon;

    //$channel 100 微信  108 字节
    //$env 1 测试服  2 预发布  3 正式服
    public function getList():array
    {
        $cacheKey = Keys::getInstance()->getNodeListKey();
        $openTime = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey) {
            return $redis->hGetAll($cacheKey);
        });

        return [
            [
                "serverId"      => 1,
                "labelName"     => "桃园",
                "serverName"    => "桃园1服",
                "address"       => "wss://wydzg.shenzhenyuren.com/wx/",
                "serverState"   => 3,
                "openTime"      => isset($openTime[1]) ? $openTime[1] : time(),
                "day"           => $this->getDay($openTime[1]),
                "new"           => false
            ],
            [
                "serverId"      => 2,
                "labelName"     => "桃园",
                "serverName"    => "桃园2服",
                "address"       => "wss://wydzg.shenzhenyuren.com/wx/",
                "serverState"   => 3,
                "openTime"      => isset($openTime[2]) ? $openTime[2] : time(),
                "day"           => $this->getDay($openTime[2]),
                "new"           => true
            ],
        ];

    }

    public function getDay($openServerTime)
    {
        if(!isset($openServerTime)) $openServerTime = time();
        $openServerTime   = strtotime(date('Y-m-d',$openServerTime));
        $currentTimestamp = strtotime(date('Y-m-d',time()));
        $timeElapsed        = $currentTimestamp - $openServerTime;
        return $timeElapsed / 86400 + 1;
    }
}
