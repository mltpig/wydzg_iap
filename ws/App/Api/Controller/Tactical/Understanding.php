<?php

namespace App\Api\Controller\Tactical;

use App\Api\Controller\BaseController;
use App\Api\Service\TacticalService;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTalentBook;

/**
 * 参悟
 */
class Understanding extends BaseController
{
    public function index()
    {
        if (!TacticalService::getInstance()->isUnlock($this->player)) {
            $this->sendMsg('阵法未解锁');
            return;
        }
        $tacticalInfo = $this->player->getData('tactical');
        $number = $this->param['num'];//使用阵法晶魄数量
        $result = '物品数量不足';
        //获取阵法参悟配置 物品id；数量
        $costConfig = ConfigParam::getInstance()->getFmtParam('TALENT_READ_COST');
        //获取阵法晶魄数量
        $hasNum = $this->player->getGoods($costConfig['gid']);

        //判断阵法晶魄是否足够
        if ($number <= $hasNum) {
            //处理参悟最大上限
            $afterNumber = $tacticalInfo['afflatus'] + $number / $costConfig['num'];
            $bookConfig = ConfigTalentBook::getInstance()->getOne($afterNumber);
            if(!$bookConfig)  $this->sendMsg('新增灵感后超过上限');

            //扣除 阵法晶魄
            $this->player->goodsBridge([[ 'type' => GOODS_TYPE_1,'gid' => $costConfig['gid'],'num' => -$number ]],'阵法参悟',$hasNum );

            $remain = $hasNum - $number;
            //+灵感afflatus，需要对数量进行处理
            $tacticalInfo['afflatus'] = $afterNumber;
            $this->player->setTactical('afflatus', 0, $afterNumber, 'set');
            $result = [
                'afflatus' => $afterNumber,
                'remain' => $remain
            ];
        }

        $this->sendMsg($result);

    }
}