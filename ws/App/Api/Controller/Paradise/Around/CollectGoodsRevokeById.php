<?php
namespace App\Api\Controller\Paradise\Around;
use App\Api\Controller\BaseController;
use App\Api\Service\ParadisService;
use App\Api\Service\PlayerService;

//采集自己物资
class CollectGoodsRevokeById extends BaseController
{

    public function index()
    {
        $rid  = $this->param['rid'];
        $id   = $this->param['id'];

        list($_prefix,$uid,$site)  = explode(':',$rid);

        $aroundPlayerSer = new PlayerService($uid,$site);

        $aroundParadise = $aroundPlayerSer->getData('paradise');
        
        $result = '该物品不存在';
        if(array_key_exists($id,$aroundParadise['list']))
        {
            $goodsDetail = $aroundParadise['list'][$id];

            $result = '该物品已过期';
            if($goodsDetail['gid'] != -1)
            {
                $result = '当前未采集';
                if(array_key_exists('g',$goodsDetail['player']))
                {
                    
                    $remainTime = abs($goodsDetail['player']['g']['time'] + $goodsDetail['player']['g']['len'] - time());
                    
                    $drift  = $goodsDetail['player']['g']['len'] - $remainTime + $goodsDetail['drift'];

                    // $workerCount = count($goodsDetail['player']['g']['wid']);

                    foreach ($goodsDetail['player']['g']['wid'] as $wid) 
                    {
                        $this->player->setParadise('worker','list',$wid,[],'set');
                    }

                    unset($goodsDetail['player']['g']);

                    $aroundPlayerSer->setParadise('list',$id,'drift',$drift,'set');
                    $aroundPlayerSer->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                    $aroundPlayerSer->saveData(['paradise']);
                    
                    $result = [
                        'list' => ParadisService::getInstance()->getAroundPlayerInfo( ['uid' => $uid,'site' => $site] )
                    ];
                }
            }

        }
        



        $this->sendMsg( $result );
    }

}