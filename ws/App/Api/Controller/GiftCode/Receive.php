<?php
namespace App\Api\Controller\GiftCode;
use App\Api\Service\GiftService;
use App\Api\Service\EquipService;
use App\Api\Model\ManageGiftCode;
use App\Api\Model\ManageCodeUse;
use App\Api\Controller\BaseController;

//抽卡
class Receive extends BaseController
{

    public function index()
    {
        $param = $this->param;

        //是否存在
        $data = ManageGiftCode::create()->get(['code' => $param['giftCode']]);
        $result = '无效礼包码';
        if($data)
        {
            $roleid = $this->player->getData('roleid');
            $data = $data->toArray();
            $result = '该礼包码已被领取';
            if(!$data['roleid'])
            {
                $time  = time();
                $result = '该礼包码已过期';
                if($time >= $data['start_time'] && $time <= $data['end_time'])
                {
                    //type 1  一号一码
                    if($data['type'] == 1)
                    {
                        $uidData = ManageGiftCode::create()->get(['roleid' => $roleid,'gift_id' => $data['gift_id']]);
                        $result = '该批次礼包码已领取';
                        if(!$uidData)
                        {
                            ManageGiftCode::create()->update(['roleid' => $roleid,'update_time' => date('Y-m-d H:i:s')],[ 'code' => $data['code'] ]);
                            $rewards = GiftService::getInstance()->getGiftData($data['gift_id']);
                            $this->player->goodsBridge($rewards,'礼包码奖励',$param['giftCode']);
                            $result = [
                                'reward'    => $rewards,
                                'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp'))),
                            ];
                        }
                    }elseif($data['type'] == 2){
                        //type 2  一码通用
                        $typeData = ManageCodeUse::create()->get(['roleid' => $roleid,'code' => $param['giftCode']]);
                        $result = '该礼包码你已领取过';
                        if(!$typeData)
                        {
                            $newData = array(
                                'code'         => $param['giftCode'],
                                'roleid'       => $roleid,
                                'create_time'  => date('Y-m-d H:i:s'),
                            );
                            ManageCodeUse::create($newData)->save();
                            $rewards = GiftService::getInstance()->getGiftData($data['gift_id']);
                            $this->player->goodsBridge($rewards,'礼包码奖励',$param['giftCode']);
                            $result = [
                                'reward'    => $rewards,
                                'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->player->getData('equip_tmp'))),
                            ];
                        }
                    }
    
                    
                }
            } 

        }

        $this->sendMsg($result);
    }

}