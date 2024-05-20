<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Controller\BaseController;

//抽卡
class Read extends BaseController
{

    public function index()
    {
        $uid  = $this->param['uid'];
        $type = $this->param['type'];
        $id   = $this->param['id'];
        $detail = EmailService::getInstance()->getOne($uid,$this->param['site'],$type,$id);
        $result = '无效的邮件ID';
        if($detail)
        {
            $result = '该邮件已阅读';
            if(!$detail['state'])
            {
                $detail['state'] = 1;
                EmailService::getInstance()->set($uid,$this->param['site'],$type,$id,$detail);
                
                $result = [ 'id'    => $id, 'state' => 1 ];
            }
        }
        $this->sendMsg( $result );
    }

}