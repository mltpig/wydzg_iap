<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigFund;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\FundService;

class TaskReward extends BaseController
{

    public function index()
    {
        $cost = ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_PAID_REWARD');
        
        $result = '领取次数已达上限';
        if(empty($this->player->getArg(Consts::XIANYUAN_GIFT_REWARD)))
        {
            $result = '请购买礼包';
            if($this->player->getArg(Consts::XIANYUAN_GIFT_SCHEDULE) >=  ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_PAID_REWARD_LIMIT'))
            {
                $this->player->setArg(Consts::XIANYUAN_GIFT_REWARD,1,'reset');

                $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => $cost['num'] ] ];
                $this->player->goodsBridge($reward,'仙缘礼包领取购买礼包奖励');
                $result = [
                    'xianyuan'  => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                    'reward'    => $reward,
                ];
            }
        }

        $this->sendMsg( $result );
    }

}