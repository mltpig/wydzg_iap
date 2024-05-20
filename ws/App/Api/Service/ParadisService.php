<?php
namespace App\Api\Service;

use App\Api\Table\ConfigParadiseLevel;
use App\Api\Table\ConfigParadiseReward;
use App\Api\Table\ConfigGoods;
use App\Api\Table\ConfigParam;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Utility\SnowFlake;
use App\Api\Utils\Keys;
use App\Api\Utils\Consts;

class ParadisService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        //福地每日广告刷新次数
        $playerSer->setArg(PARADISE_AD_REFRES_GOODS,1,'unset'); 
        $maxEnergy = $this->getMaxEnergy($playerSer);
        $playerSer->setParadise('worker','energy',null,$maxEnergy ,'set');

    }

    public function initParadise(PlayerService $player):void
    {
        if($paradise = $player->getData('paradise')) return;

        $goodsDetail = [];
        for ($i=1; $i < 7; $i++) 
        { 
            $goodsDetail[$i] = $this->getRandGoods();
        }

        $list = $this->getAroundList(10);
        $refresh = $regular = [];
        foreach ($list as $key => $value) 
        {
            $key < 3 ? $refresh[] = $value : $regular[] = $value;
        }
        
        $workerInt = ConfigParam::getInstance()->getFmtParam('HOMELAND_BASIC_WORKER_NUM')+1;
        $workerList = [];
        for ($i=1; $i < $workerInt; $i++) 
        { 
            $workerList[ $i ] = [];
        }

        $paradise = [
            'list'   => $goodsDetail,
            'worker' => [ 'energy' => 100, 'list'   => $workerList ],
            'around' => [ 'refresh' => $refresh, 'regular' => $regular ],
            'reward' => []
        ];
 
        $player->setData('paradise',null,$paradise);
    }
    

    public function getRandGoods():array
    {
        $level = ConfigParadiseLevel::getInstance()->getRewardLevel();
        $gid   = ConfigParadiseReward::getInstance()->getReward($level);
        return  [ 'gid' => $gid,'player' => [],'time' => 0 ,'type' => 1,'exp' => 0,'drift' => 0 ];
    }

    public function checkParadis(PlayerService $player,int $time,int $lastTime):void
    {
        $oldtime = $player->getArg(Consts::HOMELAND_TARGET_REFRESH_TIME);
        if($oldtime && $time >= $oldtime) $player->setArg(Consts::HOMELAND_TARGET_REFRESH_TIME,'','unset');

        $paradise = $player->getData('paradise');
        $rewards  = [];

        $refreshTime = ConfigParam::getInstance()->getFmtParam('HOMELAND_AUTO_REFRESH_TIME_PER');
        foreach ($paradise['list'] as $key => $goodsDetail)
        {
            //失效物品刷新
            if($goodsDetail['gid'] == -1)
            {
                if(time() <  ($goodsDetail['exp']) ) continue;

                $player->setParadise('list','pos',$key,$this->getRandGoods(),'set');

            }else{

                foreach ($goodsDetail['player'] as $pos => $workerDetail) 
                {
                    $timeTotalLen = $workerDetail['time'] + $workerDetail['len'];
                    if(!$workerDetail || $timeTotalLen  > $time) continue;
    
                    if($pos == 'a')
                    {
                        //释放工人
                        foreach ($workerDetail['wid'] as  $wid) 
                        {
                            $player->setParadise('worker','list',$wid,[],'set');
                        }

                        $energy = $player->getData('paradise')['worker']['energy'];
                        $player->setParadise('worker','energy',null,$energy - count($workerDetail['wid']) ,'set');
                        $player->setParadise('list',$key,'player',[],'set');
                        $player->setParadise('list',$key,'gid',-1,'set');
                        $player->setParadise('list',$key,'exp',$timeTotalLen + $refreshTime,'set');
    
                        $rewardConf =  ConfigParadiseReward::getInstance()->getOne($goodsDetail['gid']);
                        $rewards[] = [ $rewardConf['reward']['gid'] => $rewardConf['reward']['num'] ];
    
                        $goodsInfo = ConfigGoods::getInstance()->getOne($rewardConf['reward']['gid']);

                        $record = ['a',$player->getData('openid'),$workerDetail['head'],$workerDetail['nickname'],'成功采集了'.$rewardConf['level'].'级'.$goodsInfo['name'],$timeTotalLen];

                        $this->saveRecord( $player->getData('openid'),$record,$player->getData('site'));
                    }else{
                        //他人
                    }
                }
    
                //广告刷新物品，无人采集 ， 超时删除
                if($goodsDetail['type'] == 2 && time() >= $goodsDetail['time'] && !$goodsDetail['player'])
                {
                    $player->setParadise('list',$key,'player',[],'set');
                    $player->setParadise('list',$key,'gid',-1,'set');
                }
            }

        }

        if($rewards)
        {
            $paradisReward = $paradise['reward'];
    
            foreach ($rewards as $detail) 
            {
                foreach ($detail as $gid => $num) 
                {
                    array_key_exists($gid,$paradisReward) ? $paradisReward[$gid] += $num : $paradisReward[$gid] = $num;
                }
            }
            if($paradisReward) $player->setParadise(null,'reward',null,$paradisReward,'set');
        }

        $this->autoRefreshGoods($player,$time,$lastTime);
    }


    public function autoRefreshGoods(PlayerService $player,int $time,int $lastTime):void
    {
        $mapList = [
            '00' => 1 , '01'  => 1, '02' => 1, '03' => 1, '04' => 1, '05' => 1, '06' => 1, '07' => 1 ,'08' => 1, '09' => 1,
            '10' => 2,'11' => 2,'12' => 2,'13' => 2,'14' => 2,'15' => 2,'16' => 2,'17' => 2,
            '18' => 3,'19' => 3,'20' => 3,'21' => 3,'22' => 3,'22' => 3,'23' => 3
        ];

        $nowHour = $mapList[date('H',$time)];


        $tag  = $player->getArg(PARADISE_AUTO_REFRESH_TIME);

        if($tag != $nowHour)
        {
            $this->refreshGoods($player);
            $player->setArg(PARADISE_AUTO_REFRESH_TIME,$nowHour,'reset');
        } 
        
        if(date('Y-m-d',$time) !== date('Y-m-d',$lastTime ) && $tag != $nowHour)
        {
            $this->refreshGoods($player);
            $player->setArg(PARADISE_AUTO_REFRESH_TIME,$nowHour,'reset');
        } 
        
    }

    public function refreshGoods(PlayerService $player):void
    {

        $paradise = $player->getData('paradise');

        foreach ($paradise['list'] as $pos => $goodsDetail)
        {
            //视频刷新物品及有人拉取的物品 不刷新
            if($goodsDetail['type'] == 2 || $goodsDetail['player']) continue;
            $newGoods = ParadisService::getInstance()->getRandGoods();
            $player->setParadise('list','pos',$pos,$newGoods,'set');
        }

    }

    public function getAroundList(int $num ):array
    {
        return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($num) {
                return $redis->sRandMember(USER_SET,$num);
        });
    }

    public function getShowData(PlayerService $player):array
    {
        $paradise = $player->getData('paradise');

        $list   = [];
        $limitConfig =  ConfigParam::getInstance()->getFmtParam('HOMELAND_SINGLE_BOX_MOUSE_LIMIT_CONFIG');
        $addNum = $this->getWorkerStatus($paradise['worker']['energy']);
        foreach ($paradise['list'] as $key => $value)
        {
            $worker = [];
            
            foreach ($value['player'] as $pos => $workerDetail) 
            {
                $worker[$pos] = $workerDetail;
                if(!$workerDetail) continue;
                
                $rtime = ($workerDetail['time'] + $workerDetail['len']) - time();
                $worker[$pos] = [
                    'who'      => $pos == 'a' ? 1 : 2,
                    'status'   => $pos,
                    'active'   => 1,
                    'nickname' => $workerDetail['nickname'],
                    'head'     => $workerDetail['head'],
                    'wCount'   => count($workerDetail['wid']),
                    'time'     => $rtime > 0 ? $rtime : 0,
                ];
            }

            $goodsConfig =  ConfigParadiseReward::getInstance()->getOne($value['gid']);

            $remianTime  = $value['time'] - time();

            $needTime = $value['gid'] != -1 ? $this->getGoodsNeedTime($value['gid'],$addNum) : 0;
            $drift = $value['drift'];
            if($value['player'])
            {
                //偏移量等于 总 - 过去的
                $aRemianTime = abs($value['player']['a']['time'] + $value['player']['a']['len'] - time());
                // $drift  += $needTime - $aRemianTime;
                // $remainTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
                $drift  += $value['player']['a']['len'] - $aRemianTime;// + $value['drift'];
            }
                        

            $list[] = [
                'id'           => $key,
                'gid'          => $value['gid'],
                'exp'          => $value['gid'] > 0 ? 0 : $value['exp'] - time(),
                'type'         => $value['type'],
                'player'       => array_values($worker)  ,
                'time'         => $remianTime > 0 ? $remianTime : 0,
                'drift'        => -$drift,
                'need_time'    => $needTime,
                'worker_limit' => $value['gid'] != -1 ? $limitConfig[$goodsConfig['level']- 1] : 0,
            ];
        }

        $freeCount  = 0;
        $workerTask = [];
        $countList  = $this->getWorkerTaskCount($paradise['worker']['list']);

        foreach ($paradise['worker']['list'] as $key => $value)
        {
            if($value)
            {
                if($value['uid'] == 'i')
                {
                    $goodsDetail = $paradise['list'][$value['id']]['player'];
                    $gid = $paradise['list'][$value['id']]['gid'];
                    $workerTask[$value['uid']][$value['id']][$gid] = [
                        'id'     => $key,
                        'who'    => 1,
                        'status' => 'a',
                        'head'   => $player->getData('user')['head'],
                        'nickname' => $player->getData('user')['nickname'],
                        'gid'    => $gid,
                        'time'   => ($goodsDetail['a']['time'] + $goodsDetail['a']['len']) - time(),
                        'wCount' => count($countList[$value['uid']][$value['id']]),
                    ];
                }else{
                    list($_prefix,$uid,$site) = explode(':',$value['uid']);
                    $aroundPlayerSer = new PlayerService($uid,$site);
                    if(is_null($aroundPlayerSer->getData('last_time'))) continue;

                    $aroundParadise = $aroundPlayerSer->getData('paradise');

                    $goodsDetail = $aroundParadise['list'][$value['id']]['player'];
                    $gid = $aroundParadise['list'][$value['id']]['gid'];
                    $workerTask[$value['uid']][$value['id']][$gid] = [
                        'id'     => $key,
                        'who'    => 1,
                        'status' => 'g',
                        'head'   => $aroundPlayerSer->getData('user')['head'],
                        'nickname' => $aroundPlayerSer->getData('user')['nickname'],
                        'gid'    => $gid,
                        'time'   => ($goodsDetail['g']['time'] + $goodsDetail['g']['len']) - time(),
                        'wCount' => count($countList[$value['uid']][$value['id']]),
                    ];

                }
            }else{
                $freeCount++;
            };
        }

        $reward = $paradise['reward'];
        $rewards = [];
        foreach ($reward as $gid => $num) 
        {
            $rewards[] = ['type' => GOODS_TYPE_1,'gid' => $gid ,'num' => $num ];
        }

        if($rewards)
        {
            $player->goodsBridge($rewards,'福地自家采集');
            $player->setParadise(null,'reward',null,[],'set');
        } 

        $data = [];

        foreach ($workerTask as $key2 => $value2) 
        {
            foreach ($value2 as $key3 => $value3) 
            {
                foreach ($value3 as $key4 => $value4) 
                {
                    $data[] = [
                        'id'  => $value4['id'],
                        'gid' => $value4['gid'],
                        'player' => [
                            [
                                'status' =>  "a",
                                'active' =>  1,
                                'time'   =>  $value4['time'],
                                'wCount' =>  $value4['wCount'],
                                'who'    =>  $value4['who'],
                                'head'    =>  $value4['head'],
                                'nickname'    =>  $value4['nickname'],
                            ]
                        ]
                    ];
                }
            }
        }

        $workerNum = count($paradise['worker']['list']);
        //取下一个升级消耗
        $costConfig = ConfigParam::getInstance()->getFmtParam('HOMELAND_WORKER_COST');

        $workerCost = $workerNum - 1 >= count($costConfig) ? [] : $costConfig[$workerNum - 1];
        $cost = ConfigParam::getInstance()->getFmtParam('HOMELAND_PAY_REFRESH_COST');

        $cost['type'] = GOODS_TYPE_1;
        if($workerCost) $workerCost['type'] =  GOODS_TYPE_1;

        return [
            'list'   => $list,
            'worker' => [
                'total' => $workerNum,
                'free'  => $freeCount,
                'list'  => array_values($data),
            ],
            'reward' => $rewards,
            'config' => [
                'refresh_cost'     => $cost,
                'refresh_ad_limit' => ConfigParam::getInstance()->getFmtParam('HOMELAND_FREE_REFRESH_TIME'),
                'refresh_ad_use'   => $player->getArg(PARADISE_AD_REFRES_GOODS),
                'max_energy'       => $this->getMaxEnergy($player),
                'worker_status'    => ConfigParam::getInstance()->getFmtParam('HOMELAND_ENERGY_DIVIDE'),
                'worker_cost'      => $workerCost
            ],
            'energy'  => $paradise['worker']['energy'],
        ];
    }

    public function getWorkerTaskCount(array $workerList):array
    {
        //好友家一次只能一次
        $list = [];
        foreach ($workerList as $key => $value) 
        {
            if(!$value) continue;
            $list[$value['uid']][$value['id']][] = $key;
        }

        return $list;
    }

    public function getFreeWorker(array $workerList):array
    {
        //好友家一次只能一次
        $workerId = [];
        foreach ($workerList as $wid => $value) 
        {
            if($value) continue;
            $workerId[] = $wid;
        }
        return $workerId;
    }

    public function getGoodsNeedTime(int $gid,float $addNum):int
    {
        $config = ConfigParadiseReward::getInstance()->getOne($gid);
        return mul($config['time_param'],$addNum);
    }

    public function getWorkerStatus(int $energy):float
    {
        // HOMELAND_ENERGY_DIVIDE	  100|50|25|15
        // HOMELAND_ENERGY_SPEED	  20|40|80|4000
        // HOMELAND_ENERGY_COPE_SPEED 500|300|100|10

        $config = ConfigParam::getInstance()->getFmtParam('HOMELAND_ENERGY_DIVIDE');
        $count  = count($config) -1 ;
        $list   = [];
        foreach ($config as $key => $value) 
        {
            //正常设置上限 20000
            if(!$key)  $list[] = ['begin' => 20000,'end' => $config[ $key+1 ]+1 ];
            if($key > 0 && $key < $count)  $list[] = ['begin' => $value,'end' => $config[ $key+1 ]+1 ];
            if($key == $count) $list[] = ['begin' => $value,'end' => 0 ];
        }

        $num = 8000;
        $addConfig = ConfigParam::getInstance()->getFmtParam('HOMELAND_ENERGY_SPEED');

        foreach ($list as $k => $val)
        {
            if($energy < $val['begin'] && $energy > $val['end'] || $energy == $val['begin'] || $energy == $val['end'] ) $num = $addConfig[$k];
        }
        
        return $num;
    }

    public function saveRecord(string $playerid,array $content,int $site):void
    {
        $rid      = md5(SnowFlake::make(rand(0,31),rand(0,127)));
        $cacheKey = Keys::getInstance()->getFudiRecordKey($playerid,$site);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$rid,$content){
            return $redis->hSet($cacheKey,$rid,json_encode($content));
        });
    }

    public function getRecord(string $playerid,int $site):array
    {
        $cacheKey = Keys::getInstance()->getFudiRecordKey($playerid,$site);
        $content  = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey){
            return $redis->hGetAll($cacheKey);
        });

        $list = [];
        $now  = time();
        foreach ($content as $rid => $string) 
        {
            list($status,$_uid,$head,$nickname,$desc,$time) = json_decode($string,true);
            $list[$time] = [
                'rid'       => $rid,
                'nickname'  => $nickname,
                'head'      => $head,
                'status'    => $status,
                'desc'      => $desc,
                'time'      => date('Ymd',$time) === date('Ymd',$now) ? date('H:i',$time) : ceil(($now+28800)/DAY_LENGHT) - ceil(($time+28800) /DAY_LENGHT).'天前',
            ];
        }
        ksort($list);
        return array_reverse($list);
    }

    public function getAutoRefreshTimeStatus():array
    {

        $config = ConfigParam::getInstance()->getFmtParam('HOMELAND_AUTO_REFRESH_TIME');
        $count  = count($config) -1 ;
        $list   = [];
        foreach ($config as $key => $value) 
        {
            if($key < $count)  $list[ $value ] = ['begin' => $value,'end' => $config[ $key+1 ] - 1 ];
            if($key == $count) $list[ $value ] = ['begin' => $value,'end' => 24 ];
        }
        
        return $list;
    }

    public function getMaxEnergy(PlayerService $playerSer):int
    {
        $paradiseNum = ConfigParam::getInstance()->getFmtParam('HOMELAND_ENERGY_DIVIDE')[0];
        $comradeAdd  = ComradeService::getInstance()->getLvStageByTalent($playerSer,60003);
        
        return $paradiseNum + $comradeAdd;
    }

    public function getAroundInfo(array $around,array $workers):array
    {
        $homes =  array_column(array_filter($workers),'uid','uid');

        $list = [];
        foreach ($around as $type => $playerids) 
        {
            foreach ($playerids as $playerid) 
            {   
                list($_prefix,$uid,$site) = explode(':',$playerid);

                $playerSer = new PlayerService($uid,$site);
                if(is_null($playerSer->getData('last_time'))) continue;
                $goodsList = $playerSer->getData('paradise','list');
                $goods = [];
                foreach ($goodsList as $pos =>  $detail) 
                {
                    $goods[$pos] = ['id' => $pos,'gid' => $detail['gid']];
                }

                $list[$type][] = [
                    // 'rid'      =>  md5($playerid),
                    'rid'      =>  $playerid,
                    'head'     => $playerSer->getData('user','head'),
                    'nickname' => $playerSer->getData('user','nickname'),
                    'goods'    => $goods,
                    'state'    => in_array($playerid,$homes) ? 1 : 0,
                ];
            }
        }

        return $list;
    }

    public function getAroundPlayerInfo(array $playerInfo):array
    {

        $playerSer = new PlayerService($playerInfo['uid'],$playerInfo['site']);

        if(is_null($playerSer->getData('last_time'))) return [];

        $detail = [];
        $goodsList = $playerSer->getData('paradise','list');

        foreach ($goodsList as $pos => $value) 
        {
            $worker = [];
            foreach ($value['player'] as $identity => $workerDetail) 
            {
                $worker[$identity] = $workerDetail;
                if(!$workerDetail) continue;
                
                $rtime = ($workerDetail['time'] + $workerDetail['len']) - time();
                $worker[$identity] = [
                    'who'      => $identity == 'a' ? 1 : 2,
                    'status'   => $identity,
                    'active'   => 1,
                    'nickname' => $workerDetail['nickname'],
                    'head'     => $workerDetail['head'],
                    'wCount'   => count($workerDetail['wid']),
                    'time'     => $rtime > 0 ? $rtime : 0,
                ];
            }

            $detail[ $pos ] = [
                'id'           => $pos,
                'gid'          => $value['gid'],
                'exp'          => 0,
                'type'         => $value['type'],
                'player'       => array_values($worker)  ,
                'time'         => 0,
                'drift'        => 0,
                'need_time'    => 0,
                'worker_limit' => 0,
            ];
        }

        return $detail;
    }

    public function existsPlayer(array $around,string $rid):array
    {
        $list = [];
        
        foreach ($around as $type => $playerids) 
        {
            foreach ($playerids as $playerid) 
            {   
                list($_prefix,$uid,$site) = explode(':',$playerid);
                // $list[md5($playerid)] = ['uid' => $uid,'site' => $site];
                $list[$playerid] = ['uid' => $uid,'site' => $site];
            }
        }

        return array_key_exists($rid,$list) ? $list[$rid] : [];
    }
}
