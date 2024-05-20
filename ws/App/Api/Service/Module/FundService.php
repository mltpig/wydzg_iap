<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\TaskService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigFund;
use EasySwoole\Component\CoroutineSingleTon;

class FundService
{
    use CoroutineSingleTon;

    public function initFund(PlayerService $playerSer):void
    {
        //解锁初始化
        if($fund = $playerSer->getData('fund')) return ;

        $fund = [];

        $playerSer->setFund('',0,$fund,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {

    }

    public function getGroupWhereArg(int $group)
    {
        switch ($group) {
            case 1:
                $tag = Consts::ACTIVITY_FUND_GROUP1;
                break;
            case 2:
                $tag = Consts::ACTIVITY_FUND_GROUP2;
                break;
            case 3:
                $tag = Consts::ACTIVITY_FUND_GROUP3;
                break;
            case 4:
                $tag = Consts::ACTIVITY_FUND_GROUP4;
                break;
            default:
        }
        return $tag;
    }

    public function getFundGroupData(PlayerService $playerSer, $group):array
    {
        $config = ConfigFund::getInstance()->getOne($group);

        return $this->getGroupWhereList($playerSer, $group, $config);
    }

    public function getGroupWhereList(PlayerService $playerSer, int $group, array $fundConfig):array
    {
        $tag      = $this->getGroupWhereArg($group);
        $fundData = $playerSer->getData('fund');

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
                if($playerSer->getArg($tag)) $funds[$group][$value_reward['id']]['paidReward'] = 1;//TODO 购买过基金
            }

            // 已激活
            if(array_key_exists($group,$fundData))
            {
                if(array_key_exists($value_reward['id'],$fundData[$group]))
                {
                    $freeReward = $fundData[$group][$value_reward['id']]['freeReward'];
                    $paidReward = $fundData[$group][$value_reward['id']]['paidReward'];

                    if($playerSer->getArg($tag))//TODO 购买过基金
                    {
                        if($paidReward == 0) $paidReward = 1; // 没领取过状态为1
                    }

                    $funds[$group][$value_reward['id']] = ['freeReward' => $freeReward, 'paidReward' => $paidReward];
                }
            }
        }

        return $funds;
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
    
    public function getFundRedPointInfo(PlayerService $playerSer)
    {
        $red        = [false,false,false,false];
        $configfund = $this->getFundGroupData($playerSer,1);
        foreach($configfund[1] as $key => $value)
        {
            if($value['freeReward'] == 1 || $value['paidReward'] == 1)
            {
                $red[0] = true;
            }
        }

        $configfund = $this->getFundGroupData($playerSer,2);
        foreach($configfund[2] as $key => $value)
        {
            if($value['freeReward'] == 1 || $value['paidReward'] == 1)
            {
                $red[1] = true;
            }
        }

        $configfund = $this->getFundGroupData($playerSer,3);
        foreach($configfund[3] as $key => $value)
        {
            if($value['freeReward'] == 1 || $value['paidReward'] == 1)
            {
                $red[2] = true;
            }
        }

        $configfund = $this->getFundGroupData($playerSer,4);
        foreach($configfund[4] as $key => $value)
        {
            if($value['freeReward'] == 1 || $value['paidReward'] == 1)
            {
                $red[3] = true;
            }
        }

        return $red;
    }
}
