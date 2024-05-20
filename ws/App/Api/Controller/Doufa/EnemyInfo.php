<?php
namespace App\Api\Controller\Doufa;
use App\Api\Service\DoufaService;
use App\Api\Controller\BaseController;

class EnemyInfo extends BaseController
{

    public function index()
    {

        $result = '无效的playerid';
        if(in_array($this->param['playerid'],$this->player->getData('doufa','enemy')))
        {
            $result = [
                'enemyInfo' => DoufaService::getInstance()->getEnemyPlayerInfo( $this->param['playerid'],$this->param['site'] ),
            ];

        }
        
        $this->sendMsg($result);
    }

}