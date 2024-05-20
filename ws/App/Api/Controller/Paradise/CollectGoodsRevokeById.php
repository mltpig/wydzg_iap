<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Service\ParadisService;

//采集自己物资
class CollectGoodsRevokeById extends BaseController
{

    public function index()
    {
        $id  = $this->param['id'];
        $paradise = $this->player->getData('paradise');

        $result = '该物品不存在';
        if(array_key_exists($id,$paradise['list']))
        {
            $goodsDetail = $paradise['list'][$id];

            $result = '该物品已过期';
            if($goodsDetail['gid'] != -1)
            {
                $result = '当前未采集';
                if(array_key_exists('a',$goodsDetail['player']))
                {
                    
                    $remainTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
                    
                    $drift  = $goodsDetail['player']['a']['len'] - $remainTime + $goodsDetail['drift'];

                    // $workerCount = count($goodsDetail['player']['a']['wid']);

                    foreach ($goodsDetail['player']['a']['wid'] as $wid) 
                    {
                        $this->player->setParadise('worker','list',$wid,[],'set');
                    }

                    // $drift = $goodsDetail['drift'];
                    // if($goodsDetail['player'])
                    // {
                    //     $aRemianTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
                    //     $drift  += $goodsDetail['player']['a']['len'] - $aRemianTime;
                    //     $needTime = $value['gid'] != -1 ? $this->getGoodsNeedTime($value['gid'],$addNum) : 0;
                    // }
                    
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