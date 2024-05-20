<?php
namespace App\Api\Controller\Tree;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Service\TaskService;
use App\Api\Service\TreeService;
use App\Api\Controller\BaseController;

//仙树加速
class AdSpeedUp extends BaseController
{

    public function index()
    {
        $lv     = $this->player->getData('tree','lv');
        $state  = $this->player->getData('tree','state');
        $result = '军旗未在升级中';
        if($state)
        {
            $timeSpeed      = $this->player->getArg(Consts::TREE_SPEED_UP_CD_TIME);
            $timeElapsed    = $timeSpeed - time();
            $result         = '冷却中';
            if(empty($timeSpeed) || $timeElapsed <= 0)
            {
                $timestamp   = $this->player->getData('tree','timestamp');
                $cost        = ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_AD_SKIP');
                $speedupTime = ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_AD_TIME');
                if($this->param['isAd'])
                {
                    if((time() + $speedupTime) >= $timestamp)
                    {
                        $this->player->setData('tree','lv',$lv+1);
                        $this->player->setData('tree','state',0);
                        $this->player->setData('tree','timestamp', 0);
                        TaskService::getInstance()->setVal($this->player,46,$lv+1,'set');
                    }else{
    
                        $timestamp -= $speedupTime;
                        $this->player->setData('tree','timestamp', $timestamp);
                    }
    
                    $this->player->setArg(Consts::TREE_SPEED_UP_CD_TIME, time() + ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_AD_COLD_TIME'), 'reset');
                    $result = [
                        'goods'     => $this->player->getGoodsInfo(),
                        'remain'    => $this->player->getGoods($cost['gid']),
                        'tree'      => TreeService::getInstance()->getShowTree($this->player),
                    ];
                }else{
                    //元宝
                    $result = '元宝数量不足';
                    $hasNum = $this->player->getGoods($cost['gid']);
                    if( $hasNum >= $cost['num'])
                    {
    
                        $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                        $this->player->goodsBridge($reward,'军旗升级加速-元宝',$hasNum);
    
                        if((time() + $speedupTime) >= $timestamp)
                        {
                            $this->player->setData('tree','lv',$lv+1);
                            $this->player->setData('tree','state',0);
                            $this->player->setData('tree','timestamp', 0);
                            TaskService::getInstance()->setVal($this->player,46,$lv+1,'set');
                        }else{
        
                            $timestamp -= $speedupTime;
                            $this->player->setData('tree','timestamp', $timestamp);
                        }
    
                        $this->player->setArg(Consts::TREE_SPEED_UP_CD_TIME, time() + ConfigParam::getInstance()->getFmtParam('DREAM_UPGRADE_SPEEDUP_AD_COLD_TIME'), 'reset');
                        $result = [
                            'goods'         => $this->player->getGoodsInfo(),
                            'remain'        => $this->player->getGoods($cost['gid']),
                            'tree'          => TreeService::getInstance()->getShowTree($this->player),
                        ];
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}