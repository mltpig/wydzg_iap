<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Service\ComradeService;
use App\Api\Controller\BaseController;

class EnergyAdd  extends BaseController
{

    public function index()
    {
        $isAd   = $this->param['isAd'];
        $number = $this->param['number'];

        $hasNum = $this->player->getArg(Consts::COMRADE_ENERGY);
        if($isAd)
        {
            //体力上限值
            $max = ConfigRole::getInstance()->getOne($this->player->getData('role','lv'))['destiny_energy'];
            $result = '当前体力已达上限，无需再看广告';
            if($max > $hasNum)
            {
                $adNum   = $this->player->getArg(Consts::COMRADE_AD_COUNT);
                $adLimit = ConfigParam::getInstance()->getFmtParam('DESTINY_ENERGY_FREE_REFRESH_TIME');
                $result = '已达今日广告上限';
                if($adLimit > $adNum)
                {
                    $this->player->setArg(Consts::COMRADE_ENERGY_TIME,1,'unset');

                    $this->player->setArg(Consts::COMRADE_ENERGY,$max,'reset');
                    $this->player->setArg(Consts::COMRADE_AD_COUNT,$adNum+1,'reset');
                    $result = [
                        'param'     => [
                            Consts::RENSHENTANG      => $this->player->getArg(Consts::RENSHENTANG),
                            Consts::COMRADE_ENERGY   => $this->player->getArg(Consts::COMRADE_ENERGY),
                            Consts::COMRADE_AD_COUNT => $this->player->getArg(Consts::COMRADE_AD_COUNT),
                        ],
                        'remain_time'  => ComradeService::getInstance()->getCountdown($this->player),
                        'isAd'      => $isAd,
                    ];
                }
            }
        }else{
            $result = '物品数量不足';
            if($this->player->getGoods(Consts::RENSHENTANG) >= $number)
            {
                $cost = [ [ 'type' => GOODS_TYPE_1,'gid' => Consts::RENSHENTANG,'num' => -$number ] ];
                $this->player->goodsBridge($cost,'补充体力');

                $this->player->setArg(Consts::COMRADE_ENERGY,($number*5)+$hasNum,'reset');

                $nowEnergy = $this->player->getArg(Consts::COMRADE_ENERGY);
                $max = ConfigRole::getInstance()->getOne($this->player->getData('role','lv'))['destiny_energy'];

                if($nowEnergy >= $max) $this->player->setArg(Consts::COMRADE_ENERGY_TIME,1,'unset');

                $result = [
                    'param'     => [
                        Consts::RENSHENTANG      => $this->player->getArg(Consts::RENSHENTANG),
                        Consts::COMRADE_ENERGY   => $this->player->getArg(Consts::COMRADE_ENERGY),
                        Consts::COMRADE_AD_COUNT => $this->player->getArg(Consts::COMRADE_AD_COUNT),
                    ],
                    'remain_time'  => ComradeService::getInstance()->getCountdown($this->player),
                    'isAd'      => $isAd,
                ];
            }
        }

        $this->sendMsg( $result );
    }

}