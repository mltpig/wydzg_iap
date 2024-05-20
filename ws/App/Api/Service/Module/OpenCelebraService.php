<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\TaskService;
use App\Api\Service\ShopService;
use App\Api\Utils\Consts;
use App\Api\Utils\Keys;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTask;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class OpenCelebraService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);
        //每日任务重置
        $this->initTask($playerSer);
        //道具重置
        //$this->initProp($playerSer,$startTimestamp,ConfigParam::getInstance()->getFmtParam('') + 0);
    }

    public function check(PlayerService $playerSer,int $time):void
    {
        //每日登录根据配置与当前时间差
        $task103 = TaskService::getInstance()->getTasksByType($playerSer->getData('task'),103);//活动任务
        if(!$task103) $this->initTask($playerSer);

        $task104 = TaskService::getInstance()->getTasksByType($playerSer->getData('task'),104);//活动任务
        if(!$task104) $this->initTask($playerSer);
    }

    public function initTask(PlayerService $playerSer):void
    {
        $celebra103 = ConfigTask::getInstance()->getCelebration103InitTask();
        $celebra104 = ConfigTask::getInstance()->getCelebration104InitTask();
        $celebra    = $celebra103 + $celebra104;

        foreach ($celebra as $taskid => $detail) {
            $playerSer->setTask($taskid,0,0,'set');
            $playerSer->setTask($taskid,1,0,'set');
        }
    }

    public function initProp(PlayerService $playerSer,$startTimestamp,$resetInterval):void
    {
        if($this->checkAndResetActivity($startTimestamp, $resetInterval)){
            $gid = ConfigParam::getInstance()->getFmtParam('');
            $playerSer->goodsBridge([['gid' => $gid, 'type' => GOODS_TYPE_1, 'num' => -$playerSer->getGoods($gid)]],'重置开服庆典道具');
        }
    }

    public function getOpenCelebraFmtData(PlayerService $playerSer):array
    {
        $openCelebraTask = $playerSer->getData('task');

        //开服时间
        $startTimestamp = $this->getOpeningTime($playerSer);

        return [
            'schedule_task' => $this->getOpenCelebra103Task($openCelebraTask),
            'task'          => $this->getOpenCelebra104Task($openCelebraTask),
            'gift'          => ShopService::getInstance()->getShowList($playerSer,106),
            'config'        => [
                'residue_time' => 0
            ],
        ];
    }

    public function getOpenCelebra103Task(array $task):array
    {
        $taskList = [];
        $task103 = ConfigTask::getInstance()->getCelebration103InitTask();
        foreach ($task103 as $taskid => $detail) 
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
    public function getOpenCelebra104Task(array $task):array
    {
        $taskList = [];
        //$task103 = ConfigTask::getInstance()->getCelebration103InitTask();
        $task104 = ConfigTask::getInstance()->getCelebration104InitTask();
        //$openCelebraTask = $task103 + $task104;
        foreach ($task104 as $taskid => $detail) 
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
        $nodeKey        = Keys::getInstance()->getNodeListKey(); // 开服时间
        $site           = $playerSer->getData('site');
        $startTimestamp = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($nodeKey,$site) {
            return $redis->hGet($nodeKey, $site);
        });
        return $startTimestamp;
    }
}
