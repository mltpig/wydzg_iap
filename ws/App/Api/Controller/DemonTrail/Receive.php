<?php
namespace App\Api\Controller\DemonTrail;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\DemonTrailService;
use App\Api\Service\TaskService;

class Receive extends BaseController
{

    public function index()
    {
        $param = $this->param;

        $demon_trail = $this->player->getData('demon_trail');
        $result = '奖励已领取';
        if(!array_key_exists($param['id'],$demon_trail))
        {
            $task = DemonTrailService::getInstance()->getDemonTrailFmtData($this->player);

            $result = 'ID 格式错误';
            if(array_key_exists($param['id'],$task))
            {

                $result = '任务未完成';
                if($task[$param['id']] == 1)
                {
                    $config = DemonTrailService::getInstance()->getDemonTrailConfig();
                    $gid = $config[$param['id']]['reward']['gid'];
                    $num = $config[$param['id']]['reward']['num'];

                    $this->player->setDemonTrail($param['id'],0,2,'set');
                    
                    $reward[] = [ 'type' => GOODS_TYPE_1,'gid' => $gid,'num' => $num ];
                    $this->player->goodsBridge($reward,'帝王之路',$param['id'] );
                    
                    $result = [
                        'demon_trail' => DemonTrailService::getInstance()->getDemonTrailFmtData($this->player),
                        'reward' => $reward
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}