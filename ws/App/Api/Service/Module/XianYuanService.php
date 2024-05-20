<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\TaskService;
use App\Api\Service\ShopService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTask;
use App\Api\Table\Activity\ConfigFund;
use App\Api\Table\Activity\ConfigActivityDaily;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Utils\Keys;
use EasySwoole\Component\CoroutineSingleTon;

class XianYuanService
{
    use CoroutineSingleTon;

    public function initXanYuan(PlayerService $playerSer):void
    {
        //解锁初始化
        if($xianyuan = $playerSer->getData('xianyuan')) return;

        $xianyuan   = [
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
        $playerSer->setXianYuan('',0,$xianyuan,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);
        //每日任务重置
        $this->initTask($playerSer);
        //活动签到重置
        $this->initSignIn($playerSer,$startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_DAILY_RESET_TIME') + 0);
        //活动进度及道具重置
        $this->initWelfare($playerSer,$startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_FUND_RESET_TIME') + 0);
        //活动礼包重置
        $this->initGift($playerSer,$startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_RESET_TIME') + 0);
    }

    public function check(PlayerService $playerSer,int $time):void
    {
        //每日登录根据配置与当前时间差
        $tasks = TaskService::getInstance()->getTasksByType($playerSer->getData('task'),102);//活动任务
        if(!$tasks)
        {
            $this->initTask($playerSer);
        }
    }

    public function initTask(PlayerService $playerSer):void
    {
        $xianYuan = ConfigTask::getInstance()->getXianYuanInitTask();
        foreach ($xianYuan as $taskid => $detail) 
        {
            $playerSer->setTask($taskid,0,0,'set');
            $playerSer->setTask($taskid,1,0,'set');
        }
    }

    public function initSignIn(PlayerService $playerSer,$startTimestamp,$resetInterval):void
    {
        if($this->checkAndResetActivity($startTimestamp, $resetInterval)){
            $playerSer->setXianYuan('repair',0,[1 => 0,2 => 0,3 => 0,4 => 0,5 => 0,6 => 0,7 => 0],'set');
            $playerSer->setXianYuan('sign_in',0,[],'set');
            $playerSer->setArg(Consts::XIANYUAN_SIGNIN_GIFT,0,'unset');
        }
    }

    public function initWelfare(PlayerService $playerSer,$startTimestamp,$resetInterval):void
    {
        if($this->checkAndResetActivity($startTimestamp, $resetInterval)){
            $playerSer->setXianYuan('9',0,[],'set');
            $gid = ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_RESET_ITEM');
            $playerSer->goodsBridge([['gid' => $gid, 'type' => GOODS_TYPE_1, 'num' => -$playerSer->getGoods($gid)]],'重置仙缘道具');
            $playerSer->setArg($gid,0,'unset');
            $playerSer->setArg(Consts::XIANYUAN_FUND_GROUP9,0,'unset');
        }
    }

    public function initGift(PlayerService $playerSer,$startTimestamp,$resetInterval)
    {
        $playerSer->setArg(Consts::XIANYUAN_GIFT_FREE_REWARD,0,'unset');

        if($this->checkAndResetActivity($startTimestamp, $resetInterval)){
            if($playerSer->getArg(Consts::XIANYUAN_GIFT_SCHEDULE) >= 3){ //购买礼包任务进度大于等于3
                $playerSer->setArg(Consts::XIANYUAN_GIFT_SCHEDULE,0,'unset');
                $playerSer->setArg(Consts::XIANYUAN_GIFT_REWARD,0,'unset');
            }
        }
    }

    public function getXianYuanFmtData(PlayerService $playerSer):array
    {
        $xianYuanTask = $playerSer->getData('task');
        $xianYuanData = $playerSer->getData('xianyuan');

        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);

        return [
            'task'      => $this->getXianYuanTask($xianYuanTask),
            'welfare'   => $this->getFundGroupData($playerSer,9),
            'signin'    => $this->getSignIn($playerSer),
            'repair'    => $xianYuanData['repair'],
            'gift'      => ShopService::getInstance()->getShowList($playerSer,101),
            'config'    => [
                'residue_task'      => strtotime("tomorrow") - time(),
                'residue_welfare'   => $this->checkResidueTime($startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_FUND_RESET_TIME') + 0),
                'residue_signin'    => $this->checkResidueTime($startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_DAILY_RESET_TIME') + 0),
                'residue_gift'      => $this->checkResidueTime($startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_GIFTBAG_RESET_TIME') + 0),
                'current_singin'    => $this->checkAndDay($startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_DAILY_RESET_TIME') + 0),
                'schedule'          => $playerSer->getArg(Consts::XIANYUAN_GIFT_SCHEDULE),
                'free_reward'       => $playerSer->getArg(Consts::XIANYUAN_GIFT_FREE_REWARD),
                'task_reward'       => $playerSer->getArg(Consts::XIANYUAN_GIFT_REWARD),
                'buy_fund_state'    => $playerSer->getArg(Consts::XIANYUAN_FUND_GROUP9),
                'buy_sign_state'    => $playerSer->getArg(Consts::XIANYUAN_SIGNIN_GIFT),
            ],
        ];
    }

    public function getXianYuanTask(array $task):array
    {
        $taskList = [];

        $xianYuanTask = ConfigTask::getInstance()->getXianYuanInitTask();
        foreach ($xianYuanTask as $taskid => $detail) 
        {
            $taskList[] = [
                'id'     => $taskid,
                'state'  => $task[$taskid][1],
                'title'  => $detail['name'],
                'target' =>[
                    'complete_type'   => $detail['complete_type'],
                    'complete_params' => $detail['complete_params'],
                    'val'             => $task[$taskid][0],
                ],
            ];
        }

        return $taskList;
    }

    public function getFundGroupData(PlayerService $playerSer, $group):array
    {
        $config = ConfigFund::getInstance()->getOne($group);

        return $this->getGroupWhereList($playerSer, $group, $config);
    }

    public function getGroupWhereList(PlayerService $playerSer, int $group, array $fundConfig):array
    {
        $xianyuanData = $playerSer->getData('xianyuan');

        $funds  = [];
        foreach($fundConfig['data'] as $key_reward => $value_reward)
        {
            $funds[$group][$value_reward['id']]    = ['freeReward' => 0, 'paidReward' => 0];

            // 是否达成条件
            $taskConfig = ['complete_type' => $value_reward['completeType'], 'complete_params' => $value_reward['completeParams']];
            list($num,$state) = TaskService::getInstance()->getTaskState($playerSer,0,$taskConfig);

            if($state)
            {
                $funds[$group][$value_reward['id']] = ['freeReward' => 1, 'paidReward' => 0];
                if($playerSer->getArg(Consts::XIANYUAN_FUND_GROUP9)) $funds[$group][$value_reward['id']]['paidReward'] = 1;//TODO 购买过基金
            }

            // 已激活
            if(array_key_exists($group,$xianyuanData))
            {
                if(array_key_exists($value_reward['id'],$xianyuanData[$group]))
                {
                    $freeReward = $xianyuanData[$group][$value_reward['id']]['freeReward'];
                    $paidReward = $xianyuanData[$group][$value_reward['id']]['paidReward'];

                    if($playerSer->getArg(Consts::XIANYUAN_FUND_GROUP9))//TODO 购买过基金
                    {
                        if($paidReward == 0) $paidReward = 1; // 没领取过状态为1
                    }

                    $funds[$group][$value_reward['id']] = ['freeReward' => $freeReward, 'paidReward' => $paidReward];
                }
            }
        }

        return $funds;
    }

    public function getSignIn(PlayerService $playerSer):array
    {
        $xianyuanData   = $playerSer->getData('xianyuan');

        $nodeKey        = Keys::getInstance()->getNodeListKey();
        $site           = $playerSer->getData('site');
        $startTimestamp = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($nodeKey,$site) {
            return $redis->hGet($nodeKey, $site);
        });
        $resetInterval  = ConfigParam::getInstance()->getFmtParam('ZHENGJI_DAILY_RESET_TIME') + 0;

        $config = ConfigActivityDaily::getInstance()->getOne(1);
        $sign   = [];
        foreach($config['data'] as $key => $value)
        {
            $index          = $key + 1;
            $sign[$index]   = ['freeReward' => 0, 'paidReward' => 0];

            $day = $this->checkAndDay($startTimestamp,$resetInterval);
            if($day >= $index)
            {
                $sign[$index] = ['freeReward' => 1, 'paidReward' => 0];
                if($playerSer->getArg(Consts::XIANYUAN_SIGNIN_GIFT)) $sign[$index]['paidReward'] = 1;//TODO 购买过礼包
            }

            if(array_key_exists('sign_in',$xianyuanData))
            {
                if(array_key_exists($index,$xianyuanData['sign_in']))
                {
                    $freeReward = $xianyuanData['sign_in'][$index]['freeReward'];
                    $paidReward = $xianyuanData['sign_in'][$index]['paidReward'];

                    if($playerSer->getArg(Consts::XIANYUAN_SIGNIN_GIFT))//TODO 购买过礼包
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
        return $startTimestamp;
    }

    public function getXianYuanRedPointInfo(PlayerService $playerSer):array
    {
        $xianYuanTask = $playerSer->getData('task');

        $red            = [false,false,false,false,false];
        $xianYuanTask   =  $this->getXianYuanTask($xianYuanTask);
        foreach($xianYuanTask as $k => $v)
        {
            if($v['state'] == 1) $red[0] = true;
        }

        $xianYuanFundGroup  =  $this->getFundGroupData($playerSer,9);
        foreach($xianYuanFundGroup[9] as $key => $value)
        {
            if($value['freeReward'] == 1 || $value['paidReward'] == 1) $red[1] = true;
        }

        $xianYuanSignIn =  $this->getSignIn($playerSer);
        $startTimestamp = $this->getOpeningTime($playerSer);
        $isDay          = $this->checkAndDay($startTimestamp,ConfigParam::getInstance()->getFmtParam('ZHENGJI_DAILY_RESET_TIME') + 0);
        if($xianYuanSignIn[$isDay]['freeReward'] == 1 || $xianYuanSignIn[$isDay]['paidReward'] == 1) $red[2] = true;

        if(empty($playerSer->getArg(Consts::XIANYUAN_GIFT_FREE_REWARD))) $red[3] = true;

        if(empty($playerSer->getArg(Consts::XIANYUAN_GIFT_REWARD)) && $playerSer->getArg(Consts::XIANYUAN_GIFT_SCHEDULE) >= 3) $red[4] = true;

        return $red;
    }
}