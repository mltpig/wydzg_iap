<?php
namespace App\Api\Controller\Paradise\Around;
use App\Api\Utils\Consts;
use App\Api\Service\ParadisService;
use App\Api\Controller\BaseController;

//获取当前福地物品状态
class Get extends BaseController
{

    public function index()
    {

        $around  = $this->player->getData('paradise','around');
        $workers = $this->player->getData('paradise','worker')['list'];

        $time = $this->player->getArg(Consts::HOMELAND_TARGET_REFRESH_TIME);

        $this->sendMsg( [
            'list' => ParadisService::getInstance()->getAroundInfo($around,$workers),
            'remianTime' => $time ? $time - time() : 0
        ] );
    }

}