<?php
namespace App\Api\Service;
use App\Api\Utils\Consts;
use App\Api\Table\Activity\NewYear;
use App\Api\Table\ConfigTask;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\OptionalGiftbag as ActivityOptionalGiftbag;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Table\Activity\FirstRecharge;

class ActivityService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        //每日福利限制次数重置
        $playerSer->setArg(COUNTER_DAILY_REWARD,1,'unset');
        $playerSer->setArg(Consts::ACTIVITY_TAG_5,1,'unset');
        $playerSer->setArg(Consts::ACTIVITY_TAG_6,1,'unset');
        $playerSer->setArg(Consts::ACTIVITY_CHANNEL_TASK_2,1,'unset');
        $playerSer->setArg(Consts::ACTIVITY_CHANNEL_TASK_3,1,'unset');
        //自选礼包重置次数
        $options = ActivityOptionalGiftbag::getInstance()->getAll();
        foreach ($options as $groupid => $config) 
        {
            $playerSer->setArg($groupid,1,'unset');
        }

        //七日签到
        if($playerSer->getTmp('sign_in'))
        {
            list($day,$idState) = $playerSer->getTmp('sign_in');
            $playerSer->setTmp('sign_in',[ $day + 1 ,$idState ]);
        }

        //新春活动 1001=活动期间累计登录N天
        TaskService::getInstance()->setVal($playerSer,1001,1,'add');
        
    }

    public function check(PlayerService $playerSer,int $time):void
    {
        //春节活动 任务初始化  春节活动时间：2.9-2.16
        $begin = strtotime(Consts::ACTIVITY_NEW_YEAR_BEGIN);
        $end   = strtotime(Consts::ACTIVITY_NEW_YEAR_END);
        if($time > $begin && $time < $end || $time == $begin)
        {
            //如果未存在，则添加
            $tasks = TaskService::getInstance()->getTasksByType($playerSer->getData('task'),101);
            if(!$tasks)
            {
                $newYear = ConfigTask::getInstance()->getNewYearInitTask();
                foreach ($newYear as $taskid => $detail) 
                {
                    $init = $detail['complete_type'] == 1001 ? 1 : 0; 
                    $playerSer->setTask($taskid,0,$init,'set');
                    $playerSer->setTask($taskid,1,0,'set');
                }
            }
        }elseif($time > $end){
            //如果存在，且时间小于结束时间 则删除
            $tasks = TaskService::getInstance()->getTasksByType($playerSer->getData('task'),101);
            foreach ($tasks as $key => $taskid) 
            {
                $playerSer->setTask($taskid,0,0,'unset');
            }
            foreach (Consts::ACTIVITY_NEW_YEAR_TAG as  $tag) 
            {
                $playerSer->setArg($tag,0,'unset');
            }
        }

        //每日福利限制次数重置
        $recTime = $playerSer->getArg(COUNTER_DAILY_REWARD_CD);
        if(!$recTime) return;
        $cd = ConfigParam::getInstance()->getOne('AD_REWARD_CD');
        if(($recTime + $cd) > $time ) return ;

        $playerSer->setArg(COUNTER_DAILY_REWARD_CD,1,'unset');
    }

    public function getDailyRewardFmt(PlayerService $playerSer):array
    {
        $remain = $playerSer->getArg(COUNTER_DAILY_REWARD_CD);
        if($remain)
        {
            $limit = ConfigParam::getInstance()->getOne('AD_REWARD_CD');
            $remain = ($limit + $remain) - time();
        }
        return [
            'remain' => $remain,
            'count'  => $playerSer->getArg(COUNTER_DAILY_REWARD),
        ];
    }

    public function getSignInState(PlayerService $playerSer):int
    {
        list($day,$idState)  = $playerSer->getTmp('sign_in');
        
        return $day > 7 ? 0 : 1;
    }

    public function getCircleOfFriendsRewardConfig():array
    {
        // type取值	    说明	                       subKey	GameClubDataByType.value
        // 1	    加入该游戏圈时间	               无需传入	    秒级Unix时间戳
        // 3	    用户禁言状态	                   无需传入	    0：正常 1：禁言
        // 4	    当天(自然日)点赞贴子数	            无需传入	
        // 5	    当天(自然日)评论贴子数	            无需传入	
        // 6	    当天(自然日)发表贴子数	            无需传入	
        // 7	    当天(自然日)发表视频贴子数	        无需传入	
        // 8	    当天(自然日)赞官方贴子数	        无需传入	
        // 9	    当天(自然日)评论官方贴子数	        无需传入	
        // 10	    当天(自然日)发表到本圈子话题的贴子数 传入话题id，从mp-游戏圈话题管理处获取
        
        //首次加入游戏圈：100000=100  100003=5000  100029=20
        //每日评论1次：100000=50  100016=5
        //每日点赞3次：100000=50  100004=100
        return [
            Consts::ACTIVITY_CHANNEL_TASK_1 => [ 
                'type'   => 1,
                'target' => 1,
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANYU,'num' => 100 ],
                    ['type' => GOODS_TYPE_1,'gid' => LINGSHI,'num' => 5000 ],
                    ['type' => GOODS_TYPE_1,'gid' => LIULIZHU,'num' => 20 ],
                ]
            ],
            Consts::ACTIVITY_CHANNEL_TASK_2 => [ 
                'type'   => 5,
                'target' => 1,
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANYU,'num' => 10 ],
                    ['type' => GOODS_TYPE_1,'gid' => QINGPU,'num' => 1 ],
                ]
            ],
            Consts::ACTIVITY_CHANNEL_TASK_3 => [ 
                'type'   => 4,
                'target' => 3,
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => 40 ],
                    ['type' => GOODS_TYPE_1,'gid' => LINGSHI,'num' => 1000 ],
                ]
            ],
        ];
    }

    public function getZijieJumpReward():array
    {
        return [
            Consts::ACTIVITY_CHANNEL_TASK_4 => [
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => 100],
                    ['type' => GOODS_TYPE_1,'gid' => LINGSHI,'num' => 3000],
                ],
            ]
        ];
    }

    public function getLoginRewardConfig():array
    {

        return [
            Consts::ACTIVITY_TAG_5 => [ 
                'name'  => '午间登录奖励',
                'begin' => 7,
                'end'   => 14,
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => 100 ],
                    ['type' => GOODS_TYPE_1,'gid' => XIANYU,'num' => 20 ],
                ]
            ],
            Consts::ACTIVITY_TAG_6 => [ 
                'name'  => '晚间登录奖励',
                'begin' => 17,
                'end'   => 24,
                'reward' => [
                    ['type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => 100 ],
                    ['type' => GOODS_TYPE_1,'gid' => Consts::RENSHENTANG,'num' => 1 ],
                ]
            ],
        ];
    }

    public function getNewYearBoxs(PlayerService $playerSer,array $task):array
    {
        $boxs = [];
        $newYearConfig = NewYear::getInstance()->getAll();
        $map  = Consts::ACTIVITY_NEW_YEAR_TAG;
        foreach ($newYearConfig as $id => $value)
        {
            $state  = $playerSer->getArg($map[$id]);
            if(!$state)
            {
                $taskCount = count($value['task_need']);
                //0 1 2
                $weight = [];
                foreach ($value['task_need'] as $taskid)
                {
                    if(!$task[$taskid][1]) continue;
    
                    $weight[] = $task[$taskid][1];
                }
    
                if($taskCount == count($weight) ) $state = 1;
            }    

            $boxs[] = [
                'id'        => $id,
                'state'     => $state,
                'reward'    => $value['reward'],
            ];
        }

        return $boxs;
    }

    public function getNewYearTask(array $task):array
    {
        $taskList = [];

        $newYearTask = ConfigTask::getInstance()->getNewYearInitTask();
        foreach ($newYearTask as $taskid => $detail) 
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

    public function getCircleOfFriendsRedPoint(PlayerService $playerSer):array
    {
        $config = $this->getCircleOfFriendsRewardConfig();
        
        $redPoint = [];

        foreach ($config as $taskid => $detail) 
        {
            if( $playerSer->getArg($taskid) != 1) continue;
            $redPoint[] = $taskid;
        }

        return $redPoint;
    }

    public function getFirstRechargeConfig(PlayerService $playerSer):array
    {
        $config = FirstRecharge::getInstance()->getAll();
        $now  = strtotime(date('Ymd'));
        $show = [];
        //未激活 未达成领取条件  可领取  已领取  
        foreach ($config as $detail) 
        {
            //未激活
            $state = 0 ;
            if( $buyTime = $playerSer->getArg(COUNTER_FR))
            {
                // 已领取
                $state = 3;
                if(!$playerSer->getArg(  Consts::ACTIVITY_FIRST_RECHARGE_TAG + $detail['id']))
                {
                    // 未领取
                    // 未领取
                    $state = 2;
                    if($detail['complete_type'])
                    {
                        $state = 1;
                        list($_lv,$lvState) = TaskService::getInstance()->getTaskState($playerSer,0,$detail);
                        if($now >= strtotime(date('Ymd',strtotime('+'.($detail['day']-1).' day',$buyTime)))|| $lvState)
                        {
                            $state =  2;
                        }
                    }
                }
            }

            $detail['state'] = $state;
            $show[] = $detail;
        }

        return $show;
    }
}