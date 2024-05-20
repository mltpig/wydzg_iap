<?php
namespace App\Api\Controller\Tower;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigTower;
use App\Api\Table\ConfigParam;
use App\Api\Service\TaskService;
use App\Api\Service\BattleService;
use App\Api\Service\RankService;
use App\Api\Service\TowerBattleService;
use App\Api\Service\Module\TowerService;
use App\Api\Controller\BaseController;

class Battle  extends BaseController
{

    public function index()
    {
        $tower = $this->player->getData('tower');
        $towerid = $tower['towerid'];

        $result = '请选择加成';
        if(empty($tower['buffnum']) && empty($tower['bufftemp']))
        {
            $config = ConfigTower::getInstance()->getOne($towerid);
            $result = '会当凌绝顶，一览众山小';
            if($config)
            {
                $repeat_rewards = [];
                $rewards        = [];

                $selfData = BattleService::getInstance()->getBattleInitData($this->player);
                $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);
                BattleService::getInstance()->setpataBuff($tower['buff']);

                list($isWin,$battleLog,$selfHp,$enemyHp,$enemyAdd) = TowerBattleService::getInstance()->battle($selfData
                    ,$config['moster_list'],$config['moster_level_list'],$selfShowData);

                if($isWin)
                {
                    $repeat_rewards = $config['repeat_rewards'];

                    if($towerid > $this->player->getArg( Consts::TOWER_HIGH_RECORD )) // 首通
                    {
                        $rewards[]        = $config['rewards'];
                        $this->player->goodsBridge($rewards,'千里走单骑首通奖励',$towerid);
                    }

                    if($gain = TowerService::getInstance()->getTowerIdGainBuff($towerid)) // 通关获得加成
                    {
                        $this->player->setTower('buffnum',0,1,'set');
                    }
                    
                    //更新排行
                    $score = $this->player->getArg( Consts::TOWER_HIGH_RECORD );
                    if($towerid > $score)
                    {
                        // $this->player->setArg(Consts::TOWER_HIGH_RECORD,1,'add'); // 历史通关最高记录ID
                        $this->player->setArg(Consts::TOWER_HIGH_RECORD,$towerid,'reset');
                        RankService::getInstance()->updateRankScore(RANK_TOWER,$towerid,$this->param['uid'],$this->param['site']);
                    }

                    TaskService::getInstance()->setVal($this->player,31,$towerid,'set');

                    $towerid++;
                    $this->player->setTower('towerid',0,$towerid,'set');
                    $this->player->goodsBridge($config['repeat_rewards'],'千里走单骑通关奖励',$towerid);
                }

                $result = [
                    'now'   => [
                        'towerid'  => $towerid,
                    ],
                    'battleDetail' => [
                        'isWin'  => intval($isWin),
                        'log'    => $battleLog,
                        'repeat_rewards' => $repeat_rewards,
                        'reward' => $rewards,
                        'self'   => ['hp' => $selfHp ,'add' => $selfShowData,'chara' => $this->player->getData('user','chara') ],
                        'enemy'  => ['hp' => $enemyHp,'mosterid' => $config['moster_list'] ,
                            'add' => $enemyAdd,
                            "mosterLv" => $config['moster_level_list']],
                    ],
                    'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}