<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigActivityDaily;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Utils\Keys;
use EasySwoole\Component\CoroutineSingleTon;

class ShangGuService
{
    use CoroutineSingleTon;

    public function initXanYuan(PlayerService $playerSer):void
    {
        //解锁初始化
        if($shanggu = $playerSer->getData('shanggu')) return;

        $shanggu   = [
            'repair' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
                6 => 0,
                7 => 0,
            ]
        ];
        $playerSer->setShangGu('',0,$shanggu,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);
        //活动签到重置
        $this->initSignIn($playerSer,$startTimestamp,ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0);
    }

    public function initSignIn(PlayerService $playerSer,$startTimestamp,$resetInterval):void
    {
        if($this->checkAndResetActivity($startTimestamp, $resetInterval)){
            $playerSer->setXianYuan('repair',0,[1 => 0,2 => 0,3 => 0,4 => 0,5 => 0,6 => 0,7 => 0],'set');
            $playerSer->setXianYuan('sign_in',0,[],'set');
            $playerSer->setArg(Consts::SHANGGU_SIGNIN_GIFT,0,'unset');
        }
    }

    public function getShangGuFmtData(PlayerService $playerSer):array
    {
        $shangGuData = $playerSer->getData('shanggu');

        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);

        return [
            'signin'    => $this->getSignIn($playerSer),
            'repair'    => $shangGuData['repair'],
            'config'    => [
                'current_singin'    => $this->checkAndDay($startTimestamp,ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0),
                'residue_signin'    => $this->checkResidueTime($startTimestamp,ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0),
                'buy_sign_state'    => $playerSer->getArg(Consts::SHANGGU_SIGNIN_GIFT),
            ],
        ];
    }

    public function getSignIn(PlayerService $playerSer):array
    {
        $shangGuData = $playerSer->getData('shanggu');

        $nodeKey        = Keys::getInstance()->getNodeListKey();
        $site           = $playerSer->getData('site');
        $startTimestamp = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($nodeKey,$site) {
            return $redis->hGet($nodeKey, $site);
        });
        $resetInterval  = ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0;

        $config = ConfigActivityDaily::getInstance()->getOne(2);
        $sign   = [];
        foreach($config['data'] as $key => $value)
        {
            $index          = $key + 1;
            $sign[$index]   = ['freeReward' => 0, 'paidReward' => 0];

            $day = $this->checkAndDay($startTimestamp,$resetInterval);
            if($day >= $index)
            {
                $sign[$index] = ['freeReward' => 1, 'paidReward' => 0];
                if($playerSer->getArg(Consts::SHANGGU_SIGNIN_GIFT)) $sign[$index]['paidReward'] = 1;//TODO
            }

            if(array_key_exists('sign_in',$shangGuData))
            {
                if(array_key_exists($index,$shangGuData['sign_in']))
                {
                    $freeReward = $shangGuData['sign_in'][$index]['freeReward'];
                    $paidReward = $shangGuData['sign_in'][$index]['paidReward'];

                    if($playerSer->getArg(Consts::SHANGGU_SIGNIN_GIFT))//TODO
                    {
                        if($paidReward == 0) $paidReward = 1;
                    }
                    $sign[$index] = ['freeReward' => $freeReward, 'paidReward' => $paidReward];
                }
            }
        }
        return $sign;
    }

    function aggregateAwards(array $awards):array
    {
        $result = [];

        foreach ($awards as $repeatReward) {
            $gid = $repeatReward['gid'];
            $num = $repeatReward['num'];

            if (isset($result[$gid])) {
                $result[$gid]['num'] += $repeatReward['num']; // 如果已经存在该 gid，则累加数量
            } else {
                $result[$gid] = $repeatReward; // 否则，添加新的记录
            }
        }
        $resultArray = array_values($result);// 将结果转换为索引数组

        return $resultArray;
    }

    function checkAndDay($startTimestamp, $resetInterval) {

        $startTimestamp   = strtotime(date('Y-m-d',$startTimestamp));
        $currentTimestamp = strtotime(date('Y-m-d',time()));

        $timeElapsed = $currentTimestamp - $startTimestamp; // 活动开始时间与当前时间的时间差
        $timeSinceLastReset = $timeElapsed % $resetInterval; // 距离上次重置的时间差
        return $timeSinceLastReset / 86400 + 1;
    }
    
    function checkAndResetActivity($startTimestamp, $resetInterval) {

        $startTimestamp   = strtotime(date('Y-m-d',$startTimestamp));
        $currentTimestamp = strtotime(date('Y-m-d',time()));

        if($currentTimestamp == $startTimestamp) return false;

        $timeElapsed = $currentTimestamp - $startTimestamp; // 活动开始时间与当前时间的时间差
        $timeSinceLastReset = $timeElapsed % $resetInterval; // 距离上次重置的时间差
        if ($timeSinceLastReset <= 0) {
            return true;
        }else{
            return false;
        }
    }

    function checkResidueTime($startTimestamp, $resetInterval) {
        $startTimestamp   = strtotime(date('Y-m-d',$startTimestamp));
        $currentTimestamp = time();
        $timeElapsed = $currentTimestamp - $startTimestamp; // 活动开始时间与当前时间的时间差
        $timeSinceLastReset = $timeElapsed % $resetInterval; // 距离上次重置的时间差
        return $resetInterval - $timeSinceLastReset;
    }

    function getOpeningTime(PlayerService $playerSer){
        //开服时间
        $nodeKey        = Keys::getInstance()->getNodeListKey();
        $site           = $playerSer->getData('site');
        $startTimestamp = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($nodeKey,$site) {
            return $redis->hGet($nodeKey, $site);
        });
        $startTimestamp = strtotime('+8 days',$startTimestamp);
        return $startTimestamp;
    }

    public function getShangGuRedPointInfo(PlayerService $playerSer)
    {
        $red            = false;
        $shangGuSignIn  =  $this->getSignIn($playerSer);
        $startTimestamp = $this->getOpeningTime($playerSer);
        $isDay          = $this->checkAndDay($startTimestamp,ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0);
        if(isset($shangGuSignIn[$isDay]))
        {
            if($shangGuSignIn[$isDay]['freeReward'] == 1 || $shangGuSignIn[$isDay]['paidReward'] == 1) $red = true;
        }

        return $red;
    }
}