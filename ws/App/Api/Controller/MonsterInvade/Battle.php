<?php
namespace App\Api\Controller\MonsterInvade;
use App\Api\Table\ConfigParam;
use App\Api\Service\RankService;
use App\Api\Service\TaskService;
use App\Api\Service\MonsterInvadeService;
use App\Api\Controller\BaseController;
use App\Api\Service\BattleService;

class Battle extends BaseController
{

    public function index()
    {

        $config = ConfigParam::getInstance()->getFmtParam('INVADE_CHALLENGE_TIME');
        $result = '今日次数已用完，请明日再来';
        $now = $this->player->getArg(INVADE);
        if(array_sum($config)  > $now )
        {
            $this->player->setArg(INVADE,1,'add');

            $battleData = BattleService::getInstance()->getBattleInitData($this->player);
            $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);
            list($_isSuccess,$battleLog,$rewardCount,$selfTotalHurt) = MonsterInvadeService::getInstance()->battle(
                $battleData,$selfShowData);
            $selfShowData['chara'] = $this->player->getData('user','chara');
            //获取奖励
            $reward = MonsterInvadeService::getInstance()->geBattleReward($rewardCount);
            $this->player->goodsBridge($reward,'异兽入侵挑战奖励',$now+1);
            //更新排行
            RankService::getInstance()->updateRankScoreByIncr(RANK_RUQIN,$this->param['uid'],$this->param['site'],
                $selfTotalHurt);
            
            TaskService::getInstance()->setVal($this->player,39,1,'add');

            $result = [
                'reward'     => $reward,
                'battle_log' => $battleLog,
                'selfAdd'    => $selfShowData,
                'enemyAdd'   =>  ['cloud'=>-1,'pet' => [] , 'tactical'=>[],'spirit' =>[]],
                'count'      => $this->player->getArg(INVADE),
                'totalHurt'  => $selfTotalHurt,
            ];
        }

        $this->sendMsg($result);
    }

}