<?php
namespace App\Api\Service\Pay;
use App\Api\Service\Pay\Wx\PayCallBack;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;

class PaySuccessCallBackService
{
    use CoroutineSingleTon;

    private $proxy = null;

    public function __construct()
    {
        switch (CHANNEL_PAY) 
        {
            case 'weixin':
                $this->proxy = PayCallBack::getInstance();
            break;
        }
    }

    public function check(array $param):string
    {
        return $this->proxy->firstCheck($param);
    }

    public function payCallBack(\swoole_http_request $request):string
    {
        $get  = [];
        $body = [];
        switch (CHANNEL_PAY) 
        {
            case 'weixin':
                $get = $request->get ? $request->get : [];
                $body = $request->getContent();
            break;
        }
            
        Logger::getInstance()->log('pay notify:'.$body,LoggerInterface::LOG_LEVEL_ERROR,'pay_notify');
        
        return $this->proxy->payCallBack($get,$body);
    }

}