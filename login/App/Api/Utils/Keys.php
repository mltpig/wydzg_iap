<?php
namespace App\Api\Utils;
use EasySwoole\Component\CoroutineSingleTon;

class Keys 
{
    use CoroutineSingleTon;

    public function getNodeKey(string $openid):string
    {   
        return 'node:'.$openid;
    }

    public function getLoginSetKey():string
    {   
        return 'login:hash';
    }

    public function getNodeListKey():string
    {   
        return "server:node";
    }

    public function getNoticeKey():string
    {   
        return 'notice';
    }
}
