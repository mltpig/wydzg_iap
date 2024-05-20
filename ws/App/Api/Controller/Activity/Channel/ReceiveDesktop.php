<?php
namespace App\Api\Controller\Activity\Channel;

use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Controller\BaseController;

class ReceiveDesktop extends BaseController
{

    public function index()
    {
        $reward  = ConfigParam::getInstance()->getFmtParam('WX_ADD_DESKTOP_REWARD');

        $state = $this->player->getArg(Consts::ACTIVITY_CHANNEL_TASK_5);

        $result = '已领取';
        if(empty($state))
        {
            $this->player->setArg(Consts::ACTIVITY_CHANNEL_TASK_5, 1, 'reset');

            $this->player->goodsBridge($reward,'微信添加桌面奖励',Consts::ACTIVITY_CHANNEL_TASK_5);

            $result = [
                'id'     => Consts::ACTIVITY_CHANNEL_TASK_5,
                'reward' => $reward,
            ];
        }

        $this->sendMsg( $result );
    }

}