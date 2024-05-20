<?php
namespace App\Api\Controller\Tree;
use App\Api\Table\ConfigParam;
use App\Api\Service\TaskService;
use App\Api\Service\TreeService;
use App\Api\Controller\BaseController;

//仙树加速
class SpeedUp extends BaseController
{

    public function index()
    {
        $lv     = $this->player->getData('tree','lv');
        $state  = $this->player->getData('tree','state');
        $result = '仙树未在升级中';
        if($state)
        {
            $cost   = ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_ITEM_COST');
            $hasNum = $this->player->getGoods($cost['gid']);   
            $result = '数量不足';
            if($hasNum > 0 )
            {
                $speedupTime = ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_ITEM_TIME');

                $timestamp = $this->player->getData('tree','timestamp');

                $max = ceil(($timestamp - time()) / $speedupTime);

                if($hasNum >= $max ){


                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$max ] ];
                    $this->player->goodsBridge($reward,'军旗升级加速',$hasNum);

                    $this->player->setData('tree','lv',$lv+1);
                    $this->player->setData('tree','state',0);
                    $this->player->setData('tree','timestamp', 0);

                    TaskService::getInstance()->setVal($this->player,46,$lv+1,'set');
                }else{
                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$hasNum ] ];
                    $this->player->goodsBridge($reward,'军旗升级加速-沙漏',$hasNum);

                    $timestamp -= ($hasNum * $speedupTime);
                    $this->player->setData('tree','timestamp', $timestamp);
                }

                $tree = $this->player->getData('tree');
                $result = [
                    'goods'     => $this->player->getGoodsInfo(),
                    'remain'    => $this->player->getGoods($cost['gid']),
                    'tree'      => TreeService::getInstance()->getShowTree($this->player),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}