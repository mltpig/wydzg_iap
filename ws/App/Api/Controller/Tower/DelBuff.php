<?php
namespace App\Api\Controller\Tower;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TowerService;
use App\Api\Service\TaskService;

class DelBuff extends BaseController
{

    public function index()
    {
        $this->player->setTower('bufftemp',0,[],'set');
        $result = [
            'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}