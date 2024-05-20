<?php
namespace App\Api\Controller\MonthlyCard;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MonthlyCardService;

class Get extends BaseController
{

    public function index()
    {
        $result = [
            'monthlyCard' => MonthlyCardService::getInstance()->getMonthlyCardFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}