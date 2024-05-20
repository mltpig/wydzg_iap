<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigPets;
use App\Api\Table\ConfigParam;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;

//放生
class Reset extends BaseController
{

    public function index()
    {
        $bagid  = $this->param['id'];

        $bag    = $this->player->getData('pet','bag');
        
        $result = '未解锁';
        if(array_key_exists($bagid,$bag) && $bag[$bagid])
        {

            $result = '最低等级，无法重置';
            if($bag[$bagid]['lv'] > 1)
            {

                $resetCost = ConfigParam::getInstance()->getFmtParam('PET_RESET_COST');
                
                $detail = $bag[$bagid];
                $config = ConfigPets::getInstance()->getOne( $detail['id'] );
                $cost   = $resetCost[ $config['quality'] -1 ];
                $result = '数量不足';
                $has = $this->player->getGoods($cost['gid']);
                if( $has >= $cost['num'] )
                {
                    $cost_list = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                    $this->player->goodsBridge($cost_list,'副将重置',$has);

                    $lvCost = $config['level_cost'];
                    $reNum = PetService::getInstance()->getUpLvTotalCost($detail['lv']-1,$lvCost['num']);
    
                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $lvCost['gid'],'num' => $reNum ] ];
                    $this->player->goodsBridge($reward,'副将重置',$this->player->getGoods($lvCost['gid']));

                    $detail['lv'] = 1;
                    $this->player->setPet('bag',$bagid,$detail,'multiSet');
        
                    $result = [ 
                        'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                        'reward' => [ ['type' => GOODS_TYPE_1 , 'gid' => $lvCost['gid'],'num' => $reNum] ],
                        'remain' => $this->player->getGoods($cost['gid']),
                    ];
                }
            }
        }

        $this->sendMsg( $result );
    }

}