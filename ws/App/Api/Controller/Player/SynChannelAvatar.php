<?php
namespace App\Api\Controller\Player;
use App\Api\Controller\BaseController;

class SynChannelAvatar extends BaseController
{

    public function index()
    {

        $this->player->setData('user','head',['type' => 2,'value' => $this->param['avatar'] ]);
        $this->player->setHead(2,0,$this->param['avatar'],'set');


        $this->sendMsg( [ 
            'user' => $this->player->getUserInfo(),
            'head' => $this->player->getData('head'),
        ] );
    }

}