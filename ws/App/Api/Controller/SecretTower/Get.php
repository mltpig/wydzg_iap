<?php
namespace App\Api\Controller\SecretTower;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Service\TaskService;

class Get extends BaseController
{

    public function index()
    {
        
        $result = [
            'secret_tower' => SecretTowerService::getInstance()->getSecretTowerFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}