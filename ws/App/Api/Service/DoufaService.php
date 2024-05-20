<?php
namespace App\Api\Service;

use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigDoufaRobot;
use App\Api\Table\ConfigNickname;
use App\Api\Utils\Keys;
use App\Api\Service\Node\NodeService;
use App\Api\Service\module\PetService;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\EasySwoole\Task\TaskManager;

class DoufaService
{
    use CoroutineSingleTon;

    public function createRobot(int $site):void
    {
        $robotNumber = ConfigParam::getInstance()->getFmtParam('PVP_ROBOT_COUNT');
        $robotLevel  = ConfigParam::getInstance()->getFmtParam('PVP_ROBOT_LEVEL');
        //id 积分 名称 头像
        //等级 装备 附魂
        $list = [];
        for ($i=0; $i < $robotNumber; $i++) 
        { 
            $playerid = md5($i);
            $roleLv   = rand($robotLevel[0],$robotLevel[1]);

            $equip = [];
            for ($j=1; $j < 13; $j++)
            { 
                $newTmp   = EquipService::getInstance()->extract(1,$roleLv,[],$j);
                $newTmp['index'] =  md5($j);
                $equip[$j] = $newTmp;
            }
            $cloud = ['apply' => -1,'stage' => 1,'lv' => 1];
            $user = [
                'head'     => ['type' => 1, 'value' => strval(rand(141001,141002)) ],
                'chara'    => ['type' => 1, 'value' => strval(rand(141001,141002)) ],
                'nickname' => ConfigNickname::getInstance()->getNickname()
            ];
              
            $battleData =  BattleService::getInstance()->getNpcBattleInitData( [ 'rolelv' => $roleLv,'equip' => $equip,'cloud' => $cloud] );
            $list[$playerid] = json_encode([
                'playerid' => $playerid,
                'score'    => 1000,
                'power'    => BattleService::getInstance()->getPower( $battleData ),
                'rolelv'   => $roleLv,
                'user'     => json_encode($user,JSON_UNESCAPED_UNICODE),
                'cloud'    => json_encode($cloud),
                'equip'    => json_encode($equip,JSON_UNESCAPED_UNICODE),
            ]);

        }

        ConfigDoufaRobot::getInstance()->initTable($site,$list);

    }

    public function getEnemysUid(string $playerid,int $site,int $num):array
    {
        //如果自己在排行榜及排行榜长度大于6：包含自己，则从排行榜取
        //否则取机器人

        $list   = RankService::getInstance()->getRankNeighborInfo(RANK_DOUFA,$playerid,$site);
        if( !$list || (count($list) - 5) <= 0 )
        {
            $config = ConfigDoufaRobot::getInstance()->getAllRobotId($site);
            $list   = array_merge($config,$list);
        } 
        
        return array_rand($list,$num); 
    }

    public function getEnemyList(array $enemyIds,int $site):array
    {
        $info = [];

        foreach ($enemyIds as $playerid)
        {
            $robotConfig = ConfigDoufaRobot::getInstance()->getOne($site,$playerid);
            $data = $robotConfig;
            if(!$data)
            {
                $playerSer = new PlayerService($playerid,$site);

                if(is_null($playerSer->getData('last_time')))
                {
                    \EasySwoole\EasySwoole\Logger::getInstance()->log("getEnemyList: ".json_encode($enemyIds));
                    continue;
                }

                $myInfo     = RankService::getInstance()->getRankScoreByMember(RANK_DOUFA,$playerSer->getData('openid'),$site);
                $myInfo     = $this->getRankMyInfo($myInfo);
                $battleData = BattleService::getInstance()->getBattleInitData($playerSer);
                $data = [
                    'playerid' => $playerid,
                    'score'    => $myInfo['score'],
                    'rolelv'   => $playerSer->getData('role','lv'),
                    'power'    => BattleService::getInstance()->getPower($battleData),
                    'user'     => $playerSer->getData('user'),
                ];
            }

            $info[] = [
                'playerid' => $data['playerid'],
                'score'    => $data['score'],
                'power'    => $data['power'],
                'rolelv'   => $data['rolelv'],
                'nickname' => $data['user']['nickname'],
                'head'     => $data['user']['head'],
            ];
        }

        return bubbleSort($info,'power');
    }

