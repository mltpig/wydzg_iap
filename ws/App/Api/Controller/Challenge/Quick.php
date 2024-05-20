<?php
namespace App\Api\Controller\Challenge;
use App\Api\Table\ConfigChallengeBoss;
use App\Api\Table\ConfigParam;
use App\Api\Service\ComradeService;
use App\Api\Service\Module\MonthlyCardService;
use App\Api\Controller\BaseController;

//快速战斗
class Quick  extends BaseController
{

    public function index()
    {
        $result = '海内太平，天下无妖';
        $isAd = $this->param['isAd'];
        //快速战斗的关卡为当前通过的最高妖王
        $nowid = $this->player->getData('challenge');
        $config = ConfigChallengeBoss::getInstance()->getOne( $nowid );
        if($config)
        {
            $limit = ConfigParam::getInstance()->getFmtParam('WILDBOSS_REPEAT_LIMIT');
            if(MonthlyCardService::getInstance()->getMonthlyCardExpire($this->player)) $limit += 2;//月卡上限+2

            $result = '今日次数已用完，请明日再来';
            $count  = $this->player->getArg(CHALLENGE);
            if( $limit > $count )
            {
                $reward = [ $config['repeat_rewards'] ];
                $cost   = $config['repeat_cost'];
                //首次消耗免费
                $config = ConfigParam::getInstance()->getFmtParam('WILDBOSS_REPEAT_COST_PARAM');
                $number = $cost['num'] * ($config[$count]/1000);

                if($this->param['isAd'])
                {
                    $this->player->goodsBridge($reward,'妖王速战',$count+1);
                    $this->player->setArg(CHALLENGE,1,'add');
                    
                    $sum  = ComradeService::getInstance()->getLvStageByTalent($this->player,60006);
                    $comradeReward =  [];
                    if($sum > 0)
                    {
                        $comradeReward = [ [ 'type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => $sum ] ];
                        $this->player->goodsBridge($comradeReward,'妖王速战-贤士加成',$count+1);
                    }

                    $result = [
                        'reward'   => $reward,
                        'comrade_reward' => $comradeReward,
                        'count'    => $this->player->getArg(CHALLENGE),
                        'remain'   => $this->player->getGoods($cost['gid']),
                        'cost_num' => $cost['num'] * ($config[$count+1]/1000),
                    ];

                }else{                    
                    $result = '数量不足';
                    if($this->player->getGoods($cost['gid']) >= $number)
                    {
                        $this->player->goodsBridge($reward,'妖王速战',$count+1);
                        $this->player->setArg(CHALLENGE,1,'add');

                        $sum  = ComradeService::getInstance()->getLvStageByTalent($this->player,60006);
                        $comradeReward =  [];
                        if($sum > 0)
                        {
                            $comradeReward = [ [ 'type' => GOODS_TYPE_1,'gid' => XIANTAO,'num' => $sum ] ];
                            $this->player->goodsBridge($comradeReward,'妖王速战-贤士加成',$count+1);
                        }
                        $costNumberCopy = $cost['num'];
                        if($number > 0)
                        {
                            $cost['num'] = -$number;
                            $this->player->goodsBridge([ $cost ],'妖王速战',$count+1);
                        }
                        
                        $result = [
                            'reward'   => $reward,
                            'comrade_reward' => $comradeReward,
                            'count'    => $this->player->getArg(CHALLENGE),
                            'remain'   => $this->player->getGoods($cost['gid']),
                            'cost_num' => $costNumberCopy * ($config[$count+1]/1000),
                        ];
                    }
                }
            }
            
        }
        $this->sendMsg( $result );
    }

}