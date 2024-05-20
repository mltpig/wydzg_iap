<?php
namespace App\Api\Controller\Paradise\Around;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigParadiseReward;
use App\Api\Service\ParadisService;
use App\Api\Service\PlayerService;
use App\Api\Service\TaskService;

//采集自己物资
class CollectGoods extends BaseController
{

    public function index()
    {
        $around     = $this->player->getData('paradise','around');
        $playerInfo = ParadisService::getInstance()->existsPlayer( $around,$this->param['rid'] );

        $result = '无该邻居数据';
        if($playerInfo)
        {
            $id   = $this->param['id'];
            $num  = $this->param['num'];

            $paradise = $this->player->getData('paradise');
    
            $result = '工人体力不足';
            if($paradise['worker']['energy'] > 0)
            {

                $aroundPlayerSer = new PlayerService($playerInfo['uid'],$playerInfo['site']);
            
                $aroundParadise  = $aroundPlayerSer->getData('paradise');
                $aroundPLayerKey = $aroundPlayerSer->getData('playerKey');

                $result = '该物品不存在';
                if(array_key_exists($id,$aroundParadise['list']))
                {
                    $goodsDetail = $aroundParadise['list'][$id];
    
                    $result = '该物品已过期';
                    if($goodsDetail['gid'] != -1)
                    {
                        $result = '该物品已有人采集中';

                        //一个人采集或追加
                        $goodsConfig =  ConfigParadiseReward::getInstance()->getOne($goodsDetail['gid']);
                        $list =  ConfigParam::getInstance()->getFmtParam('HOMELAND_SINGLE_BOX_MOUSE_LIMIT_CONFIG');
                        $countList = ParadisService::getInstance()->getWorkerTaskCount($paradise['worker']['list']);

                        $guest  = array_key_exists('g',$goodsDetail['player']) ? $goodsDetail['player']['g'] : [];
                        //未采集，有人采集并且是自己
                        $playerKey = $this->player->getData('playerKey');
                        if(!$guest || $guest && $guest['uid'] == $playerKey)
                        {
                            $result = '工人超过采集上限';
                            if($list[$goodsConfig['level']-1] >= $num)
                            {
    
                                $addNum    = ParadisService::getInstance()->getWorkerStatus($paradise['worker']['energy']);
                                $needTime  = ParadisService::getInstance()->getGoodsNeedTime($goodsDetail['gid'],$addNum);            
                                $workerIds = ParadisService::getInstance()->getFreeWorker( $paradise['worker']['list'] );
                                if($guest)
                                {
                                    $result = '正在采集中';
                                    //加 减
                                    $widCount = count($goodsDetail['player']['g']['wid']);
                                    if($num != $widCount)
                                    {
                                        $remainTime = abs($goodsDetail['player']['g']['time'] + $goodsDetail['player']['g']['len'] - time());
                                        //加
                                        $diff = $num - $widCount;
                                        if($diff > 0 )
                                        {    
                                            $result = '无空闲工人';
                                            if(count($workerIds) >=  $diff)
                                            {
                                                $i = 0;
                                                foreach ($workerIds as $key => $wid) 
                                                {
                                                    if($i >= $diff) continue;
                                                    $i++;
                                                    $goodsDetail['player']['g']['wid'][] = $wid;
                                                    $this->player->setParadise('worker','list',$wid,[ 'uid' => $aroundPLayerKey,'id' => $id ],'set');
                                                }
                                                //
                                                // 30 2  60
                                                // 30 * 20 / 3 => 
                                                $goodsDetail['player']['g']['time'] =  time();
                                                $goodsDetail['player']['g']['len']  =  div( $remainTime * $widCount , $num );
                                                
                                            }
                                        }else{
                                            $index = abs($diff);
                                            while ($index > 0) 
                                            {
                                                $wid = array_pop($goodsDetail['player']['g']['wid']);
                                                $this->player->setParadise('worker','list',$wid,[],'set');
                                                $index--;
                                            }
                                            //
                                            // 20 3  60
                                            // 20 * 3  /  2 => 30 
                                            $goodsDetail['player']['g']['time'] =  time();
                                            $goodsDetail['player']['g']['len']  =  div( $remainTime * $widCount, $num);
                                        }
    
                                        $aroundPlayerSer->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                                        $aroundPlayerSer->saveData(['paradise']);
    
                                        $result = [
                                            'list' => ParadisService::getInstance()->getAroundPlayerInfo( $playerInfo )
                                        ];
                                    }
                                    
                                }else{
                                    $result = '无空闲工人';
                                    if(count($workerIds) >=  $num)
                                    {
                                        // TaskService::getInstance()->setVal($this->player,67,1,'add');
    
                                        $len =  div( $needTime - $goodsDetail['drift'], $num);
                                        $useWorker = [];
                                        $i = 0;
                                        foreach ($workerIds as $key => $wid) 
                                        {
                                            if($i >= $num) continue;
                                            $i++;
                                            $useWorker[] = $wid;
                                            $this->player->setParadise('worker','list',$wid,[ 'uid' => $aroundPLayerKey,'id' => $id ],'set');
                                        }

                                        $goodsDetail['player']['g'] = [
                                            'wid' => $useWorker ,'uid' => $playerKey ,
                                            'id'   => $id,'time' => time(),'len' => $len,'active' => 1,
                                            'head' => $this->player->getData('user','head'),
                                            'nickname' => $this->player->getData('user','nickname'),
                                        ];
                                        $aroundPlayerSer->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                                        $aroundPlayerSer->saveData(['paradise']);

                                        $result = [
                                            'list' => ParadisService::getInstance()->getAroundPlayerInfo( $playerInfo )
                                        ];
                                    }

                                }
                            }
    
                        }

                    }
                }
            }

        }

        $this->sendMsg( $result );
    }

}