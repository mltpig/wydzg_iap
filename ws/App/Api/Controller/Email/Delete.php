<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Controller\BaseController;

//抽卡
class Delete extends BaseController
{

    public function index()
    {
        $uid  = $this->param['uid'];
        $type = $this->param['type'];
        $id   = $this->param['id'];
        $detail = EmailService::getInstance()->getOne($uid,$this->param['site'],$type,$id);
        $result = '没有可处理的邮件';
        if($detail)
        {
            $result = '暂未阅读或有奖励未领取';
            if($detail['reward'] && $detail['state'] == 2 || !$detail['reward'] && $detail['state'] == 1)
            {
                EmailService::getInstance()->delete($uid,$this->param['site'],$type,$id);
                $result = [ 'id' => $id];
            }
            
        }
        $this->sendMsg( $result );
    }

}