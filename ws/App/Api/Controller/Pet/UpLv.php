<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigPets;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;
use App\Api\Service\TaskService;

//升级
class UpLv extends BaseController
{

    public function index()
    {
        $bagid  = $this->param['id'];

        $bag    = $this->player->getData('pet','bag');
        
        $result = '未解锁';
        if(array_key_exists($bagid,$bag) && $bag[$bagid])
        {
            $old    = $bag[$bagid];
            $config = ConfigPets::getInstance()->getOne( $old['id'] );

            $result = '已达到顶级';
            if($config['level_limit'] > $old['lv'])
            {

                $cost    = $config['level_cost'];
                $costNum = ceil( $cost['num'] * PetService::getInstance()->getUpLvCost($old['lv'])/1000 );

                $hasNum  = $this->player->getGoods($cost['gid']);
                
                $result = '数量不足';
                if($hasNum >= $costNum )
                {

                    $old['lv']++;
                    $this->player->setPet('bag',$bagid,$old,'multiSet');

                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$costNum ] ];
                    $this->player->goodsBridge($reward,'副将升级',$hasNum);

                    TaskService::getInstance()->setVal($this->player,54,1,'add');
                    $result = [ 
                        'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
                        'remain' => $this->player->getGoods($cost['gid']),
                        'bagid'  => $bagid,
                    ];
                }

    
            }
        }


        $this->sendMsg( $result );
    }

}