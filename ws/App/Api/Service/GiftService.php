<?php

namespace App\Api\Service;
use App\Api\Model\ManageGift;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class GiftService
{
    use CoroutineSingleTon;

    public function getGiftData(string $giftId):array
    {
        $reward = $this->getGiftReward($giftId);

        $result = [];
        foreach ($reward as $key => $value) {
            $result[] = array(
                'type'  => $value['type'],
                'gid'   => $value['gid'],
                'num'   => $value['num'],
            );
        }
        return $result;
    }

    public function getGiftReward(string $giftId):array
    {
        $cache = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($giftId) {
            return $redis->hget('manage:gift',$giftId);
        });

		if($cache) return json_decode($cache,true);

		$data  = ManageGift::create()->get(['gift_id' => $giftId ])->toArray();
        if(!$data) return [];

        $reward = $data['reward'];
        $cache  = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($giftId,$reward) {
            return $redis->hset('manage:gift',$giftId,$reward);
        });

        return json_decode($reward,true);
    }

}
