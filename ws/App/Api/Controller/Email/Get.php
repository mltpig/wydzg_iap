<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Controller\BaseController;

//邮件列表
class Get extends BaseController
{

    public function index()
    {
        $uid  = $this->param['uid'];
        $type = $this->param['type'];

        $this->sendMsg( [
            'email'  => EmailService::getInstance()->getEamils($uid,$this->param['site'],$type),
        ] );
    }

}