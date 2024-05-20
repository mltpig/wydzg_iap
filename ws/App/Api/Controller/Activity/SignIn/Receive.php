<?php
namespace App\Api\Controller\Activity\SignIn;

use App\Api\Controller\BaseController;
use App\Api\Table\Activity\SignIn as ActivitySignIn;


class Receive extends BaseController
{

    public function index()
    { 

        $result = '签到活动暂未开启';
        if($signIn = $this->player->getTmp('sign_in'))
        {
            $isAd = $this->param['isAd'];
            list($day,$idState)  = $signIn;

            $rewards = $list = [];
            $config = ActivitySignIn::getInstance()->getAll();
            $desc = '';
            foreach ($config as $value) 
            {
                // 0未达成  1 已达成 2已领取
                $state = $day >= $value['day_num'] ? 1 : 0;
                if($idState >= $value['id']) $state = 2;
                if($state == 1)
                {
                    foreach ($value['reward'] as $reward) 
                    {
                        if($isAd) $reward['num'] *= 2;
                        array_key_exists($reward['gid'],$rewards) ? $rewards[$reward['gid']]['num'] += $reward['num'] : $rewards[$reward['gid']] = $reward;
                    }
                    $state   = 2;
                    $idState = $value['id'];
                    $desc .= $value['id'].',';
                }
                $list[] = [
                    'id'      => $value['id'],
                    'state'   => $state,
                    'day_num' => $value['day_num'],
                    'reward'  => $value['reward'],
                ];
            }

            $this->player->goodsBridge($rewards,'签到活动',$desc);

            $this->player->setTmp('sign_in',[ $day ,$idState]);

            $result = [
                'list'   => $list,
                'reward' => array_values($rewards),
            ];
        }

        $this->sendMsg( $result );
    }

}