<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Service\ParadisService;

//采集自己物资
class CollectGoodsRevokeByWorker extends BaseController
{

    public function index()
    {
        $id  = $this->param['id'];
        $goodsList = $this->player->getData('paradise')['list'];
        $worker    = $this->player->getData('paradise')['worker'];

        $result = '该工人未雇佣';
        if(array_key_exists($id,$worker['list']))
        {
            $id = $worker['list'][$id]['id'];
            $goodsDetail = $goodsList[$id];

            $result = '该物品已过期';
            if($goodsDetail['gid'] != -1)
            {
                $result = '当前未采集';
                if(array_key_exists('a',$goodsDetail['player']))
                {

                    foreach ($goodsDetail['player']['a']['wid'] as $wid) 
                    {
                        $this->player->setParadise('worker','list',$wid,[],'set');
                    }
                    
                    // $drift = $goodsDetail['drift'];
                    // if($goodsDetail['player'])
                    // {
                    //     $aRemianTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
                    //     $addNum   = ParadisService::getInstance()->getWorkerStatus($worker['energy']);
                    //     $needTime = ParadisService::getInstance()->getGoodsNeedTime($goodsDetail['gid'],$addNum);

                    //     $drift  += $needTime - $aRemianTime;
                    // }

                    $remainTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
                    $drift  = $goodsDetail['player']['a']['len'] - $remainTime + $goodsDetail['drift'];

                    unset($goodsDetail['player']['a']);
                    $this->player->setParadise('list',$id,'drift',$drift,'set');
                    $this->player->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                    
                    $result = ParadisService::getInstance()->getShowData( $this->player );

                }
            }

        }
        



        $this->sendMsg( $result );
    }

}