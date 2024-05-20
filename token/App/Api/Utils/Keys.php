<?php
namespace App\Api\Utils;
use EasySwoole\Component\CoroutineSingleTon;

class Keys 
{
    use CoroutineSingleTon;

    public function getWeixinTokenKey():string
    {   
        return 'token:weixin';
    }
}
