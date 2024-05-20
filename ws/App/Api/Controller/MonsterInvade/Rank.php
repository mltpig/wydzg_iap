<?php
namespace App\Api\Controller\MonsterInvade;
use App\Api\Service\RankService;
use App\Api\Controller\BaseController;
use App\Api\Service\DoufaService;

//抽卡
class Rank extends BaseController
{

    public function index()
    {
        $site = $this->param['site'];
        list($myInfo,$worldInfo) = RankService::getInstance()->getRankInfo(RANK_RUQIN,$this->player->getData('openid'),$site);

        $this->sendMsg( [
            'my'    => [ 'index'    => $myInfo['index'], 'score'    => $myInfo['score'] ],
            'world' => DoufaService::getInstance()->getRankPlayerInfo($worldInfo,$site),
        ] );
    }

}