    public function getRankMyInfo(array $myInfo ):array
    {
        $initScore = ConfigParam::getInstance()->getFmtParam('PVP_INITIAL_SCORE');
        return [ 'index'  => $myInfo['index'], 'score'    => !$myInfo['index'] ? $initScore : $myInfo['score'] ];
    }

    public function getRankPlayerInfo(array $worldInfo,int $site ):array
    {
        $list = [];
        foreach ($worldInfo as $key => $value) 
        {
            $player = new PlayerService($value['playerid'],$site);
            
            $list[] = [
                'index'    => $value['index'],
                'score'    => $value['score'],
                'playerid' => $value['playerid'],
                'rolelv'   => $player->getData('role','lv'),
                'head'     => $player->getData('user','head'),
                'nickname' => $player->getData('user','nickname'),
                'chara'    => $player->getData('user','chara'),
                'cloudid'  => $player->getData('cloud','apply'),
                'pet'      => PetService::getInstance()->getPetGoIds($player->getData('pet')),
            ];
        }
        return $list;
    }

    public function updateDoufaRankSocre(string $openid,int $incr,int $site ):int
    {   
        //如果不在排行榜，初始化1000，如果在则正常判断
        $nowRankInfo = RankService::getInstance()->getRankScoreByMember(RANK_DOUFA,$openid,$site);
        if($nowRankInfo['index'] > 0)
        {
            return RankService::getInstance()->updateRankScoreByIncr(RANK_DOUFA,$openid,$site,$incr);
        }else{
            $score = ConfigParam::getInstance()->getFmtParam('PVP_INITIAL_SCORE') + $incr ;

            RankService::getInstance()->updateRankScore(RANK_DOUFA,$score,$openid,$site);
            return $score;
        }

    }

