<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;

//解锁格子
class UnlockBox extends BaseController
{

    public function index()
    {
        $param = $this->param;

        $nowSize  = count($this->player->getData('pet','bag'));
        $bagsize  = ConfigParam::getInstance()->getFmtParam('PET_BAG_SIZE');
        $bagParam = ConfigParam::getInstance()->getFmtParam('PET_BAG_ADD_COST');
        $count    = $nowSize - $bagsize;
        $cost     = $bagParam[ $count];
        
        if($param['isAd'])
        {
            $this->player->setPet('bag',0,[],'push');
            $result = [ 
                'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                'remain' => $this->player->getGoods($cost['gid']),
                'isAd'   => $param['isAd'],
            ];

        }else{
            $result = '数量不足';
            $has = $this->player->getGoods($cost['gid']);
            if( $has >= $cost['num'] )
            {
                $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                $this->player->goodsBridge($reward,'副将解锁格子',$has);

                $this->player->setPet('bag',0,[],'push');
    
                $result = [ 
                    'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                    'remain' => $this->player->getGoods($cost['gid']),
                    'isAd'   => $param['isAd'],
                ];
            }
        }

        $this->sendMsg( $result );
    }

}