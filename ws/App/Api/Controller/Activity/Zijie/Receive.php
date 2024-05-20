<?php
namespace App\Api\Controller\Activity\Zijie;
use App\Api\Utils\Consts;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

class Receive extends BaseController
{

    public function index()
    { 
        $id = Consts::ACTIVITY_CHANNEL_TASK_4;

        $config  = ActivityService::getInstance()->getZijieJumpReward();

        $result = '该任务暂未开启';
        if(array_key_exists($id,$config))
        {
            $state = $this->player->getArg($id);
            $result = '该任务未完成或已领取';
            if(!$state)
            {
                $this->player->setArg($id,1,'reset');

                $this->player->goodsBridge($config[$id]['reward'],'字节侧边栏奖励',$id);

                $result = [
                    'id'     => $id,
                    'state'  => $this->player->getArg(Consts::ACTIVITY_CHANNEL_TASK_4),
                    'reward' => $config[$id]['reward'],
                ];
            }
        }

        $this->sendMsg( $result );
    }

}