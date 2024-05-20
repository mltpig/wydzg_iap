<?php
namespace App\Api\Controller\Doufa;
use App\Api\Table\ConfigDoufaRobot;
use App\Api\Table\ConfigParam;
use App\Api\Service\DoufaService;
use App\Api\Service\TaskService;
use App\Api\Controller\BaseController;
use App\Api\Service\PlayerService;
use App\Api\Service\RankService;
use App\Api\Service\BattleService;
use App\Api\Service\ComradeService;
use App\Api\Utils\Consts;

class Battle extends BaseController
{

    public function index()
    {

        $site          = $this->param['site'];
        $enemyPlayerid = $this->param['playerid'];

        $costGoods = ConfigParam::getInstance()->getFmtParam('PVP_CHALLENGE_COST');

        $result = '切磋券数量不足';
        $has = $this->player->getGoods($costGoods['gid']);
        if($has >= $costGoods['num'])
        {
            $result   = '无效的playerid';
            $enemy    = $this->player->getData('doufa','enemy');
    
            $nowSelfRankInfo = RankService::getInstance()->getRankScoreByMember(RANK_DOUFA,$this->player->getData('openid'),$site);
            $nowSelfRankInfo = DoufaService::getInstance()->getRankMyInfo($nowSelfRankInfo);
            $myOldScore = $myNewScore = $nowSelfRankInfo['score'];
    
            if(in_array($this->param['playerid'],$this->player->getData('doufa','enemy')))
            {
                TaskService::getInstance()->setVal($this->player,28,1,'add');

                $reward = $comradeReward = $seleBattleData = $enemyBattleData = $selfShowData = $enemyShowData = $enemyUserInfo = [];

                $selePlayerid = $this->param['uid'];
    
                if(!$robotConf = ConfigDoufaRobot::getInstance()->getOne($site,$enemyPlayerid))
                {
                    $enemyPlayer     = new PlayerService($enemyPlayerid,$this->param['site']);
                    $nowEnemyRankInfo = RankService::getInstance()->getRankScoreByMember(RANK_DOUFA,$enemyPlayer->getData('openid'),$site);
                    $nowEnemyRankInfo = DoufaService::getInstance()->getRankMyInfo($nowEnemyRankInfo);
                    $enemyOldScore   = $enemyNewScore = $nowEnemyRankInfo['score'];

                    $enemyBattleData = BattleService::getInstance()->getBattleInitData($enemyPlayer);
                    $enemyShowData   = BattleService::getInstance()->getBattleShowData($enemyPlayer);
                    $enemyUserInfo   = $enemyPlayer->getData('user');
                }else{
                    $enemyOldScore   = $enemyNewScore = $robotConf['score'];
                    $enemyBattleData = BattleService::getInstance()->getNpcBattleInitData($robotConf);
                    $enemyShowData   = BattleService::getInstance()->getNpcBattleShowData($robotConf);
                    $enemyUserInfo   = $robotConf['user'];
                }

                $seleBattleData = BattleService::getInstance()->getBattleInitData($this->player);
                $selfShowData = BattleService::getInstance()->getBattleShowData($this->player);

                list($isWin,$battleLog,$selfHp,$enemyHp) =  BattleService::getInstance()->run($seleBattleData,
                    $enemyBattleData,20,$selfShowData,$enemyShowData,false);
                
                if($isWin)
                {
                    $this->player->setArg(Consts::DOUFA_WIN_COUNT,1,'add');
                    //发放奖励
                    $reward = ConfigParam::getInstance()->getFmtParam('PVP_CHALLENGE_REWARD');
                    $rate = ConfigParam::getInstance()->getFmtParam('PVP_SCORE_CHANGE_RATE');
                    $this->player->goodsBridge($reward,'斗法挑战胜利',$has);

                    //减少对方积分
                    //发送邮件通知
                    $incr = DoufaService::getInstance()->getIncrScore($myOldScore,$enemyOldScore);
                    $decr = floor( $incr * ($rate/1000));
                    if(!$robotConf)
                    {
                        $enemyNewScore = DoufaService::getInstance()->updateDoufaRankSocre($enemyPlayerid,-$decr,$site);
                        DoufaService::getInstance()->saveRecord($enemyPlayerid,[$selePlayerid,0,0-$decr,time()],$site);
                    }else{
                        $enemyNewScore = ConfigDoufaRobot::getInstance()->decr($site,$enemyPlayerid,-$decr);
                    }
    
                    //增加自己积分
                    $myNewScore = DoufaService::getInstance()->updateDoufaRankSocre($selePlayerid,$incr,$site);
                    $ratio  = ComradeService::getInstance()->getLvStageByTalent($this->player,60005);
                    if($ratio > rand(1,1000))
                    {
                        $comradeReward = [ [ 'type' => GOODS_TYPE_1,'gid' => GENGJIN,'num' => 1 ] ];
                        $this->player->goodsBridge($comradeReward,'斗法贤士掉落',$has);
                    }
    
                }else{
                    DoufaService::getInstance()->saveRecord($enemyPlayerid,[$selePlayerid,1,0,time()],$site);
                }

                $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => $costGoods['gid'],'num' => -$costGoods['num'] ] ];
                $this->player->goodsBridge($cost,'斗法挑战','积分：'.$myOldScore." => ".$myNewScore);
                
                //刷新敌人
                $enemy = DoufaService::getInstance()->getEnemysUid($this->param['uid'],$this->param['site'],5);
                
                $this->player->setData('doufa','enemy',$enemy);

                $result =  [ 
                    'now'  => [
                        'enemys'     => DoufaService::getInstance()->getEnemyList($enemy,$this->param['site']),
                        'myScore'    => ['old' => $myOldScore,'new' => $myNewScore],
                        'enemyScore' => ['old' => $enemyOldScore,'new' => $enemyNewScore],
                    ],
                    'remain'     => $this->player->getGoods($costGoods['gid']),
                    'battleDetail' => [
                        'isWin'         => intval($isWin),
                        'log'           => $battleLog,
                        'reward'        => $reward,
                        'comradeReward' => $comradeReward,
                        'self'   => [
                            'hp'       => $selfHp ,
                            'add'      => $selfShowData,
                            'userInfo' => $this->player->getData('user'),
                        ],
                        'enemy'  => [
                            'hp'       => $enemyHp,
                            'add'      => $enemyShowData,
                            'userInfo' => $enemyUserInfo,
                        ],
                    ]
                ];
            }


        }

        $this->sendMsg($result);
    }

}