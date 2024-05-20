<?php
namespace App\Api\Controller\Shop;
use App\Api\Service\ShopService;

use App\Api\Controller\BaseController;


class Get extends BaseController
{

    public function index()
    { 

        $this->sendMsg( [ 
            'list' => ShopService::getInstance()->getShowList($this->player,11)
        ] );
    }

}