<?php
namespace App\Api\Service\Channel;

use App\Api\Utils\Keys;
use App\Api\Utils\Request;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;

class ZijieService
{
    use CoroutineSingleTon;

    private $appid  = 'tt274351feb8737d5802';
    private $secret = '400c90e2b646a885f679384daa9bc6b3f4550eeb';

    public function getUserInfo(string $code)
    {
        $api  = 'https://developer.toutiao.com/api/apps/jscode2session';

        $param =  array(
            'appid'      => $this->appid,
            'secret'     => $this->secret,
            'code'   => $code,
        );

        list($result,$body) = Request::getInstance()->http($api,'get',$param);

        if(is_array($result) && isset($result['openid']))
        {
            $data  = [ 
                'openid'     => $result['openid'],
                'session_key' => '',
            ];
        }else{
            Logger::getInstance()->log("URL: ".$api.' === param: '.json_encode($param,JSON_UNESCAPED_UNICODE)." === body: ".$body);

            $data  = $result['errmsg'];
        }

        return $data;
    }

}