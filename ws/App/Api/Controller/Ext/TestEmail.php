<?php
namespace App\Api\Controller\Ext;
use EasySwoole\EasySwoole\Core;
use App\Api\Service\EmailService;
use App\Api\Controller\BaseController;
use EasySwoole\Utility\SnowFlake;

class TestEmail  extends BaseController
{

    public function index()
    {
        $result = [];

        if(Core::getInstance()->runMode() === 'dev')
        {
            $reward = [
                ['type' => GOODS_TYPE_1,'gid' => '100000','num' => 1000],
                ['type' => GOODS_TYPE_1,'gid' => '100003','num' => 1000],
                ['type' => GOODS_TYPE_1,'gid' => '100004','num' => 1000],
                ['type' => GOODS_TYPE_1,'gid' => '100005','num' => 1000],
                ['type' => GOODS_TYPE_100,'gid' => '3080015','num' => 1],
            ];
            $email  = [
                'title'      => '测试邮件',
                'content'    => '话说天下大势，分久必合，合久必分。<br/>时势造英雄，英雄亦适时。历经数载，此番乱世也终于出现了天命之人，将终结乱世，一统天下。<br/>而英雄辈出之际，想乘势而起，又谈何容易！但若天命在身，怎能不争？！<br/>扬名立万、扭转乾坤，扶摇直上九万里。沧海横流，方显英雄本色。在这风云际会之时，你，便是绝对的主角。<br/>去吧，去搅动这乱世风云，去与这时代争辉，去成就你独一无二的荣耀吧~<br/>',
                'start_time' => time(),
                'end_time'   => time()+2592000,
                'reward'     => $reward,
                'from'       => '水镜先生',
                'state'      => 0,
            ];

            $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
            EmailService::getInstance()->set($this->param['uid'],$this->param['site'],1,$emailId,$email);
    
            $result = [
                'email'  => EmailService::getInstance()->getEamils($this->param['uid'],$this->param['site'],1),
            ];
        }

        $this->sendMsg($result);
    }

}