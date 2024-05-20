<?php
namespace App\Api\Controller\Pet;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\PetService;

//升级
class Get extends BaseController
{

    public function index()
    {

        $result = [
            'pet' 	   => PetService::getInstance()->getPetFmtData($this->player),
        ];

        $this->sendMsg( $result );
    }

}