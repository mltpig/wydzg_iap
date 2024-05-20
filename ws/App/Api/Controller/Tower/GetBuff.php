<?php
namespace App\Api\Controller\Tower;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TowerService;
use App\Api\Service\TaskService;

class GetBuff extends BaseController
{

    public function index()
    {
        $tower = $this->player->getData('tower');

        if(empty($tower['bufftemp']))
        {
            $buff           = TowerService::getInstance()->getTierWhetherBuff($tower['towerid']);
            $old_buffnum    = $this->player->getData('tower','buffnum') + 0;
            $result = '关卡未通过';
            if($buff && $old_buffnum >= 1)
            {
                $old_buffnum--;
                $this->player->setTower('bufftemp',0,$buff,'set');
                $this->player->setTower('buffnum',0,$old_buffnum,'set');
            }
        }
        $result = [
            'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
        ];
        
        $this->sendMsg( $result );
    }

}