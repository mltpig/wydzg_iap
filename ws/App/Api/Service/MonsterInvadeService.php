<?php
namespace App\Api\Service;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMonsterInvade;
use EasySwoole\Redis\Redis;
use EasySwoole\Utility\SnowFlake;
use App\Api\Service\Node\NodeService;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Component\CoroutineSingleTon;

class MonsterInvadeService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日妖王挑战次数
        $playerSer->setArg(INVADE,1,'unset'); 
    }

    public function geBattleReward(int $count):array
    {  
        $reward = [];

        list($rewardList,$randList) = $this->getInvadeRewardConfig();

        for ($i=0; $i < $count; $i++) 
        { 
            $gid = randTable($randList);
            
            if(array_key_exists($gid,$reward)){
                $reward[$gid]['num'] += $rewardList[$gid];
            }else{
                $reward[$gid] = [ 'type' => GOODS_TYPE_1,'gid' => $gid,'num' => $rewardList[$gid] ];
            }
        }

        return array_values($reward);
    }
    
    public function getInvadeRewardConfig():array
    {
        $rewardList = $randList = [];
        $config = explode('|',ConfigParam::getInstance()->getFmtParam('INVADE_FIGHT_REWARD'));
        
        foreach ($config as $value) 
        {
            list($gid,$range) = explode('=',$value);
            list($num,$weight) = explode(',',$range);
            $rewardList[$gid] = intval($num);
            $randList[$gid]   = $weight;
        }

        return [$rewardList,$randList];
    }

    public function getMonsterid():string
    {
        return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) {
            return $redis->get(CONFIG_MONSTER_INVADE);
        });
    }

    public function setMonsterid(int $monsterid):void
    {
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($monsterid){
            return $redis->set(CONFIG_MONSTER_INVADE,$monsterid);
        });
    }

    public function settlementRewards():void
    { 
        \EasySwoole\EasySwoole\Logger::getInstance()->waring('异兽入侵排行榜奖励');

        $config = DoufaService::getInstance()->getSettlementRewardsFmt('INVADE_RANK_REWARD');
        if(!$config) return ;

        $sites = NodeService::getInstance()->getServerNodeList();

        foreach ($sites as $siteid => $openTime) 
        {
            TaskManager::getInstance()->async(function () use($siteid,$config){
                $this->beginDistributeReward($siteid,$config);
            });
        }
    }

    public function beginDistributeReward(int $siteid,array $config):void
    {
        $rankList = RankService::getInstance()->getRankPlayeridByIndex(RANK_RUQIN,$siteid,99998);

        $this->flushMonsterInvadeId();
        RankService::getInstance()->delRank(RANK_RUQIN,$siteid);

        $index = 1;
        foreach ($rankList as $playerid => $score) 
        {
            $reward = DoufaService::getInstance()->getMatchReward($index,$config);

            $this->sendDailyRewardEmail($playerid,$siteid,$index,$reward);

            $index++;
        }

    }

    public function flushMonsterInvadeId():void
    {
        
        $list  = ConfigParam::getInstance()->getFmtParam('INVADE_MONSTER_ID');
        $index = ceil(time() / DAY_LENGHT) % 3;

        $this->setMonsterid($list[$index]);
    }

    public function sendDailyRewardEmail(string $playerid,int $site,int $index,array $reward):void
    { 
        $email  = [
            'title'      => '今日南蛮入侵奖励',
            'content'    => '大人在今日抵抗南蛮入侵行动中表现神勇，排名第'.$index.'位，妾身特准备好礼一份犒劳大人',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => $reward,
            'from'       => '貂蝉',
            'state'      => 0,
        ];

        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($playerid,$site,1,$emailId,$email);
    }

    public function battle(array $self,&$selfShowData):array
    {
        //
        $selfAttr  = BattleService::getInstance()->getRoleAttr( $self );
        $enemyList = $this->getMonsterBattleParam($selfAttr['attack']);

        return MonsterInvadeBattleService::getInstance()->run( [ 1 => $self ],$enemyList,$selfShowData,15);
    } 

    public function getMonsterBattleParam(string $selfAttack):array
    {
        $monsterConfig = ConfigMonsterInvade::getInstance()->getAll();
        ksort($monsterConfig);
        $monsterList = [];
        foreach ($monsterConfig as $monsterId => $value) 
        {
            //由于属性写死，把属性挂到装备下
            $attack = mul($selfAttack,(1000 + $value['attack_base'])/1000);
            $monsterList[$monsterId] = [
                'lv'    => 0,
                'cloud' => [],
                'comrade' => [],
                'chara' => [],
                'equip' => [
                    "1" => [
                        "base"          => [ "attack"=> $attack,"hp"=> $value['hp_base'], "defence"=> $value['def_base'] ,"speed"=> $value['speed_base'] ],
                        "sec_attr"      => [ ],
                        "sec_def_attr"  => [ 
                            1  => $value['re_stun'],
                            2  => $value['re_critical_hit'],
                            3  => $value['re_double_attack'],
                            4  => $value['re_dodge'],
                            5  => $value['re_attack_back'],
                            6  => $value['re_life_steal']
                        ]
                    ],
                ],
            ];
        }

        return $monsterList;
    }

}   
