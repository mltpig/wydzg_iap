<?php
namespace App\Api\Service\Module;

use App\Api\Service\PlayerService;
use App\Api\Service\EmailService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Component\CoroutineSingleTon;

class LifetimeCardService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        $playerSer->setArg(Consts::LIFETIME_CARD_STATE,1,'unset');
    }
    
    public function check(PlayerService $playerSer,$time)
    {
        if($playerSer->getArg(Consts::LIFETTIME_CARD_TIME) && empty($playerSer->getArg(Consts::LIFETIME_CARD_STATE)))
        {
            $this->lifetimeCardEmail($playerSer);
        }
    }

    public function getLifetimeCardFmtData(PlayerService $playerSer):array
    {
        return [
            'state' => $playerSer->getArg(Consts::LIFETTIME_CARD_TIME)
        ];
    }

    public function lifetimeCardEmail(PlayerService $playerSer):void
    {
        $email  = [
            'title'      => '终身卡每日福利',
            'content'    => '大人，这是您的终身卡每日福利，请笑纳。',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => [['type' => GOODS_TYPE_1,'gid' => 100000,'num' => 100 ],['type' => GOODS_TYPE_1,'gid' => 100004,'num' => 400 ]],
            'from'       => '貂蝉',
            'state'      => 0,
        ];

        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($playerSer->getData('openid'),$playerSer->getData('site'),1,$emailId,$email);
        
        $playerSer->setArg(Consts::LIFETIME_CARD_STATE,time(),'reset');
    }
}
