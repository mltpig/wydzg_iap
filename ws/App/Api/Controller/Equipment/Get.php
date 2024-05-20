<?php
namespace App\Api\Controller\Equipment;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\TaskService;

class Get extends BaseController
{

    public function index()
    {
        
        $result = [
            'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}