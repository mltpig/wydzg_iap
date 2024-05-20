<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Utils\Consts;
use App\Api\Service\Module\XianYuanService;
use App\Api\Controller\BaseController;

class Get extends BaseController
{

    public function index()
    { 
        $time   = time();

        $result = [
            'xianyuan' => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}