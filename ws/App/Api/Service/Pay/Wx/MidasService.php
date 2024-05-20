<?php
namespace App\Api\Service\Pay\Wx;
use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class MidasService
{
    use CoroutineSingleTon;
    
    private $offerId          = "1450217302";
    private $devAppKey        = '8C1RKb5sWWX9cVY5P1JulPpsnhAxSKfM';
    private $produceAppKey    = '42gnQaItqU8YCRWGpCxbMjLmoQ2vTVK4';
    private $isDev            = 1;
    private $sessionKey       = '';

    public function getOfferId():string
    {
        return $this->offerId;
    }

    public function getAppkey():string
    {
        return $this->isDev ? $this->devAppKey : $this->produceAppKey;
    }

    public function getEnv():int
    {
        return $this->isDev;
    }


    public function getSessionKey(string $openid):string
    {
        if($this->sessionKey) return $this->sessionKey;
        
        $cacheKey = Keys::getInstance()->getLoginSetKey();
        $this->sessionKey = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($cacheKey,$openid) {
            return $redis->hget($cacheKey,$openid);
        });
        
        return $this->sessionKey;
    }

    public function createPaySign(string $data):string
    {
        return hash_hmac('sha256', $data,$this->getAppkey(),false);
    }
    
    public function createLoginSign(array $data):string
    {
        return hash_hmac('sha256', json_encode($data),$this->getSessionKey($data['openid']),false);
    }

}