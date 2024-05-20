<?php
namespace App\Api\Controller\Tower;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TowerService;
use App\Api\Service\TaskService;

class Preinst extends BaseController
{

    public function index()
    {
        $param = $this->param;

        if($param['open'])
        {
            $result = 'preinst 格式错误';
            if(is_array($param['preinst']))
            {
                $this->player->setTower('preinst',0,$param['preinst'],'set');
                $this->player->setTower('open',0,1,'set');
                $result = [
                    'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                ];
            }
        }else{
            $this->player->setTower('open',0,0,'set');
            $result = [
                'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
            ];
        }
        $this->sendMsg( $result );
    }

}