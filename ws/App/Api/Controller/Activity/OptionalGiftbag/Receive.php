<?php
namespace App\Api\Controller\Activity\OptionalGiftbag;

use App\Api\Controller\BaseController;
use App\Api\Table\Activity\OptionalGiftbag as ActivityOptionalGiftbag;

//首冲
class Receive extends BaseController
{

    public function index()
    { 
        $groupid =  $this->param['groupid'];
        $option  =  $this->param['option'];

        $result = '无效的自选格式';
        if(is_array($option))
        {
            $config  = ActivityOptionalGiftbag::getInstance()->getOne($groupid);
            $result = '无效自选礼包';
            if($config)
            {
                $reward = [ $config['basics_reward'] ];
                //免费礼包
                $result = '已达今日最大购买次数';
                if($config['limit_num'] > $this->player->getArg($groupid) )
                {
                    if($config['giftbag_type'] !== 1)
                    {
                        $result = '暂未选择购买商品';
                        if($option)
                        {
                            $data   = $config['data'];
                            $isTrue = true;

                            foreach ($option as $type => $id) 
                            {
                                isset($data[$type][$id]) ?  $reward[] = $data[$type][$id] : $isTrue = false;
                            }

                            $result = '非法的物品';
                            if($isTrue)
                            {
                                $result = '请完整选择物品';
                                if($config['choice_num'] == count($option) )
                                {
                                    $this->player->goodsBridge($reward,'自选礼包',$groupid);
                                    $this->player->setArg($groupid,1,'add');

                                    $this->player->setArg(COUNTER_AD,1,'add');

                                    $isEq = $this->player->getArg($groupid) == $config['limit_num'];
                                    if( $isEq )
                                    {
                                        $ogb = $this->player->getTmp('ogb');
                                        if($ogb)
                                        {
                                            $ogb[$groupid] = $option;
                                        }else{
                                            $ogb = [$groupid => $option];
                                        }

                                        $this->player->setTmp('ogb',$ogb);
                                    } 

                                    $result = [
                                        'groupid'     => $groupid,
                                        'reward'      => $reward,
                                        'receive'     => $isEq ? $option : [],
                                        'remain'      => $this->player->getArg($groupid),
                                    ];
                                }
                            }
                        }
                    }else{

                        foreach ($config['data'] as $type => $rewardList) 
                        {
                            foreach ($rewardList as  $value)
                            {
                                $reward[] = $value;
                            }
                        }

                        $this->player->goodsBridge($reward,'自选礼包',$groupid);
                        $this->player->setArg($groupid,1,'add');

                        $result = [
                            'groupid'     => $groupid,
                            'reward'      => $reward,
                            'receive'     => [],
                            'remain'      => $this->player->getArg($groupid),
                        ];
                    }
    
                }
    
            }
        }

        $this->sendMsg( $result );
    }

}