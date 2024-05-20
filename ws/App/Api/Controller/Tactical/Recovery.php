<?php

namespace App\Api\Controller\Tactical;

use App\Api\Controller\BaseController;
use App\Api\Service\TacticalService;

/**
 *  回收
 */
class Recovery extends BaseController
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
        //判断是否存在无用阵眼
        if ($tmp) {
            //回收阵眼，增加悟性
            $reward = TacticalService::getInstance()->RecoveryEye($this->player, $tacticalInfo['level']['lv']);
            //数据存储到存档,删除无用阵眼
            $this->player->setTactical('tmp', 0, [], 'set');
            $result = ['reward' => $reward,'tmp'=>[]];
        }
        $this->sendMsg($result);
    }
}