<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Service\Module\PetService;
use App\Api\Controller\BaseController;

//升级
class Lock extends BaseController
{

    public function index()
    {
        $bagid  = $this->param['id'];

        $bag    = $this->player->getData('pet','bag');
        
        $result = '未解锁';
        if(array_key_exists($bagid,$bag) && $bag[$bagid])
        {
            $detail = $bag[$bagid];
            $detail['lock'] = $detail['lock'] ? 0 : 1;

            $this->player->setPet('bag',$bagid,$detail,'multiSet');

            $result = [ 
                'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
            ];
        }

        $this->sendMsg( $result );
    }

}