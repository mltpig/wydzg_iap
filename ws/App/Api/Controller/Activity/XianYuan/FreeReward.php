<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigFund;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\FundService;

class FreeReward extends BaseController
{

    public function index()
    {
        $cost = ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_FREE_REWARD');

        $result = '领取次数已达上限';
        if(empty($this->player->getArg(Consts::XIANYUAN_GIFT_FREE_REWARD)))
        {
            $this->player->setArg(Consts::XIANYUAN_GIFT_FREE_REWARD,1,'reset');

            $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => $cost['num'] ] ];
            $this->player->goodsBridge($reward,'仙缘礼包免费领取奖励');
            $result = [
                'xianyuan'  => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                'reward'    => $reward,
            ];
        }

        $this->sendMsg( $result );
    }

}