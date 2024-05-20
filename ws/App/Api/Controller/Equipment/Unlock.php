<?php
namespace App\Api\Controller\Equipment;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\TaskService;

class Unlock extends BaseController
{

    public function index()
    {
        $param      = $this->param;
        $equipment  = $this->player->getData('equipment');
        
        $result = '未解锁';
        if(array_key_exists($param['id'],$equipment['hm']))
        {
            $result = '激活等级不足';
            if($equipment['stage'] >= $param['id'])
            {
                $this->player->setEquipment('hm',$param['id'],1,'multiSet');

                $result = [
                    'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($this->player),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}