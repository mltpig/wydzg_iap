<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\PetService;
use App\Api\Service\TaskService;

//升级
class Apply extends BaseController
{

    public function index()
    {
        $bagid  = $this->param['id'];

        $bag    = $this->player->getData('pet','bag');
        
        $result = '未解锁';
        if(array_key_exists($bagid,$bag) && $bag[$bagid])
        {

            $this->player->setPet('active',0,$bagid,'set');

            TaskService::getInstance()->setVal($this->player,53,1,'add');

            $result = [ 
                'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
            ];

        }

        $this->sendMsg( $result );
    }

}