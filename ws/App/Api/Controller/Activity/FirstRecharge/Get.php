<?php
namespace App\Api\Controller\Activity\FirstRecharge;

use App\Api\Controller\BaseController;
use App\Api\Service\ActivityService;
//首冲
class Get extends BaseController
{

    public function index()
    { 

        $result = [
            'list' => ActivityService::getInstance()->getFirstRechargeConfig($this->player)
        ];
        
        $this->sendMsg( $result );
    }

}