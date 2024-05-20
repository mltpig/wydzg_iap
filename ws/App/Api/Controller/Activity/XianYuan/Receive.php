<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigFund;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\XianYuanService;

class Receive extends BaseController
{

    public function index()
    {
        $group = 9;
        $configFund = XianYuanService::getInstance()->getFundGroupData($this->player,$group);

        $result = '仙缘暂未开放';
        if(array_key_exists($group,$configFund))
        {
            $data           = $configFund[$group];
            $group_reward   = [];
            $config         = ConfigFund::getInstance()->getOne($group);
            $config_reward  = $config['data'];
            if($this->player->getArg(Consts::XIANYUAN_FUND_GROUP9)) // TODO:购买过仙缘
            {
                foreach($data as $id => $val)
                {
                    if($val['freeReward'] == 1 || $val['paidReward'] == 1)
                    {
                        foreach($config_reward as $v_reward)
                        {
                            if($id != $v_reward['id']) continue;
    
                            //判断是否领取过基础奖励,再充值的仙缘
                            if($val['freeReward'] != 2) $group_reward[] = $v_reward['freeReward'];
    
                            foreach($v_reward['paidReward'] as $paidReward){
                                $group_reward[] = $paidReward;
                            }
                        }
                        $old = ['freeReward' => 2, 'paidReward' => 2];
                        $this->player->setXianYuan($group,$id,$old,'multiSet');
                    }
                }
                $this->player->goodsBridge($group_reward,'领取仙缘奖励');

                $result = [
                    'xianyuan'  => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                    'reward'    => XianYuanService::getInstance()->aggregateAwards($group_reward)
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
                    $this->player->setXianYuan($group,$id,$old,'multiSet');
                }
                $this->player->goodsBridge($group_reward,'领取仙缘奖励');

                $result = [
                    'xianyuan'  => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
                    'reward'    => XianYuanService::getInstance()->aggregateAwards($group_reward),
                ];
            }
        }
        $this->sendMsg( $result );
    }

}