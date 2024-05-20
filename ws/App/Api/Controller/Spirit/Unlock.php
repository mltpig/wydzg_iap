<?php
namespace App\Api\Controller\Spirit;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSpirits;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\TaskService;

class Unlock extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $id     = $param['id'];

        $config      = ConfigSpirits::getInstance()->getOne($id);
        $result = 'ID 暂未开放';
        if($config)
        {
            $num         = $config['compound_num'];
            $compound_id = $config['compound_item_id'];
            $result = '碎片数量不足';
            if($this->player->getGoods($compound_id) >= $num)
            {
                $old = [
                    'id' => $id, 'state' => 1, 'lv' => 1,
                ];
    
                $this->player->setSpirit('bag',$id,$old,'multiSet');
                
                $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $compound_id,'num' => -$num ] ];
                $this->player->goodsBridge($reward,'红颜解锁',$id );

                $this->player->setHead(4,0,$id,'push');
    
                TaskService::getInstance()->setVal($this->player,69,1,'add');
    
                $result = [ 
                    'remain' => $this->player->getGoods($compound_id),
                    'spirit' => SpiritService::getInstance()->getSpiritFmtData( $this->player, $this->player->getArg( Consts::SPIRIT_AD_TAG )),
                    'head'   => $this->player->getData('head'),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}