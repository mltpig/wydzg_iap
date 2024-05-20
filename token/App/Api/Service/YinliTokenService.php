<?php
namespace App\Api\Service;

use EasySwoole\Component\CoroutineSingleTon;

class YinliTokenService
{
    use CoroutineSingleTon;

    private $token = 'gf2XkYnCFMhVt5yEPebajodqh0apQzN9';

    public function createSign(int $timestamp):string
    {
        $appid = WeixinService::getInstance()->getAppid();

        return md5($this->token.$timestamp.$appid);
    }
}