<?php
namespace App\Api\Controller\Dongfu\Comrade;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigParam;
use App\Api\Service\ComradeService;
use App\Api\Controller\BaseController;

//获取妖王挑战参数 
class Get extends BaseController
{

    public function index()
    {

        $comrade = $this->player->getData('comrade');
        list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);

        $result = [
            'list'         => ComradeService::getInstance()->getShowData($this->player,$comrade),
            'attr_sum'     => $attrSum,
            'comrade_need' => ComradeService::getInstance()->getNeedGoods($this->player),
            'config'       => [ 
                'ad_limit'     => intval( ConfigParam::getInstance()->getFmtParam('DESTINY_ENERGY_FREE_REFRESH_TIME')),
                'energy_limit' => ConfigRole::getInstance()->getOne( $this->player->getData('role','lv') )['destiny_energy'],
            ],
            'param'     => [
                Consts::RENSHENTANG      => $this->player->getArg(Consts::RENSHENTANG),
                Consts::COMRADE_ENERGY   => $this->player->getArg(Consts::COMRADE_ENERGY),
                Consts::COMRADE_AD_COUNT => $this->player->getArg(Consts::COMRADE_AD_COUNT),
            ],
            'remain_time'  => ComradeService::getInstance()->getCountdown($this->player)
        ];

        $this->sendMsg( $result );

    }

}