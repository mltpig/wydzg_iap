<?php
namespace App\Api\Controller\Doufa;
use App\Api\Table\ConfigDoufaRobot;
use App\Api\Table\ConfigParam;
use App\Api\Service\DoufaService;
use App\Api\Controller\BaseController;
use App\Api\Service\PlayerService;
use App\Api\Service\RankService;
use App\Api\Utils\Consts;
use App\Api\Service\ComradeService;
use App\Api\Service\BattleService;

class RecordBattle extends BaseController
{

    public function index()
    {
        $site   = $this->param['site'];
        $rid = $this->param['rid'];
        $result = '无效的rid';
        if($info = DoufaService::getInstance()->getRecordEnemy($this->param['uid'],$this->param['site'],$rid))
        {
            $result = '只有失败才能切磋';
            if(!$info['state'])
            {
                $costGoods = ConfigParam::getInstance()->getFmtParam('PVP_CHALLENGE_COST');
                $num = $this->player->getGoods($costGoods['gid']);
                $result = '数量不足';
                if($num >= $costGoods['num'])
                {

                    $nowSelfRankInfo = RankService::getInstance()->getRankScoreByMember(RANK_DOUFA,$this->player->getData('openid'),$site);
                    $nowSelfRankInfo = DoufaService::getInstance()->getRankMyInfo($nowSelfRankInfo);
                    $myOldScore = $myNewScore = $nowSelfRankInfo['score'];

                    $enemyPlayerid = $info['playerid'];
                    $result   = '不可挑战自己';
                    if($enemyPlayerid !=  $this->player->getData('openid') )
                    {

                        $reward = $comradeReward = $seleBattleData = $enemyBattleData = $selfShowData = $enemyShowData = $enemyUserInfo = [];
    
                        $uid = $this->param['uid'];
            
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
                            $enemyOldScore = $enemyNewScore = $robotConf['score'];
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
                            $this->player->goodsBridge($reward,'斗法复仇胜利',$num);
            
                            $incr = DoufaService::getInstance()->getIncrScore($myOldScore,$enemyOldScore);
                            $decr = floor( $incr * ($rate/1000));
                            if(!$robotConf)
                            {
                                //减少对方积分
                                //发送邮件通知
                                $enemyNewScore = DoufaService::getInstance()->updateDoufaRankSocre($enemyPlayerid,-$decr,$site);
                                DoufaService::getInstance()->saveRecord($enemyPlayerid,[$uid,0,0-$decr,time()],$site);
                            }else{
                                $enemyNewScore = ConfigDoufaRobot::getInstance()->decr($site,$enemyPlayerid,$decr);
                            }
                            
                            //增加自己积分
                            $myNewScore = DoufaService::getInstance()->updateDoufaRankSocre($uid,$incr,$site);
                            //删除记录
                            DoufaService::getInstance()->deleteRecord($uid,$this->param['site'],$rid );
            
                            $ratio  = ComradeService::getInstance()->getLvStageByTalent($this->player,60005);
                            if($ratio > rand(1,1000))
                            {
                                $comradeReward = [ [ 'type' => GOODS_TYPE_1,'gid' => GENGJIN,'num' => 1 ] ];
                                $this->player->goodsBridge($comradeReward,'斗法复仇贤士掉落',$num);
                            }
            
                        }else{
                            DoufaService::getInstance()->saveRecord($enemyPlayerid,[$uid,1,0,time()],$site);
                        }

                        $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => $costGoods['gid'],'num' => -$costGoods['num'] ] ];
                        $this->player->goodsBridge($cost,'斗法挑战','积分：'.$myOldScore." => ".$myNewScore);

                        $result =  [ 
                            'now'  => [
                                'list'       => DoufaService::getInstance()->getRecord($this->param['uid'],$this->param['site']),
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
                                    'hp'    => $selfHp ,
                                    'add'   => $selfShowData,
                                    'userInfo' => $this->player->getData('user'),
                                ],
                                'enemy'  => [
                                    'hp'    => $enemyHp,
                                    'add'   => $enemyShowData,
                                    'userInfo' => $enemyUserInfo,
                                ],
                            ]
                        ];
                    }
                }
            }

        }

    
        $this->sendMsg($result);
    }

}