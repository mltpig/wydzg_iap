<?php
namespace App\Api\Controller\Cloud;
use App\Api\Table\ConfigCloudStage;
use App\Api\Controller\BaseController;
use App\Api\Service\TaskService;

//升级
class Upgrade extends BaseController
{

    public function index()
    {
        $quick    = $this->param['quick'];
        $cloudid  = $this->param['id'];

        $cloud  = $this->player->getData('cloud');
        
        $result = '未解锁';
        if(in_array($cloudid,$cloud['list']) )
        {
            $config = ConfigCloudStage::getInstance()->getOne($cloud['stage'],$cloud['lv']);

            $result = '已达到顶级';
            if($config)
            {

                if($config['advance_cost'])
                {
                    $cost   = $config['advance_cost'];
                    $hasNum = $this->player->getGoods($cost['gid']);
                    $result = '数量不足';
                    if($hasNum >= $cost['num'])
                    {
    
                        $cost['num'] = -$cost['num'];
                        $this->player->goodsBridge([ $cost ],'附魂升阶',$cloudid.'|'.$cloud['stage'].'|'.$cloud['lv']);

                        $this->player->setCloud('stage',1,'add');
                        $this->player->setCloud('lv',1,'set');
                        $this->player->setCloud('step',0,'set');
                        $result = [ 
                            'cloud'  => $this->player->getData('cloud'),
                            'remain' => $this->player->getGoods($cost['gid']),
                        ];
                    }
                }else{

                    $cost = $config['cost'];
                    $hasNum = $this->player->getGoods($cost['gid']);
                    $result = '数量不足';
                    if($hasNum > 0)
                    {
    
                        $remain = $cost['num'] - $cloud['step'];
                        if($quick){
                            $costNum = $hasNum >= $remain ? $remain : $hasNum;
                        }else{
                            $costNum = 1;
                        }
    
                        //$cost['num'] = -$cost['num'];
                        $this->player->goodsBridge([['gid' => $cost['gid'], 'type' => $cost['type'], 'num' => -$costNum]],'附魂升级',$cloudid.'|'.$cloud['stage'].'|'.$cloud['lv']);

                        $this->player->setCloud('step',$costNum,'add');
                        $this->player->setArg(COUNTER_CLOUD_UP,$costNum,'add');
                        
                        $newStep = $this->player->getData('cloud','step');
    
                        if( $newStep >= $cost['num'])
                        {
                            $this->player->setCloud('lv',1,'add');
                            $this->player->setCloud('step',0,'set');
                        }
                        TaskService::getInstance()->setVal($this->player,30,$costNum,'add');
                        $result = [ 
                            'cloud'  => $this->player->getData('cloud'),
                            'remain' => $this->player->getGoods($cost['gid']),
                        ];
                    }
                }
    
            }
        }


        $this->sendMsg( $result );
    }

}