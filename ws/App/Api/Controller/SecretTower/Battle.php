<?php
namespace App\Api\Controller\SecretTower;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigSecretTower;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigChapter;
use App\Api\Service\TaskService;
use App\Api\Service\BattleService;
use App\Api\Service\RankService;
use App\Api\Service\MosterBattleService;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Controller\BaseController;

class Battle  extends BaseController
{

    public function index()
    {
        $param          = $this->param;
        $secret_tower   = $this->player->getData('secret_tower');
        $towerid        = $secret_tower['towerid'];

        $config = ConfigSecretTower::getInstance()->getOne($towerid);
        $result = '会当凌绝顶，一览众山小';
        if($config)
        {
            $reward = [];
            // $this->player->setArg(Consts::SECRET_TOWER_COUNT, 1, 'add');
            $selfData       = BattleService::getInstance()->getBattleInitData($this->player);
            $selfShowData   = BattleService::getInstance()->getBattleShowData($this->player);
            list($isWin,$battleLog,$selfHp,$enemyHp,$enemyAdd) = MosterBattleService::getInstance()->battle(
                $selfData,$config['moster_list'],$config['moster_level_list'],$selfShowData);

            if($isWin)
            {
                $reward = $config['challenge_reward'];
                $score = time();

                // 最高记录排行榜
                RankService::getInstance()->updateRankScore(RANK_SECRET,$towerid,$this->param['uid'],$this->param['site']);

                // 位面成就排行榜
                $floor_config = SecretTowerService::getInstance()->getServerConfig();
                foreach($floor_config as $floor => $state)
                {
                    if($towerid == $floor)
                    {
                        $secret_tower_rank_key = RANK_SECRET_TOWER.$floor;
                        $worldInfo = RankService::getInstance()->getSecretTowerRankInfo($secret_tower_rank_key, $this->param['site']);
                        if(count($worldInfo) == 10) continue;
    
                        $power      = BattleService::getInstance()->getPower($selfData);
                        $playerid   = $this->param['uid'].':'.$power.':'.$selfData['lv'];

                        RankService::getInstance()->updateRankScore($secret_tower_rank_key,$score,$playerid,$this->param['site']);
                    }
                }

                $towerid++;
                $this->player->setSecretTower('towerid',0,$towerid,'set');
                $this->player->goodsBridge($config['challenge_reward'],'坠星矿场挑战奖励','0|'.$towerid);
            }
            
            $result = [
                'now'   => [
                    'towerid'  => $this->player->getData('secret_tower','towerid'),
                ],
                'battleDetail' => [
                    'isWin'  => intval($isWin),
                    'log'    => $battleLog,
                    'reward' => $reward,
                    'self'   => ['hp' => $selfHp ,'add' => $selfShowData,'chara' => $this->player->getData('user','chara')],
                    'enemy'  => ['hp' => $enemyHp,'mosterid' => $config['moster_list'] ,
                        'add'       => $enemyAdd,
                        "mosterLv"  => $config['moster_level_list']],
                ],
                'secret_tower' => SecretTowerService::getInstance()->getSecretTowerFmtData($this->player),
                'isAd'   => $param['isAd'],
            ];
        }

        $this->sendMsg( $result );
    }

}