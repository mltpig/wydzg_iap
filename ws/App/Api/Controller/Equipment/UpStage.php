<?php
namespace App\Api\Controller\Equipment;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigEquipmentAdvance;
use App\Api\Table\ConfigEquipmentAdvanceUp;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\TaskService;

class UpStage extends BaseController
{

    public function index()
    {
        $equipment  = $this->player->getData('equipment');
        $old        = $equipment['stage'];

        $result = '已达进阶上限';
        if($old < 41)
        {
            $configUp   = ConfigEquipmentAdvanceUp::getInstance()->getOne($old);
            $where      = EquipmentService::getInstance()->getEquipmentWhereUpStage($equipment['equiplv'], $configUp['level_limit']);
            $big_cost   = $configUp['big_cost'];
            $result     = '精炼等级不足';
            if($where)
            {
                $result     = '道具不足';
                if($this->player->getGoods($big_cost[0]['gid']) >= $big_cost[0]['num'] && $this->player->getGoods($big_cost[1]['gid']) >= $big_cost[1]['num'])
                {
                    $old++;
                    $this->player->setEquipment('stage',0,$old,'set');

                    foreach($big_cost as $value)
                    {

                        $this->player->goodsBridge([[ 'type' => GOODS_TYPE_1,'gid' => $value['gid'],'num' => -$value['num'] ]],'升星' );
                        $remain[] = ['gid' => $value['gid'], 'num' => $this->player->getGoods($value['gid'])];
                    }
                    
                    $result = [
                        'equipment' => EquipmentService::getInstance()->getEquipmentFmtData($this->player),
                        'remain'    => $remain
                    ];
                }
            }
        }
        $this->sendMsg( $result );
    }

}