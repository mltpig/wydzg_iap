<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigComrade;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigGoods;
use App\Api\Controller\BaseController;
use App\Api\Service\ComradeService;
use App\Api\Service\TaskService;

//升级
class Upgrade extends BaseController
{
    
    public function index()
    {
        $id      = $this->param['id'];
        $gid     = $this->param['gid'];
        $quick   = $this->param['quick'];

        $comrades = $this->player->getData('comrade');
        
        $result = '未激活';
        if(array_key_exists($id,$comrades) )
        {
            $comrade = $comrades[$id];
            $result = '请先解锁';
            if($comrade['state'])
            {
                $result = '情义值已满';
    
                $config = ConfigParam::getInstance()->getFmtParam('DESTINY_LEVEL_UP');
    
                if(count($config) > $comrade['lv'])
                {
                 
                    $oldLv = $comrade['lv'];
                    $comradeConfig  = ConfigComrade::getInstance()->getOne($id);
                    $cost  = $comradeConfig['cost_id'];

                    $likeGoods = [ Consts::QINPU,Consts::DUKANGJIU,Consts::WANSHOUTAO];
                    if($cost) $likeGoods[] = $cost['gid'];

                    $result = '无效的物品';
                    if(in_array($gid,$likeGoods ))
                    {
                        $totalNum   =  $comrade['lv'] == 1 ? $config[ $comrade['lv']-1] : $config[ $comrade['lv'] ] - $config[ $comrade['lv'] -1 ];
                        
                        $hasNum = $this->player->getGoods($gid);
                        $result = '数量不足';
    
                        if($hasNum > 0)
                        {
                            $costNum = 1;
                            
                            if($quick) $costNum = $hasNum >= 10 ? 10 : $hasNum;
                            
                            //碎片
                            if($cost && $gid == $cost['gid']){
                                $stepNum = $costNum * $cost['step'];
                            }else{
                                $goodsInfo = ConfigGoods::getInstance()->getOne($gid);
                                $stepNum = $costNum * $goodsInfo['params'][0];
                            }

                            TaskService::getInstance()->setVal($this->player,41,$costNum,'add');

                            $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $gid,'num' => -$costNum ] ];
                            $this->player->goodsBridge($reward,'贤士升级',$this->player->getGoods($gid) );

                            $this->player->setComrade($id,'step',$stepNum,'add');
    
                            $detail = $this->player->getData('comrade',$id);
                            $isContinue = true;
                            //限制顶级
                            while ($detail['step'] >= $totalNum && $isContinue) 
                            {
                                $detail['step'] -= $totalNum;
                                $comrade['lv']  += 1;
                                
                                $this->player->setComrade($id,'lv',1,'add');
                                $this->player->setComrade($id,'step',$detail['step'],'set');
    
                                if(count($config) > $comrade['lv'] )
                                {
                                    $totalNum   =  $config[ $comrade['lv'] ] - $config[ $comrade['lv'] -1 ];
                                }else{
                                    $isContinue = false;
                                }

                            }

                            $comrade = $this->player->getData('comrade');
                            //鼠宝
                            if($comradeConfig['talent'] == 60003 && $oldLv != $comrade[$id]['lv'])
                            {
                                $old = ComradeService::getInstance()->getLvStage($oldLv,$comradeConfig['talent_level_up']);
                                $new = ComradeService::getInstance()->getLvStage($comrade[$id]['lv'],$comradeConfig['talent_level_up']);
                                if($old != $new )
                                {
                                    $worker = $this->player->getData('paradise','worker');
                                    $this->player->setParadise('worker','energy',null,$worker['energy'] + 10 ,'set');
                                }
                            }

                            list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);
                            $result = [
                                'list'         => ComradeService::getInstance()->getShowData($this->player,$comrade),
                                'attr_sum'     => $attrSum,
                                'step_num'     => $stepNum,
                                'comrade_need' => ComradeService::getInstance()->getNeedGoods($this->player),
                                'goods'        => $this->player->getGoodsInfo(),
                            ];
                        }
                    }  
                }
            }
        }


        $this->sendMsg( $result );
    }

}