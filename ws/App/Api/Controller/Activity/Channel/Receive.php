<?php
namespace App\Api\Controller\Activity\Channel;

use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

class Receive extends BaseController
{

    public function index()
    { 
        $id = $this->param['id'];

        $config  = ActivityService::getInstance()->getCircleOfFriendsRewardConfig();

        $result = '该任务暂未开启';
        if(array_key_exists($id,$config))
        {
            $state = $this->player->getArg($id);

            $result = '该任务未完成或已领取';
            if($state == 1)
            {
                $this->player->setArg($id,2,'reset');

                $this->player->goodsBridge($config[$id]['reward'],'微信朋友圈奖励',$id);

                $result = [
                    'id'     => $id,
                    'state'  => 2,
                    'reward' => $config[$id]['reward'],
                ];
            }
        }

        $this->sendMsg( $result );
    }

}