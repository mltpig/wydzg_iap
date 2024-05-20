<?php
namespace App\Api\Controller\Activity\Channel;

use App\Api\Utils\Consts;
use App\Api\Utils\Keys;
use App\Api\Table\ConfigParam;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Controller\BaseController;

class ReceiveReview extends BaseController
{

    public function index()
    {
        $reward         = ConfigParam::getInstance()->getFmtParam('GOOD_REPUTATION_REWARD');
        $five_star_key  =  Keys::getInstance()->getFiveStarKey($this->player->getData('openid'));
        $five           = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($five_star_key){
            return $redis->hget($five_star_key,'status');
        });

        $result = '已领取';
        if(empty($five))
        {
            PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($five_star_key){
                $redis->hset($five_star_key,'status',1);
            });
            $this->player->goodsBridge($reward,'微信五星好评奖励');

            $result = [
                'reward' => $reward,
            ];
        }

        $this->sendMsg( $result );
    }

}