<?php
namespace App\Api\Controller\Activity\Login;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

//登录奖励
class Get extends BaseController
{

    public function index()
    { 
        
        $config  = ActivityService::getInstance()->getLoginRewardConfig();

        $result = [];
        foreach ($config as $actid => $value)
        {
            $hour = date('H');

            $state = $hour > $value['begin'] && $hour < $value['end'] || $hour == $value['begin'] ? 1 : 0;
        
            $state = $this->player->getArg($actid) ? 2 : $state ;

            $result[] = [
                'id'      => $actid,
                'name'    => $value['name'],
                'state'   => $state,
                'begin'   => $value['begin'],
                'end'     => $value['end'],
                'reward'  => $value['reward'],
            ];
        }
        $this->sendMsg( $result );
    }

}