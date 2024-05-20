<?php
namespace App\Api\Controller\Activity\OverFlowGift;
use App\Api\Utils\Consts;
use App\Api\Service\ShopService;
use App\Api\Controller\BaseController;

class Get extends BaseController
{

    public function index()
    {
        $result = [
            '102' => ShopService::getInstance()->getShowList($this->player,102),
            '103' => ShopService::getInstance()->getShowList($this->player,103),
            '104' => ShopService::getInstance()->getShowList($this->player,104),
        ];

        $this->sendMsg( $result );
    }

}