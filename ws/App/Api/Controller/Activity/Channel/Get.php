<?php
namespace App\Api\Controller\Activity\Channel;

use App\Api\Service\ActivityService;
use App\Api\Service\Channel\WeixinService;
use App\Api\Controller\BaseController;

//朋友圈
class Get extends BaseController
{

    public function index()
    { 
        $iv            = $this->param['iv'];
        $sessionKey    = $this->param['sessionKey'];
        $encryptedData = $this->param['encryptedData'];

        $result = $list = WeixinService::getInstance()->getDecryptData($sessionKey,$iv,$encryptedData);

        if(is_array($result))
        {
            $result = [ 'list' => [] ];
            //更加值刷新状态
            $config  = ActivityService::getInstance()->getCircleOfFriendsRewardConfig();
            foreach ($config as  $id => $value) 
            {
                $num = array_key_exists($value['type'],$list)? $list[$value['type']] : 0;

                $isReceive = $this->player->getArg($id);
                $state = !$isReceive  ? 0 : $isReceive;//  0 未完成  1 已完成  2已领取

                if(!$state && $num >= $value['target'] )
                {
                    $state = 1;
                    $this->player->setArg($id,$state,'reset');
                }

                $result['list'][] = [
                    'id'      => $id,
                    'state'   => $state ,
                    'type'    => $value['type'],
                    'target'  => $value['target'],
                    'val'     => $num >= $value['target'] ? $value['target'] : $num ,
                    'reward'  => $value['reward'],
                ];
            }

        }

        $this->sendMsg( $result );
    }

}