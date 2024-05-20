<?php

namespace App\Api\Controller\Tactical;

use App\Api\Controller\BaseController;
use App\Api\Service\TacticalService;
use App\Api\Service\TaskService;
use App\Api\Table\ConfigParam;

/**
 * 推演阵法
 */
class Deduce extends BaseController
{
    public function index()
    {
        if (!TacticalService::getInstance()->isUnlock($this->player)) {
            $this->sendMsg('阵法未解锁');
            return;
        }

        $tacticalInfo = $this->player->getData('tactical');

        $tmp = $tacticalInfo['tmp'];
        $result = '当前存在待处理阵眼';
        //判断是否自动回收，回收要考虑buff
        $auto = $this->param['auto'];
        //如果是自动情况下，如果存在阵眼需要自动回收阵眼
        $reward = [];
        if($auto == 1 && $tmp){
            $reward = TacticalService::getInstance()->RecoveryEye($this->player, $tacticalInfo['level']['lv']);
            $tmp = [];
        }

        //判断是否有未处理阵眼
        if (!$tmp) {
            $result = '物品数量不足';
            //获取阵法推演配置 物品id；数量
            $costConfig = ConfigParam::getInstance()->getFmtParam('TALENT_PULL_COST');
            //获取阵法晶魄数量
            $hasNum = $this->player->getGoods($costConfig['gid']);
            $number = $costConfig['num'];
            if ($number <= $hasNum) {
                //扣除 物品
                $this->player->goodsBridge([[ 'type' => GOODS_TYPE_1,'gid' => $costConfig['gid'],'num' => -$number ]],'阵法',$hasNum );

                $remain = $hasNum - $number;

                //推送任务
                TaskService::getInstance()->setVal($this->player, 29, 1, 'add');

                //抽取阵眼
                $eyeInfo = TacticalService::getInstance()->lotteryEye($tacticalInfo);
                //存储抽奖结果
                $tacticalInfo['tmp'] = $eyeInfo;

                //处理等级
                $tacticalInfo['level']['exp'] = $tacticalInfo['level']['exp'] + 1;
                $lv = $tacticalInfo['level']['lv'];
                $tacticalInfo['next_lv'] = TacticalService::getInstance()->getNextLvNeedExp($lv);
                if ($tacticalInfo['level']['exp'] >= $tacticalInfo['next_lv']) {
                    $tacticalInfo['level']['exp'] = $tacticalInfo['level']['exp'] - $tacticalInfo['next_lv'];
                    $tacticalInfo['level']['lv'] = $tacticalInfo['level']['lv'] + 1;
                }
                $this->player->setTactical('tactical', 0, $tacticalInfo, 'flushall');

                //返回抽奖结果
                $result = ['tmp' => $eyeInfo, 'level' => $tacticalInfo['level'],
                    'next_lv' => $tacticalInfo['next_lv'], 'remain' => $remain ,'reward' => $reward];
            }

        }



        $this->sendMsg($result);
    }
}