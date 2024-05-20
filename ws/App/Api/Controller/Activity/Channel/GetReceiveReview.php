<?php
namespace App\Api\Controller\Activity\Channel;

use App\Api\Utils\Consts;
use App\Api\Utils\Keys;
use App\Api\Table\ConfigParam;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Controller\BaseController;

class GetReceiveReview extends BaseController
{

    public function index()
    {
        $five_star_key  =  Keys::getInstance()->getFiveStarKey($this->player->getData('openid'));
        $five           = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($five_star_key){
            $redis->hIncrBy($five_star_key,'config',1);
            return $redis->hgetall($five_star_key);
        });

        $result = [
            'five' => [
                'config' => $five['config'] + 0,
                'status' => array_key_exists('status',$five) ? $five['status'] : 0,
            ]
        ];

        $this->sendMsg( $result );
    }

}