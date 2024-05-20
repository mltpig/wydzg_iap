<?php

namespace App\Api\Controller\Tactical;

use App\Api\Controller\BaseController;
use App\Api\Service\TacticalService;
use App\Api\Table\ConfigParam;

/**
 * 使用
 */
class Apply extends BaseController
{
    public function index()
    {
        if (!TacticalService::getInstance()->isUnlock($this->player)) {
            $this->sendMsg('阵法未解锁');
            return;
        }
        $tacticalInfo = $this->player->getData('tactical');
        $tmp = $tacticalInfo['tmp'];
        $result = ['msg'=>'没有阵眼可以使用','tmp'=>$tmp];
        if ($tmp) {
            //判断是否自动回收，回收要考虑buff
            $auto = $this->param['auto'];
            //判断是否有老阵眼
            $old = $tacticalInfo['list'][$tmp['type']];
            //处理阵眼列表数据
            $tmpCopy = $tmp;
            unset($tmpCopy['type']);
            $tacticalInfo['list'][$tmp['type']] = $tmpCopy;

            $reward = [];
            if ($old) {
                if ($auto) {
                    //回收阵眼
                    $reward = TacticalService::getInstance()->RecoveryEye($this->player, $tacticalInfo['level']['lv']);
                    $tacticalInfo['tmp'] = [];
                } else {
                    $old['type'] = $tmp['type'];
                    $tacticalInfo['tmp'] = $old;
                }
            } else {
                $tacticalInfo['tmp'] = [];
            }

            //数据存储到存档
            $this->player->setTactical('tactical', 0, $tacticalInfo, 'flushall');


            //处理技能等级 通过类型判断
            $result = ['tactical' => $tacticalInfo, 'reward' => $reward, 'old' => $tmp['type'],'tmp'=>$tacticalInfo['tmp']];

        }
        $this->sendMsg($result);

    }
}