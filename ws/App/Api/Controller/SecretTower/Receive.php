<?php
namespace App\Api\Controller\SecretTower;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSecretTower;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Service\TaskService;

class Receive extends BaseController
{

    public function index()
    {
        $param = $this->param;

        $secret_tower = $this->player->getData('secret_tower');
        $result = '已领取';
        if(!array_key_exists($param['id'],$secret_tower['floor']))
        {
            $data = SecretTowerService::getInstance()->getSecretTowerFmtData($this->player);
            $result = 'ID 格式错误';
            if(array_key_exists($param['id'],$data['floor_award']))
            {
                $result = '条件未满足';
                if($data['floor_award'][$param['id']] == 1)
                {
                    $reward = [];
                    $config = ConfigSecretTower::getInstance()->getOne($param['id']);
                    $reward = $config['big_reward'];

                    $this->player->setSecretTower('floor',$param['id'],2,'multiSet');
                    $this->player->goodsBridge($config['big_reward'],'坠星矿场奖励',$param['id']);

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