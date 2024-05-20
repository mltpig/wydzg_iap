<?php
namespace App\Api\Controller\Paradise\Around;
use App\Api\Table\ConfigParam;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\ParadisService;

//刷新自己物资
class Refresh extends BaseController
{

    public function index()
    {

        $time = $this->player->getArg(Consts::HOMELAND_TARGET_REFRESH_TIME);
        $result = '当前时间未冷却';
        if(!$time)
        {
            $timeLen = ConfigParam::getInstance()->getFmtParam('HOMELAND_TARGET_REFRESH_TIME'); 

            $this->player->setArg(Consts::HOMELAND_TARGET_REFRESH_TIME, time() + $timeLen,'reset');

            $new = ParadisService::getInstance()->getAroundList(3);
    
            $this->player->setParadise('around','pos','refresh',$new,'set');
    
            $around  = $this->player->getData('paradise','around');
            $workers = $this->player->getData('paradise','worker')['list'];

            $result =  [ 'list' => ParadisService::getInstance()->getAroundInfo($around,$workers) ];
        }


        $this->sendMsg($result );
    }

}