    public function saveRecord(string $playerid,array $content,$site):void
    {
        $rid = md5(SnowFlake::make(rand(0,31),rand(0,127)));
        $cacheKey  = Keys::getInstance()->getDoufaRecordKey($playerid,$site);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$rid,$content){
            return $redis->hSet($cacheKey,$rid,json_encode($content));
        });
    }

    public function deleteRecord(string $playerid,int $site,string $rid):void
    {
        $cacheKey  = Keys::getInstance()->getDoufaRecordKey($playerid,$site);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$rid){
            $redis->hDel($cacheKey,$rid);
        });
    }

    public function getRecordEnemy(string $playerid,int $site,string $rid):array
    {
        $cacheKey  = Keys::getInstance()->getDoufaRecordKey($playerid,$site);
        $string = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$rid){
            return $redis->hGet($cacheKey,$rid);
        });
        
        if(!$string) return [];

        list($uid,$state,$score,$time) = json_decode($string,true);

        return ['playerid' => $uid,'state' => $state];
    }

    public function getRecord(string $playerid,int $site):array
    {
        $cacheKey = Keys::getInstance()->getDoufaRecordKey($playerid,$site);
        $content  = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey){
            return $redis->hGetAll($cacheKey);
        });

        $list = [];
        $now  = time();
        foreach ($content as $rid => $string) 
        {
            //[$uid,1,0,time]
            list($uid,$state,$score,$time) = json_decode($string,true);
            list($record) = $this->getEnemyList([ $uid ],$site);
            $record['state']  = $state;
            $record['deduct'] = $score;
            $record['time']   = date('Ymd',$time) === date('Ymd',$now) ? date('H:i',$time) : ceil($now/DAY_LENGHT) - ceil($time /DAY_LENGHT).'天前' ;
            $record['rid']    = $rid;
            $record['playerid'];
            //unset($record['playerid']);
            
            $list[$time] = $record;
        }
        ksort($list);
        return array_reverse($list);
    }

    public function getIncrScore($myScore,$enemyScore):int
    {
        $diff   = $enemyScore - $myScore;
        $config = ConfigParam::getInstance()->getFmtParam('PVP_SCORE_CHANGE_PARAM');
        $min    = -20000000;
        $max    = 20000000;
        $list   = [];
        $count  = count($config)-1;

        foreach ($config as $key => $value) 
        {
            if(!$key){
                $list[] = [ 'max' => $value[0] ,'min'  => $min,'val' => $value[1]];
            }elseif($key == $count){
                $list[] = [ 'max' => $max ,'min'  => $value[0],'val' => $value[1]];
            }else{

                if($value[0] < 0) $list[] =  [ 'max' => $config[$key+1][0]-1 ,'min'  => $value[0],'val' => $value[1]];

                if(!$value[0]) $list[] = [ 'max' => $value[0] ,'min'  => $value[0],'val' => $value[1]];

                if(!$value[0] > 0) $list[] = [ 'max' => $value[0] ,'min'  => $config[$key-1][0]+1,'val' => $value[1]];
            }

        }

        $socre  =  0;
        foreach ($list as $item) 
        {
            if( $diff == $item['max'] || $diff == $item['min'] || $diff < $item['max'] && $diff > $item['min'] ) $socre = $item['val'];
        }

        return $socre;
    }


    public function settlementRewards():void
    { 
        \EasySwoole\EasySwoole\Logger::getInstance()->waring('斗法排行榜奖励');

        $sites = NodeService::getInstance()->getServerNodeList();

        foreach ($sites as $siteid => $openTime) 
        {
            TaskManager::getInstance()->async(function () use($siteid){
                $this->beginDistributeReward($siteid);
            });
        }
    }
    
    public function beginDistributeReward(int $siteid):void
    {
        $weekDay  = date('w');
        $rankList = RankService::getInstance()->getRankPlayeridByIndex(RANK_DOUFA,$siteid,9998);
        //星期一凌晨删除排行榜，创建机器人
        if($weekDay == 1)
        {
            RankService::getInstance()->delRank(RANK_DOUFA,$siteid);
            TaskManager::getInstance()->async(function () use($siteid){
                $this->createRobot($siteid);
                $this->clearRecord($siteid);
            });
        }


        $dayReward  = $this->getSettlementRewardsFmt('PVP_DAILY_RANK_REWARD');
        $weekReward = $this->getSettlementRewardsFmt('PVP_WEEK_RANK_REWARD');
        $index = 1;
        foreach ($rankList as $playerid => $score) 
        {

            $this->sendDailyRewardEmail($playerid,$siteid,$index,$this->getMatchReward($index,$dayReward));
            //星期一凌晨发送周邮件
            if($weekDay == 1 ) $this->sendWeekRewardEmail($playerid,$siteid,$index,$this->getMatchReward($index,$weekReward));
            $index++;
        }

    }

    public function getMatchReward(int $index,array $config):array
    {

        foreach ($config as $value) 
        {
            if($value['begin'] == $index || $value['end'] == $index || $index > $value['begin'] && $index < $value['end'] ) return $value['rewards'];
        }

        return [];
    }

    public function getSettlementRewardsFmt(string $keyName):array
    {
        $config  = [];
        $string  = ConfigParam::getInstance()->getOne($keyName);
        $list    = explode(';',$string);
        foreach ($list as $value) 
        {
            $rewards  = [];
            list($bego,$end,$rewardStr) = explode(',',$value);
            $rewardList = explode('|',$rewardStr);
            foreach ($rewardList as $key => $detail)
            {
                $item = getFmtGoods(explode('=',$detail));
                $item['type'] = GOODS_TYPE_1;
                $rewards[] = $item;
            }

            $config[] = [
                'begin'   => $bego,
                'end'     => $end,
                'rewards' => $rewards,
            ];
        }

        return $config;
    }


    public function sendDailyRewardEmail(string $playerid,int $site,int $index,array $reward):void
    { 
        $email  = [
            'title'      => '今日演武场奖励',
            'content'    => '大人在今日的演武场中所向披靡，排名第'.$index.'位，这是妾身为您奉上的薄礼~<br/>',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => $reward,
            'from'       => '貂蝉',
            'state'      => 0,
        ];
        
        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($playerid,$site,1,$emailId,$email);
    }

    public function sendWeekRewardEmail(string $playerid,int $site,int $index,array $reward):void
    { 
        $email  = [
            'title'      => '本周演武场奖励',
            'content'    => '大人在本周的演武场中一往无前，万夫不当之势令众人无不钦佩，本周排名第'.$index.'位，妾身特献上好礼，望大人笑纳！<br/>',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => $reward,
            'from'       => '貂蝉',
            'state'      => 0,
        ];
        
        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($playerid,$site,1,$emailId,$email);
    }

    public function clearRecord(int $site):void
    {
        $cacheKey  = Keys::getInstance()->getDoufaRecordKey('*',$site);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey){
            $keys = $redis->keys($cacheKey);
            foreach ($keys as $key => $value)
            {
                $redis->del($value);
            }
        });
    }

}
