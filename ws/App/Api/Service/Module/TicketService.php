<?php
namespace App\Api\Service\Module;
use App\Api\Service\PlayerService;
use App\Api\Service\Pay\Wx\VoucherService as WeiXinService;
use App\Api\Service\Pay\Dev\VoucherService as Dev;
use EasySwoole\Component\CoroutineSingleTon;

class TicketService
{
    use CoroutineSingleTon;
    
    private $proxy = null;

    public function __construct(PlayerService $player)
    {
        switch (CHANNEL_PAY) 
        {
            case 'weixin':
                $this->proxy =  WeiXinService::getInstance($player->getData('openid'),$player->getData('site'));
                break;
            case 'dev':
                $this->proxy =  Dev::getInstance($player);
                break;
        }
    }

    public function getBalance():int
    {
        return $this->proxy->getBalance();
    }

    public function present(int $number ):int
    {
        return $this->proxy->present($number);
    }

    public function pay(int $number):array
    {
        return $this->proxy->pay($number);
    }

    public function queryOrder(string $tradeNo):array
    {
        return $this->proxy->queryOrder($tradeNo);
    }

}
