<?php
namespace App\Api\Controller\YuanBao;
use App\Api\Service\ShopService;

use App\Api\Controller\BaseController;


class Get extends BaseController
{

    public function index()
    { 

        $this->sendMsg( [ 
            'list' => ShopService::getInstance()->getShowList($this->player,10)
        ] );
    }

}