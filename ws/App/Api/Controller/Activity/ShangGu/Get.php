<?php
namespace App\Api\Controller\Activity\ShangGu;
use App\Api\Service\Module\ShangGuService;
use App\Api\Controller\BaseController;

class Get extends BaseController
{

    public function index()
    { 
        $result = [
            'shanggu' => ShangGuService::getInstance()->getShangGuFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}