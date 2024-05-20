<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigPets;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\PetService;

class Wish extends BaseController
{

    public function index()
    {
        $id  = $this->param['id'];

        $pools  = ConfigPets::getInstance()->getQualityWeight(5);
        
        $result = '心愿池未解锁';
        if(array_key_exists($id,$pools))
        {
            $this->player->setPet('wish',0,$id,'set');

            $result = [
                'pet' => PetService::getInstance()->getPetFmtData( $this->player ),
            ];
        }

        $this->sendMsg( $result );
    }

}