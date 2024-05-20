<?php
namespace App\Api\Controller\LifetimeCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\LifetimeCardService;

class Get extends BaseController
{

    public function index()
    {
        $result = [
            'lifetimeCard' => LifetimeCardService::getInstance()->getLifetimeCardFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}