<?php
namespace App\Api\Controller\SecretTower;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSecretTower;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Service\TaskService;

class Draw extends BaseController
{

    public function index()
    {
        $param = $this->param;

        $secret_tower = $this->player->getData('secret_tower');
        $result = '已领取';
        if(!array_key_exists($param['id'],$secret_tower['achievement']))
        {
            $data = SecretTowerService::getInstance()->getSecretTowerFmtData($this->player);
            $result = 'ID 格式错误';
            if(array_key_exists($param['id'],$data['achievement']))
            {
                $result = '条件未满足';
                if($data['achievement'][$param['id']] == 1)
                {
                    $reward = [];
                    $config = ConfigSecretTower::getInstance()->getOne($param['id']);
                    $reward = $config['server_reward'];

                    $this->player->setSecretTower('achievement',$param['id'],2,'multiSet');
                    $this->player->goodsBridge($config['server_reward'],'坠星矿场成就奖励',$param['id']);

                    $result = [
                        'secret_tower' => SecretTowerService::getInstance()->getSecretTowerFmtData($this->player),
                        'reward'       => $reward
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}