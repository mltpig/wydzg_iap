<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigParadiseReward;
use App\Api\Service\ParadisService;
use App\Api\Service\TaskService;

//采集自己物资
class CollectGoods extends BaseController
{

    public function index()
    {
        $id  = $this->param['id'];
        $num = $this->param['num'];
        $paradise = $this->player->getData('paradise');

        $result = '工人体力不足';
        if($paradise['worker']['energy'] > 0)
        {
            $result = '该物品不存在';
            if(array_key_exists($id,$paradise['list']))
            {
                $goodsDetail = $paradise['list'][$id];

                $result = '该物品已过期';
                if($goodsDetail['gid'] != -1)
                {
                    $result = '该物品已有人采集中';
                    //一个人采集或追加
                    $goodsConfig =  ConfigParadiseReward::getInstance()->getOne($goodsDetail['gid']);
                    $list =  ConfigParam::getInstance()->getFmtParam('HOMELAND_SINGLE_BOX_MOUSE_LIMIT_CONFIG');
                    $countList = ParadisService::getInstance()->getWorkerTaskCount($paradise['worker']['list']);
                    $admin  = array_key_exists('a',$goodsDetail['player']) ? $goodsDetail['player']['a'] : [];

                    if(!$admin || $list[$goodsConfig['level']-1] > count($countList['i'][ $admin['id'] ]) || $list[$goodsConfig['level']-1] > $num)
                    {
                        $result = '工人超过采集上限';
                        if($list[$goodsConfig['level']-1] >= $num)
                        {

                            $addNum    = ParadisService::getInstance()->getWorkerStatus($paradise['worker']['energy']);
                            $needTime  = ParadisService::getInstance()->getGoodsNeedTime($goodsDetail['gid'],$addNum);            
                            $workerIds = ParadisService::getInstance()->getFreeWorker( $paradise['worker']['list'] );
                            if($admin)
                            {
                                $result = '正在采集中';
                                //加 减
                                $widCount = count($goodsDetail['player']['a']['wid']);
                                if($num != $widCount)
                                {
                                    $remainTime = abs($goodsDetail['player']['a']['time'] + $goodsDetail['player']['a']['len'] - time());
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
                                                $goodsDetail['player']['a']['wid'][] = $wid;
                                                $this->player->setParadise('worker','list',$wid,[ 'uid' => 'i','id' => $id ],'set');
                                            }
                                            //
                                            // 30 2  60
                                            // 30 * 20 / 3 => 

                                            $goodsDetail['player']['a']['time'] =  time();
                                            $goodsDetail['player']['a']['len']  =  div( $remainTime * $widCount , $num );
                                            
                                        }
                                    }else{
                                        $index = abs($diff);
                                        while ($index > 0) 
                                        {
                                            $wid = array_pop($goodsDetail['player']['a']['wid']);
                                            $this->player->setParadise('worker','list',$wid,[],'set');
                                            $index--;
                                        }
                                        //
                                        // 20 3  60
                                        // 20 * 3  /  2 => 30 
                                        $goodsDetail['player']['a']['time'] =  time();
                                        $goodsDetail['player']['a']['len']  =  div( $remainTime * $widCount, $num);
                                    }

                                    $this->player->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                                    $this->player->check();

                                    $result = ParadisService::getInstance()->getShowData( $this->player );
                                }
                                
                            }else{
                                $result = '无空闲工人';
                                if(count($workerIds) >=  $num)
                                {
                                    TaskService::getInstance()->setVal($this->player,67,1,'add');

                                    $len =  div( $needTime - $goodsDetail['drift'], $num);
                                    $useWorker = [];
                                    $i = 0;
                                    foreach ($workerIds as $key => $wid) 
                                    {
                                        if($i >= $num) continue;
                                        $i++;
                                        $useWorker[] = $wid;
                                        $this->player->setParadise('worker','list',$wid,[ 'uid' => 'i','id' => $id ],'set');
                                    }
                                    $goodsDetail['player']['a'] = [
                                        'wid' => $useWorker ,'uid' => 'i' ,
                                        'id'   => $id,'time' => time(),'len' => $len,
                                        'head' => $this->player->getData('user','head'),
                                        'nickname' => $this->player->getData('user','nickname'),
                                    ];
                                    $this->player->setParadise('list',$id,'player',$goodsDetail['player'],'set');
                                    $this->player->check();
                                    $result = ParadisService::getInstance()->getShowData( $this->player );
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