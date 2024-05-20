<?php

namespace App\Api\Service;
use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class NodeService
{
    use CoroutineSingleTon;

    //$channel 100 微信  108 字节
    //$env 1 测试服  2 预发布  3 正式服
    public function getPlayerList(string $openid):array
    {
        $cacheKey = Keys::getInstance()->getNodeKey($openid);
        $info = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey) {
            return $redis->hGetAll($cacheKey);
        });

        $list = [];
        foreach ($info as $nodeid => $value) 
        {
            $detail = json_decode($value,true);
            $list[] = [
                'serverId' => $nodeid,
                'level'    => $detail['level'],
                'nickname' => $detail['nickname'],
            ];
        }

        return $list;
    }

    public function addMember(string $openid,string $sessionKey):void
    {
        $cacheKey = Keys::getInstance()->getLoginSetKey();
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$openid,$sessionKey) {
            return $redis->hSet($cacheKey,$openid,$sessionKey);
        });
    }

}
