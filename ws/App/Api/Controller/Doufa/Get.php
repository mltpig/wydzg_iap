<?php
namespace App\Api\Controller\Doufa;
use App\Api\Service\RankService;
use App\Api\Controller\BaseController;
use App\Api\Service\DoufaService;

//抽卡
class Get extends BaseController
{

    public function index()
    {
        $site = $this->param['site'];
        list($myInfo,$worldInfo) = RankService::getInstance()->getRankInfo(RANK_DOUFA,$this->player->getData('openid'),$site);

        $this->sendMsg( [
            'my'    => DoufaService::getInstance()->getRankMyInfo($myInfo),
            'world' => DoufaService::getInstance()->getRankPlayerInfo($worldInfo,$site),
        ] );
    }

}