<?php
namespace App\Api\Controller\Activity\ShangGu;
use App\Api\Utils\Keys;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\Activity\ConfigActivityDaily;
use App\Api\Service\Module\ShangGuService;
use App\Api\Controller\BaseController;

class SignIn extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $day    = $param['day'];
        $isDay  = $param['day'] - 1;
        $sign   = ShangGuService::getInstance()->getSignIn($this->player);
        $startTimestamp = ShangGuService::getInstance()->getOpeningTime($this->player);
        $resetInterval  = ConfigParam::getInstance()->getFmtParam('SHANGGUTOUZI_RESET_TIME') + 0;
        $dayFrom        = ShangGuService::getInstance()->checkAndDay($startTimestamp,$resetInterval);
        $result         = '超出签到日期';
        if($day <= $dayFrom)
        {
            $reward  = [];
            $config  = ConfigActivityDaily::getInstance()->getOne(2);
            if($day < $dayFrom) //需要补签
            {
                $repair = $this->player->getData('shanggu','repair');
                if($repair[$day] == 1) //该日期是否补过签
                {
                    if($this->player->getArg(Consts::SHANGGU_SIGNIN_GIFT)) //TODO
                    {
                        if($sign[$day]['paidReward'] == 1)
                        {
                            $reward[] = $config['data'][$isDay]['paidReward'];
                            
                            $old = ['freeReward' => 2, 'paidReward' => 2];
                            $this->player->setShangGu('sign_in',$day,$old,'multiSet');
                            $this->player->goodsBridge($reward,'领取仙缘签到奖励');
                            
                            $result = [
                                'shanggu'  => ShangGuService::getInstance()->getShangGuFmtData($this->player),
                                'reward'    => $reward,
                            ];
                        }
                    }
                }else{
                    $gid    = $config['data'][$isDay]['cost']['gid'];
                    $num    = $config['data'][$isDay]['cost']['num'];
                    $result = '道具数量不足';
                    if($this->player->getGoods($gid) >= $num)
                    {
                        $this->player->goodsBridge([['gid' => $gid, 'type' => GOODS_TYPE_1, 'num' => $num]],'仙缘补签消耗');
                        $this->player->setShangGu('repair',$day,1,'multiSet');

                        if($this->player->getArg(Consts::SHANGGU_SIGNIN_GIFT)) //TODO
                        {
                            if($sign[$day]['freeReward'] == 1 || $sign[$day]['paidReward'] == 1)
                            {
                                //判断是否领取过基础奖励再充值
                                if($sign[$day]['freeReward'] == 1) $reward[] = $config['data'][$isDay]['freeReward'];
                                $reward[] = $config['data'][$isDay]['paidReward'];
                                
                                $old = ['freeReward' => 2, 'paidReward' => 2];
                                $this->player->setShangGu('sign_in',$day,$old,'multiSet');
                                $this->player->goodsBridge($reward,'领取仙缘签到奖励');
                                
                                $result = [
                                    'shanggu'  => ShangGuService::getInstance()->getShangGuFmtData($this->player),
                                    'reward'    => $reward,
                                ];
                            }
                        }else{
                            if($sign[$day]['freeReward'] == 1)
                            {
                                $reward[] = $config['data'][$isDay]['freeReward'];
            
                                $old = ['freeReward' => 2, 'paidReward' => 0];
                                $this->player->setShangGu('sign_in',$day,$old,'multiSet');
                                $this->player->goodsBridge($reward,'领取仙缘签到奖励');
                
                                $result = [
                                    'shanggu'  => ShangGuService::getInstance()->getShangGuFmtData($this->player),
                                    'reward'    => $reward,
                                ];
                            }
                        }
                    }
                }
            }else{
                if($this->player->getArg(Consts::SHANGGU_SIGNIN_GIFT)) //TODO
                {
                    if($sign[$day]['freeReward'] == 1 || $sign[$day]['paidReward'] == 1)
                    {
                        //判断是否领取过基础奖励再充值
                        if($sign[$day]['freeReward'] == 1) $reward[] = $config['data'][$isDay]['freeReward'];
                        $reward[] = $config['data'][$isDay]['paidReward'];
                        
                        $old = ['freeReward' => 2, 'paidReward' => 2];
                        $this->player->setShangGu('sign_in',$day,$old,'multiSet');
                        $this->player->goodsBridge($reward,'领取仙缘签到奖励');
                        
                        $result = [
                            'shanggu'  => ShangGuService::getInstance()->getShangGuFmtData($this->player),
                            'reward'    => $reward,
                        ];
                    }
                }else{
                    if($sign[$day]['freeReward'] == 1)
                    {
                        $reward[] = $config['data'][$isDay]['freeReward'];
    
                        $old = ['freeReward' => 2, 'paidReward' => 0];
                        $this->player->setShangGu('sign_in',$day,$old,'multiSet');
                        $this->player->goodsBridge($reward,'领取仙缘签到奖励');
        
                        $result = [
                            'shanggu'  => ShangGuService::getInstance()->getShangGuFmtData($this->player),
                            'reward'    => $reward,
                        ];
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}