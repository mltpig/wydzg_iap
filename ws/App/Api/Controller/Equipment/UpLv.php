<?php
namespace App\Api\Controller\Equipment;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigEquipmentAdvance;
use App\Api\Table\ConfigEquipmentAdvanceUp;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\TaskService;

class UpLv extends BaseController
{

    public function index()
    {
        $param      = $this->param;
        $equipment  = $this->player->getData('equipment');

        $configUp           = ConfigEquipmentAdvanceUp::getInstance()->getOne($equipment['stage']);
        $where_level_limit  = EquipmentService::getInstance()->getEquipmentLevelLimit($equipment['equiplv'], $configUp['level_limit']);
        $result = '已达等级上限';
        if($where_level_limit)
        {
            $config = ConfigEquipmentAdvance::getInstance()->getOne(1); // 1级所要消耗为例
            $cost   = $config['cost'];
            $result = '道具不足'; // 升级精炼材料少于一件条件
            if($this->player->getGoods($cost[0]['gid']) >= $cost[0]['num'] && $this->player->getGoods($cost[1]['gid']) >= $cost[1]['num'])
            {
                // 一键精炼
                if($param['open'])
                {
                    $remain = [];
                    list($equip_lv, $equip_type) = EquipmentService::getInstance()->getUpLvEquipType($equipment['equiplv']);

                    for($i = $equip_type; $i <= 12; $i++)
                    {
                        // 循环消耗升级精炼材料
                        if($this->player->getGoods($cost[0]['gid']) >= $cost[0]['num'] && $this->player->getGoods($cost[1]['gid']) >= $cost[1]['num'])
                        {
                            $this->player->setEquipment('equiplv',$i,$equip_lv,'multiSet');

                            $goodsList = [ ];
                            foreach($cost as $value)
                            {
                                $goodsList[] = [ 'type' => GOODS_TYPE_1,'gid' => $value['gid'],'num' => -$value['num'] ];
                            }
                            $this->player->goodsBridge($goodsList,'一键精炼' );
                        }
                    }

                    $remain[] = ['gid' => $cost[0]['gid'], 'num' => $this->player->getGoods($cost[0]['gid'])];
                    $remain[] = ['gid' => $cost[1]['gid'], 'num' => $this->player->getGoods($cost[1]['gid'])];

                    $result = [
                        'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($this->player),
                        'remain'    => $remain,
                    ];
                }else{
                    $remain = [];
                    list($equip_lv, $equip_type) = EquipmentService::getInstance()->getUpLvEquipType($equipment['equiplv']);
                    $this->player->setEquipment('equiplv',$equip_type,$equip_lv,'multiSet');

                    foreach($cost as $value)
                    {
                        $this->player->goodsBridge([[ 'type' => GOODS_TYPE_1,'gid' => $value['gid'],'num' => -$value['num']]],'精炼' );
                        
                        $remain[] = ['gid' => $value['gid'], 'num' => $this->player->getGoods($value['gid'])];
                    }

                    $result = [
                        'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($this->player),
                        'remain'    => $remain,
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}