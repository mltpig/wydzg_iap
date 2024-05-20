<?php
namespace App\Api\Controller\Activity\Login;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

class Receive extends BaseController
{

    public function index()
    { 
        $id = $this->param['id'];

        $loginRewardConfig  = ActivityService::getInstance()->getLoginRewardConfig();

        $result = '该奖励暂未开启';
        if(array_key_exists($id,$loginRewardConfig))
        {
            $result = '该奖励已领取';
            if(!$this->player->getArg($id))
            {
                $config = $loginRewardConfig[$id];

                $hour   = date('H');

                $result = '未到领取时间';
                if($hour > $config['begin'] && $hour < $config['end'] || $hour == $config['begin'] || $hour == $config['end'])
                {
                    $this->player->setArg($id,2,'reset');
    
                    $this->player->goodsBridge($config['reward'],'登录奖励',$id);
    
                    $result = [
                        'id'     => $id,
                        'state'  => 2,
                        'reward' => $config['reward'],
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}