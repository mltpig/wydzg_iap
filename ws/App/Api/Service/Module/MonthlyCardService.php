<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\EmailService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Component\CoroutineSingleTon;

class MonthlyCardService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        $playerSer->setArg(Consts::MONTHLY_CARD_STATE,1,'unset');
        if(empty($this->getMonthlyCardExpire($playerSer))) $playerSer->setArg(Consts::MONTHLY_CARD_TIME,1,'unset');
    }
    
    public function check(PlayerService $playerSer,$time)
    {
        if($this->getMonthlyCardExpire($playerSer) && empty($playerSer->getArg(Consts::MONTHLY_CARD_STATE)))
        {
            $this->monthlyCardEmail($playerSer);
        }
    }

    // 月卡是否到期
    public function getMonthlyCardExpire(PlayerService $playerSer)
    {
        $monthlyCardTime  = $playerSer->getArg(Consts::MONTHLY_CARD_TIME);
        if(empty($monthlyCardTime)) return 0;
        if(date('Y-m-d',time()) > date('Y-m-d',$monthlyCardTime)) return 0;
        return 1;
    }

    public function getMonthlyCardFmtData(PlayerService $playerSer):array
    {
        $day = 0;

        $monthlyCardTime  = $playerSer->getArg(Consts::MONTHLY_CARD_TIME);
        if($monthlyCardTime)
        {
            // 计算两个时间戳相差的天数
            // $day = floor(abs($monthlyCardTime - time()) / (60 * 60 * 24));
            $day = ceil(abs($monthlyCardTime - time()) / (60 * 60 * 24));
        }

        return [
            'day' => $day
        ];
    }

    public function monthlyCardEmail(PlayerService $playerSer):void
    {
        $email  = [
            'title'      => '月卡每日福利',
            'content'    => '大人，这是您的月卡每日福利，请笑纳。',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => [['type' => GOODS_TYPE_1,'gid' => 100004,'num' => 400 ]],
            'from'       => '貂蝉',
            'state'      => 0,
        ];
 
        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($playerSer->getData('openid'),$playerSer->getData('site'),1,$emailId,$email);

        $playerSer->setArg(Consts::MONTHLY_CARD_STATE,time(),'reset');
    }
}
