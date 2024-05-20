<?php
namespace App\Api\Controller\Activity\Zijie;
use App\Api\Service\ActivityService;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;

//朋友圈
class Get extends BaseController
{

    public function index()
    { 
        
        $config  = ActivityService::getInstance()->getZijieJumpReward();

        $result = [];
        foreach ($config as $taskid => $value)
        {
            $result = [
                'id'      => $taskid,
                'state'   => $this->player->getArg(Consts::ACTIVITY_CHANNEL_TASK_4),
                'reward'  => $value['reward'],
            ];
        }


        $this->sendMsg( $result );
    }

}