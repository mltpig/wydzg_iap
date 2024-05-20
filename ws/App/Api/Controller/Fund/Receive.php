<?php
namespace App\Api\Controller\Fund;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigFund;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\FundService;

class Receive extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $group = $param['group'];
        
        $configFund     = FundService::getInstance()->getFundGroupData($this->player,$group);
        $tag            = FundService::getInstance()->getGroupWhereArg($group);

        $result = '基金暂未开放';
        if(array_key_exists($group,$configFund))
        {
            $data           = $configFund[$group];
            $group_reward   = $reward = [];
            $config         = ConfigFund::getInstance()->getOne($group);
            $config_reward  = $config['data'];
            if($this->player->getArg($tag))//TODO 购买过基金
            {
                foreach($data as $id => $val)
                {
                    if($val['freeReward'] == 1 || $val['paidReward'] == 1)
                    {
                        foreach($config_reward as $v_reward)
                        {
                            if($id != $v_reward['id']) continue;
    
                            //判断是否领取过基础奖励,再充值的基金
                            if($val['freeReward'] == 1) $group_reward[] = $v_reward['freeReward'];
    
                            foreach($v_reward['paidReward'] as $paidReward){
                                $group_reward[] = $paidReward;
                            }
                        }
                        $old = ['freeReward' => 2, 'paidReward' => 2];
                        $this->player->setFund($group,$id,$old,'multiSet');
                    }
                }
                $this->player->goodsBridge($group_reward,'领取基金奖励');
                $reward = FundService::getInstance()->aggregateAwards($group_reward);
                $result = [
                    'fund'      => FundService::getInstance()->getFundGroupData($this->player,$group),
                    'config'    => ['state' => $this->player->getArg($tag)],
                    'reward'    => $reward
                ];
            }else{
                foreach($data as $id => $val)
                {
                    if($val['freeReward'] != 1) continue;

                    foreach($config_reward as $v_reward)
                    {
                        if($id != $v_reward['id']) continue;
                        $group_reward[] = $v_reward['freeReward'];
                    }
                    
                    $old = ['freeReward' => 2, 'paidReward' => 0];
                    $this->player->setFund($group,$id,$old,'multiSet');
                }
                $this->player->goodsBridge($group_reward,'领取基金奖励');
                $reward = FundService::getInstance()->aggregateAwards($group_reward);
                $result = [
                    'fund'      => FundService::getInstance()->getFundGroupData($this->player,$group),
                    'config'    => ['state' => $this->player->getArg($tag)],
                    'reward'    => $reward,
                ];
            }
        }
        $this->sendMsg( $result );
    }

}