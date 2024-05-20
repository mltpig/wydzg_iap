<?php
namespace App\Api\Controller\Email;
use App\Api\Service\EmailService;
use App\Api\Service\EquipService;
use App\Api\Controller\BaseController;

//抽卡
class ReceiveAll extends BaseController
{

    public function index()
    {
        $uid  = $this->param['uid'];
        $type = $this->param['type'];
        $list = EmailService::getInstance()->getAll($uid,$this->param['site'],$type);
        $result = '没有可处理的邮件';
        if($list)
        {
            $tmp = [];
            foreach ($list as $key => $detail) 
            {
                if($detail['state'] == 2 || !$detail['reward']) continue;

                foreach ($detail['reward'] as $goods) 
                {
                    if(!isset($tmp[$goods['type']][$goods['gid']])) $tmp[$goods['type']][$goods['gid']] = 0;

                    $tmp[$goods['type']][$goods['gid']] += $goods['num'];
                }

                $detail['state'] = 2;
                $this->player->goodsBridge($detail['reward'],'邮件一键领取',$key);
                EmailService::getInstance()->set($uid,$this->param['site'],$type,$key,$detail);
            }
            
            $reward = [];
            if($tmp)
            {
                foreach ($tmp as $gtype => $gInfo) 
                {
                    foreach ($gInfo as $gid => $gnum) 
                    {
                        $reward[] = ['type' => $gtype,'gid' => $gid,'num' => $gnum ];
                    }
                }
            }
            
            $result = '没有可领取奖励的邮件';
            if($reward)
            {
                $result = [
                    'email'     => EmailService::getInstance()->getEamils($uid,$this->param['site'],$type),
                    'reward'    => $reward,
                    'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp')))
                ];
            }

        }
        $this->sendMsg( $result );
    }

}