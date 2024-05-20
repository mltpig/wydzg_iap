<?php
namespace App\Api\Service;

use App\Api\Utils\Keys;
use App\Api\Utils\Request;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;

class WeixinService
{
    use CoroutineSingleTon;

    private $appid  = 'wx3878aa286c71eebd';
    private $secret = 'e2e7e6637b66528c5fc794a4d18e29f8';

    public function getAppid():string
    {
        return $this->appid;
    }

    public function getAppToken():string
    {
        $tokenKey    = Keys::getInstance()->getWeixinTokenKey();
        $accessToken = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($tokenKey) {
            return $redis->get($tokenKey);
        });

        return  $accessToken ? $accessToken : $this->getNewToken();
    
    }

    public function  getNewToken()
    {
        $api = 'https://api.weixin.qq.com/cgi-bin/token';
        $param = ['grant_type' => 'client_credential','appid' => $this->appid,'secret' => $this->secret ];

        list($result,$body) = Request::getInstance()->http($api,'get',$param);

        Logger::getInstance()->log("access_token weixin ".$body);

        if(is_array($result) && array_key_exists('access_token',$result))
        {
            $expiresin   = $result['expires_in'] - 600 ;
            $accessToken = $result['access_token'];

            $tokenKey    = Keys::getInstance()->getWeixinTokenKey();
            PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($tokenKey,$accessToken,$expiresin) {
                $redis->set($tokenKey,$accessToken,$expiresin);
            });
        }else{
            throw new \Exception('异常：'.$body);
        }

        return $accessToken;
    }


    public function checkToken():void
    {
        //确保存在Token
        try {

            $this->getAppToken();
            $this->checkTtl();

        } catch (\Throwable $th) {
            Logger::getInstance()->log("check token ".$th->getMessage());
        }
    }

    public function checkTtl():void
    {
        $tokenKey   = Keys::getInstance()->getWeixinTokenKey();
        $ttl = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($tokenKey) {
            return $redis->ttl($tokenKey);
        });

        //此时公众平台后台会保证在5分钟内
        if($ttl > 120) return ;

        $this->getNewToken();
        
    }

    public function getYinliFmtData():array
    {
        $tokenKey = Keys::getInstance()->getWeixinTokenKey();
        return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($tokenKey) {
            return [
                $redis->get($tokenKey),
                $redis->ttl($tokenKey),
            ];
        });
    }

}