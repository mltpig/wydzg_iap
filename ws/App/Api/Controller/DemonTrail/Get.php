<?php
namespace App\Api\Controller\DemonTrail;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\DemonTrailService;
use App\Api\Service\TaskService;

class Get extends BaseController
{

    public function index()
    {
        
        $result = [
            'demon_trail' => DemonTrailService::getInstance()->getDemonTrailFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}