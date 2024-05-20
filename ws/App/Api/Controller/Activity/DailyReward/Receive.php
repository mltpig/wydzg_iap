<?php
namespace App\Api\Controller\Activity\DailyReward;
use App\Api\Table\ConfigParam;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

//首冲
class Receive extends BaseController
{

    public function index()
    { 

        $nowNum = $this->player->getArg(COUNTER_DAILY_REWARD);
        $max = ConfigParam::getInstance()->getFmtParam('AD_REWARD_DAILY_MAX_NUM');
        
        $result = '每日福利已达最大限制次数';
        if($max > $nowNum)
        {

            $result = '元气还未恢复';
            if(!$this->player->getArg(COUNTER_DAILY_REWARD_CD))
            {
                $reward = ConfigParam::getInstance()->getFmtParam('AD_REWARD');
                $this->player->goodsBridge($reward,'每日在线奖励',$nowNum+1);
                $this->player->setArg(COUNTER_DAILY_REWARD,1,'add');
                $this->player->setArg(COUNTER_DAILY_REWARD_CD,time(),'reset');
                $this->player->setArg(COUNTER_AD,1,'add');
    
                $result = [ 
                    'reward'       => $reward , 
                    'daily_reward' => ActivityService::getInstance()->getDailyRewardFmt($this->player),
                ];
            }

        }
        
        $this->sendMsg( $result );
    }

}