<?php
namespace App\Api\Controller\Activity\FirstRecharge;

use App\Api\Service\Module\PetService;
use App\Api\Table\Activity\FirstRecharge;
use App\Api\Service\EquipService;
use App\Api\Controller\BaseController;
use App\Api\Service\TaskService;
use App\Api\Utils\Consts;
use App\Api\Service\ActivityService;

//首冲
class Receive extends BaseController
{

    public function index()
    {

        $result = '请先购买';
        if($this->player->getArg(COUNTER_FR))
        {
            $result = '已领取过该活动奖励';
            $firstRechargeTag = Consts::ACTIVITY_FIRST_RECHARGE_TAG + $this->param['id'];
            if(!$this->player->getArg($firstRechargeTag))
            {

                $result = '无效的奖励ID';
                if($config = FirstRecharge::getInstance()->getOne($this->param['id']))
                {
                    if($config['complete_type'])
                    {
                        $now  = strtotime(date('Ymd'));
                        $buyTime = $this->player->getArg(COUNTER_FR);

                        list($lv,$lvState) = TaskService::getInstance()->getTaskState($this->player,0,$config);

                        $result = '等级未满足';
                        if($now >= strtotime(date('Ymd',strtotime('+'.($config['day']-1).' day',$buyTime)))|| $lvState)
                        {
                            //如果奖励有副将，需要判断副将是否有位置存放
                            foreach ($config['reward'] as $key => $item){
                                if($item['type'] == GOODS_TYPE_3 ){
                                    $bag   = $this->player->getData('pet','bag');
                                    $total = count($bag);
                                    $free  =  $total - count(array_filter($bag));
                                    if($free  >= 1){
                                        $result = '副将栏已满';
                                        $this->sendMsg( $result );
                                        return;
                                    }
                                }
                            }


                            $this->player->goodsBridge($config['reward'],'首冲奖励 '.$this->param['id'],1);

                            $this->player->setArg($firstRechargeTag,1,'add');
                            $result = [
                                'reward'    => $config['reward'],
                                'head'  => $this->player->getData('head'),
                                'list'      => ActivityService::getInstance()->getFirstRechargeConfig($this->player),
                                'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp'))),
                            ];
                        }
                    }else{
                        $this->player->goodsBridge($config['reward'],'首冲奖励 '.$this->param['id'],1);

                        $this->player->setArg($firstRechargeTag,1,'add');
                        $result = [
                            'reward'      => $config['reward'],
                            'list'        => ActivityService::getInstance()->getFirstRechargeConfig($this->player),
                            'equip_tmp'   => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp'))),
                        ];
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}