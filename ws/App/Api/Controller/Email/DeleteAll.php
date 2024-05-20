<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Controller\BaseController;

//抽卡
class DeleteAll extends BaseController
{

    public function index()
    {
        $uid  = $this->param['uid'];
        $type = $this->param['type'];
        $list = EmailService::getInstance()->getAll($uid,$this->param['site'],$type);
        $result = '没有可处理的邮件';
        if($list)
        {
            foreach ($list as $key => $detail) 
            {
                //未读 有奖励未领取 跳过
                if(!$detail['state'] || $detail['reward'] && $detail['state'] != 2) continue;
                EmailService::getInstance()->delete($uid,$this->param['site'],$type,$key);
            }
            
            $result = [
                'email'  => EmailService::getInstance()->getEamils($uid,$this->param['site'],$type),
            ];

        }
        $this->sendMsg( $result );
    }

}