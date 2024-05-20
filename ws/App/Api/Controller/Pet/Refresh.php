<?php
namespace App\Api\Controller\Pet;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\PetService;
use App\Api\Utils\Consts;

//刷新灵兽
class Refresh extends BaseController
{

    public function index()
    {
        $param = $this->param;

        if($param['isAd'])
        {
            $freeCount  = ConfigParam::getInstance()->getFmtParam('PET_FREE_REFRESH_TIME');
            $result = '今日次数已达上限';
            if($freeCount > $this->player->getArg( Consts::PET_AD_TAG ))
            {
                $newPool = PetService::getInstance()->getPetMgRandom($this->player);

                $this->player->setPet('pool',0,$newPool,'set');
                
                $this->player->setArg(Consts::PET_AD_TAG,1,'add');
                
                $result = [
                    'remain' => $freeCount - $this->player->getArg( Consts::PET_AD_TAG ) ,
                    'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                    'isAd'   => $param['isAd'],
                ];
            }
        }else{
            $result = '数量不足';
            $cost = ConfigParam::getInstance()->getFmtParam('PET_REFRESH_COST');
            $has  = $this->player->getGoods($cost['gid']);
            if($has >= $cost['num'] )
            {
                $newPool = PetService::getInstance()->getPetMgRandom($this->player);

                $this->player->setPet('pool',0,$newPool,'set');
                
                $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                $this->player->goodsBridge($costList,'副将刷新',$has);

                $result = [
                    'isAd'   => $param['isAd'],
                    'remain' => $this->player->getGoods($cost['gid']),
                    'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}