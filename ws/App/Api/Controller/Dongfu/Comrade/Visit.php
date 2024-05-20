<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Service\ComradeService;
use App\Api\Service\TaskService;
use App\Api\Controller\BaseController;

class Visit  extends BaseController
{

    public function index()
    {
        
        $result = '当前体力不足';
        $oldEnergy = $this->player->getArg(Consts::COMRADE_ENERGY);
        if($oldEnergy > 0)
        {
            
            $comrades = $this->player->getData('comrade');
            
            $costNum = 1;
            if($this->param['quick']) $costNum = $oldEnergy >= 10 ? 10 : $oldEnergy;
            
            TaskService::getInstance()->setVal($this->player,76,$costNum,'add');
            
            $this->player->setArg(Consts::COMRADE_ENERGY,$oldEnergy - $costNum,'reset');

            
            $comradeIds = ComradeService::getInstance()->getUnlockComrade($comrades);

            list($reward,$visit) = ComradeService::getInstance()->getRandReward($comradeIds,$costNum);
            //普通奖励
            if($reward['normal']) $this->player->goodsBridge($reward['normal'],'贤士寻访',$costNum);
            if($reward['like'])
            {
                foreach ($reward['like'] as $likeList) 
                {
                    foreach ($likeList as $id => $likeNum) 
                    {
                        $this->player->setComrade($id,'step',$likeNum,'add');
                    }
                }

                $upConfig = ConfigParam::getInstance()->getFmtParam('DESTINY_LEVEL_UP');
                $comrade = $this->player->getData('comrade');
                foreach ($comrade as $comradeid => $detail) 
                {

                    if(!$detail['state'] || count($upConfig) <= $detail['lv']) continue;

                    $totalNum   =  $detail['lv'] == 1 ? $upConfig[ $detail['lv']-1] : $upConfig[ $detail['lv'] ] - $upConfig[ $detail['lv'] -1 ];
                    while ($detail['step'] >= $totalNum) 
                    {
                        $detail['step'] -= $totalNum;
                        $detail['lv']  += 1;
                        
                        $this->player->setComrade($comradeid,'lv',1,'add');
                        $this->player->setComrade($comradeid,'step',$detail['step'],'set');
                        $totalNum = $upConfig[ $detail['lv'] ] - $upConfig[ $detail['lv'] -1 ];
                    }
                }
            }

            $nowEnergy = $this->player->getArg(Consts::COMRADE_ENERGY);
            $energyMax = ConfigRole::getInstance()->getOne($this->player->getData('role','lv'))['destiny_energy'];
            //首次
            if(!$this->player->getArg(Consts::COMRADE_ENERGY_TIME) && $energyMax > $nowEnergy) $this->player->setArg(Consts::COMRADE_ENERGY_TIME,time(),'reset');

            $comrade = $this->player->getData('comrade');

            list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);
            $result = [
                'reward'       => $visit,
                'goods'        => $this->player->getGoodsInfo(),
                'list'         => ComradeService::getInstance()->getShowData($this->player,$comrade),
                'attr_sum'     => $attrSum,
                'comrade_need' => ComradeService::getInstance()->getNeedGoods($this->player),
                'param'     => [
                    Consts::RENSHENTANG      => $this->player->getArg(Consts::RENSHENTANG),
                    Consts::COMRADE_ENERGY   => $nowEnergy,
                    Consts::COMRADE_AD_COUNT => $this->player->getArg(Consts::COMRADE_AD_COUNT),
                ],
                'remain_time'  => ComradeService::getInstance()->getCountdown($this->player)
            ];
        }

        $this->sendMsg( $result );
    }

}