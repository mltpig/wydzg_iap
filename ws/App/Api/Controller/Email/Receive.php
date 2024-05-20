<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Service\EquipService;
use App\Api\Controller\BaseController;

//抽卡
class Receive extends BaseController
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
            $result = '该邮件无奖励可领取';
            if($detail['reward'])
            {
                $result = '该邮件已领取';
                if($detail['state'] != 2)
                {
                    $detail['state'] = 2;
                    $this->player->goodsBridge($detail['reward'],'邮件领取',$id);
    
                    EmailService::getInstance()->set($uid,$this->param['site'],$type,$id,$detail);
    
                    $result = [ 
                        'id' => $id, 
                        'state' => 2,
                        'reward' => $detail['reward'],
                        'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}