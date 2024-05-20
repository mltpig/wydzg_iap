<?php
namespace App\Api\Controller\LifetimeCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\LifetimeCardService;

class Apply extends BaseController
{

    public function index()
    {
        $result = '不可重复使用';
        if(empty($this->player->getArg(Consts::LIFETTIME_CARD_TIME)))
        {
            $result = '道具不足';
            if($this->player->getGoods(100002) >=  1)
            {
                $this->player->setArg(Consts::LIFETTIME_CARD_TIME,time(),'reset');

                $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => 100000,'num' => 1980 ];
                $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => 100002,'num' => -1 ];
                $this->player->goodsBridge($reward,'使用终身卡道具');

                if(empty($this->player->getArg(Consts::LIFETIME_CARD_STATE)))
                {
                    LifetimeCardService::getInstance()->lifetimeCardEmail($this->player);
                }

                $result = [
                    'lifetimeCard' => LifetimeCardService::getInstance()->getLifetimeCardFmtData($this->player),
                    'reward'      => [[ 'type' => GOODS_TYPE_1,'gid' => 100000,'num' => 1980 ]],
                    'remain'      => $this->player->getGoods(100002),
                ];
            }
        }
        $this->sendMsg( $result );
    }

}