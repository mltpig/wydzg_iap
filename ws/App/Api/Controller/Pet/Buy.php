<?php
namespace App\Api\Controller\Pet;
use App\Api\Table\ConfigPets;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\PetService;
use App\Api\Service\TaskService;
//升级
class Buy extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $pool  = $this->player->getData('pet','pool');

        $result = '已解锁';
        if(array_key_exists($param['id'],$pool) )
        {
            $detail  = $pool[$param['id']];

            $result = '已购买';
            if( !$detail['state'] )
            {
                $bagid = PetService::getInstance()->checkFreeBag( $this->player->getData('pet','bag') );

                $result = '副将栏已满';
                if($bagid >= 0)
                {
                    $config = ConfigPets::getInstance()->getOne( $detail['id'] );

                    $cost = $config['create_cost'];

                    $result = '数量不足';
                    $has = $this->player->getGoods($cost['gid']);
                    if($has  >= $cost['num'])
                    {
    
                        $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                        $this->player->goodsBridge($costList,'副将解锁',$has);
        

                        $detail['state'] = 1;
                        $this->player->setPet('pool',$param['id'],$detail,'multiSet');

                        $petInfo = PetService::getInstance()->getBagPetInitFmtData($detail['id'],$config['passive_skill']);
                        
                        $this->player->setPet('bag',$bagid,$petInfo,'multiSet');
                        $this->player->setPet('map',$detail['id'],1,'multiSet');

                        //解锁副将头像
                        $headInfo = $this->player->getData('head');
                        if (!isset($headInfo[5]) || !in_array($config['icon'], $headInfo[5])) {
                            $this->player->setHead(5, 0, $config['icon'], 'push');
                        }

                        TaskService::getInstance()->setVal($this->player,36,1,'add');

                        $result = [ 
                            'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                            'remain' => $this->player->getGoods($cost['gid']),
                            'id'     => $bagid,
                            'head'  => $this->player->getData('head'),
                        ];
                    }
            
                    
                }
            }

        }


        $this->sendMsg( $result );
    }

}