<?php
namespace App\Api\Controller\MonthlyCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MonthlyCardService;

class Apply extends BaseController
{

    public function index()
    {
        $result = '道具不足';
        if($this->player->getGoods(100001) >=  1)
        {
            $expiration_time = $this->player->getArg(Consts::MONTHLY_CARD_TIME);
            if(empty($expiration_time)) $expiration_time = time();

            $this->player->setArg(Consts::MONTHLY_CARD_TIME,strtotime("+30 days", $expiration_time),'reset');

            $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => 100000,'num' => 300 ];
            $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => 100001,'num' => -1 ];
            $this->player->goodsBridge($reward,'道具续费月卡');

            if(empty($this->player->getArg(Consts::MONTHLY_CARD_STATE)))
            {
                MonthlyCardService::getInstance()->monthlyCardEmail($this->player);
            }

            $result = [
                'monthlyCard' => MonthlyCardService::getInstance()->getMonthlyCardFmtData($this->player),
                'reward'      => [[ 'type' => GOODS_TYPE_1,'gid' => 100000,'num' => 300 ]],
                'remain'      => $this->player->getGoods(100001),
            ];
        }

        $this->sendMsg( $result );
    }

}