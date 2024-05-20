<?php
namespace App\Api\Controller\SecretTower;
use App\Api\Service\RankService;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SecretTowerService;

class AchievementRank extends BaseController
{

    public function index()
    {
        $site   = $this->param['site'];
        $floor  = $this->param['floor'];

        $secret_tower_rank_key = RANK_SECRET_TOWER.$floor;
        $worldInfo = RankService::getInstance()->getSecretTowerRankInfo($secret_tower_rank_key,$site);

        $this->sendMsg( [
            'world' => SecretTowerService::getInstance()->getRankPlayerInfo($worldInfo,$site),
        ] );
    }

}