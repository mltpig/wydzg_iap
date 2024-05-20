<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Service\RankService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSecretTower;
use EasySwoole\Component\CoroutineSingleTon;

class SecretTowerService
{
    use CoroutineSingleTon;

    public function initSecretTower(PlayerService $playerSer):void
    {
        //解锁初始化
        if($secret_tower = $playerSer->getData('secret_tower')) return;

        $secret_tower = [
            'towerid'     => 1,
            'floor'       => [], // 0:未满足; 1:可领取; 2:已领取
            'achievement' => [], // 0:未满足; 1:可领取; 2:已领取
        ];

        $playerSer->setSecretTower('',0,$secret_tower,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日
        $playerSer->setArg(Consts::SECRET_TOWER_COUNT,0,'unset'); // 每日次数
        $playerSer->setArg(Consts::SECRET_TOWER_AD_TAG,0,'unset');// 视频次数
    }
    
    // public function getFloorConfig():array
    // {
    //     $config = ConfigSecretTower::getInstance()->getAll();
    //     $list = [];
    //     foreach($config as $id => $val)
    //     {
    //         if($val['big_reward'])
    //         {
    //             $list[$id] = 0;
    //         }
    //     }
    //     return $list;
    // }

    public function getServerConfig():array
    {
        $config = ConfigSecretTower::getInstance()->getAllReward();
        $list = [];
        foreach($config as $id => $val)
        {
            if($val['server_reward'])
            {
                $list[$id] = 0;
            }
        }
        return $list;
    }

    public function getConfigAwardState()
    {
        $config = ConfigSecretTower::getInstance()->getAllReward();

        $big_reward = [];
        foreach($config as $id => $val)
        {
            if($val['big_reward'])
            {
                $big_reward[$id] = 0;
            }
        }


        $server_reward = [];
        foreach($config as $id => $val)
        {
            if($val['server_reward'])
            {
                $server_reward[$id] = 0;
            }
        }

        return [$big_reward,$server_reward];
    }

    public function getSecretTowerFmtData(PlayerService $playerSer):array
    {
        $tower_count    = $playerSer->getArg(Consts::SECRET_TOWER_COUNT);
        $tower_ad_count = $playerSer->getArg(Consts::SECRET_TOWER_AD_TAG);

        $free_time_limit    = ConfigParam::getInstance()->getFmtParam("SECRETTOWER_FREE_TIME_LIMIT") + 0; //挑战次数
        $ad_time_limit      = ConfigParam::getInstance()->getFmtParam("SECRETTOWER_AD_TIME_LIMIT") + 0;   //视频次数
        
        $secretTower = $playerSer->getData('secret_tower');
        $site        = $playerSer->getData('site');

        list($floor_config, $server_config) = $this->getConfigAwardState();
        
        $floors         = $this->getStateFloorAward($floor_config, $secretTower['floor'], $secretTower['towerid']);
        $achievements   = $this->getStateServerAward($server_config, $secretTower['achievement'], $site);

        $secret_tower = [
            'towerid'       => $secretTower['towerid'],
            'floor_award'   => $floors,
            'achievement'   => $achievements,
            'remain_ad'         => ($ad_time_limit - $tower_ad_count),
            'remain_challenge'  => ($free_time_limit - $tower_count),
        ];

        return $secret_tower;
    }

    public function getStateFloorAward(array $list, array $floor, int $towerid):array
    {
        $floors = [];
        foreach($list as $k => $v)
        {
            if(array_key_exists($k, $floor))
            {
                $floors[$k] = 2;
            }else{
                $floors[$k] = 0;
                if(($towerid-1) >= $k)
                {
                    $floors[$k] = 1;
                }
            }
        }
        return $floors;
    }

    public function getStateServerAward(array $list, array $achievement, int $site):array
    {
        // 本位面任意10名道友通关N层
        $achievements = [];
        foreach($list as $k => $v)
        {
            if(array_key_exists($k, $achievement))
            {
                $achievements[$k] = 2;
            }else{
                $achievements[$k] = 0;

                // 根据排行榜条件判断奖励状态
                $key = RANK_SECRET_TOWER.$k;
                $worldInfo = RankService::getInstance()->getSecretTowerRankInfo($key, $site);
                if(count($worldInfo) == 10)
                {
                    $achievements[$k] = 1;
                }
            }
        }

        return $achievements;
    }

    public function getRankPlayerInfo(array $worldInfo,int $site ):array
    {
        $list = [];
        foreach ($worldInfo as $key => $value) 
        {
            list($playerid,$power,$lv) = explode(":",$value['playerid']);

            $player = new PlayerService($playerid,$site);

            $list[] = [
                'index'    => $value['index'],
                'score'    => $value['score'],
                'playerid' => $playerid,
                'power'    => $power,
                'rolelv'   => $lv + 0,
                'head'     => $player->getData('user','head'),
                'nickname' => $player->getData('user','nickname'),
                'chara'    => $player->getData('user','chara'),
                'cloudid'  => $player->getData('cloud','apply'),
                'pet'      => PetService::getInstance()->getPetGoIds($player->getData('pet')),
            ];
        }
        return $list;
    }

    public function getSecretTowerRedPointInfo(PlayerService $playerSer):array
    {
        $secret_tower = $this->getSecretTowerFmtData($playerSer);

        $floor = $achievement = false;

        foreach($secret_tower['floor_award'] as $floor_id => $floor_val)
        {
            if($floor_val == 1)
            {
                $floor = true;
            }
        }

        foreach($secret_tower['achievement'] as $achievement_id => $achievement_val)
        {
            if($achievement_val == 1)
            {
                $achievement = true;
            }
        }

        return [$floor, $achievement];
    }
}
