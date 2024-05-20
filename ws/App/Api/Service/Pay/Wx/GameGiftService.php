<?php
namespace App\Api\Service\Pay\Wx;
use App\Api\Table\ConfigGoods;
use App\Api\Service\EmailService;
use App\Api\Service\Node\NodeService;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\Utility\SnowFlake;

class GameGiftService
{
    use CoroutineSingleTon;
    
    function send(array $param):string
    {
        $uid     = $param['ToUserOpenid'];
        $site    = NodeService::getInstance()->getLastLoginNode($uid);
        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));

        $reward  = [];
        foreach ($param['GoodsList'] as $detail) 
        {
            $goodInfo = ConfigGoods::getInstance()->getOne($detail['Id']);

            $reward[] = [
                'type' => $goodInfo['type'],
                'gid'  => $detail['Id'],
                'num'  => $detail['Num'],
            ];
        }

        $content = [
            'title'      => '游戏圈福利',
            'content'    => '大人，这是您的游戏圈福利，请笑纳。',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => $reward,
            'from'       => '貂蝉',
            'state'      => 0,
        ];

        EmailService::getInstance()->set($uid,$site,1,$emailId,$content);

        return json_encode(["ErrCode" => 0,"ErrMsg" => "Success"],273);
    }

}