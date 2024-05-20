<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class Get extends BaseController
{

    public function index()
    {
        $result = [
            'magic' => MagicService::getInstance()->getMagicFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}