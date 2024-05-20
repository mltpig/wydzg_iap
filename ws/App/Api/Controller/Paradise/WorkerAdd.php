<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Service\ParadisService;
use App\Api\Service\TaskService;

//工人雇佣
class WorkerAdd extends BaseController
{

    public function index()
    {

        $count = count( $this->player->getData('paradise')['worker']['list']);
        $costs = ConfigParam::getInstance()->getFmtParam('HOMELAND_WORKER_COST');
        
        $result = '已达上限';   
        if(count($costs) + 1 >  $count )
        {
            $result = '数量不足'; 
            $cost  = $costs[ $count - 1];
            $has = $this->player->getGoods($cost['gid']);
            if($has >= $cost['num'] )
            {
                $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                $this->player->goodsBridge($costList,'福地工人雇佣',$has);

                $this->player->setParadise('worker','list',$count+1,[],'set');
                
                TaskService::getInstance()->setVal($this->player,24,1,'add');
                
                $result = ParadisService::getInstance()->getShowData( $this->player );
                $result['remain'] = $this->player->getGoods($cost['gid']);
    
            }
        }

        $this->sendMsg( $result );
    }

}