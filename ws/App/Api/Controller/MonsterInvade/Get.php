<?php
namespace App\Api\Controller\MonsterInvade;
use App\Api\Service\MonsterInvadeService;
use App\Api\Controller\BaseController;

//抽卡
class Get extends BaseController
{

    public function index()
    {

        $result = [
            'monsterid' => MonsterInvadeService::getInstance()->getMonsterid(),
            'count'     => $this->player->getArg(INVADE)
        ];

        $this->sendMsg( $result );
    }

}