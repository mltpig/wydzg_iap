<?php
namespace App\Api\Controller\Player;
use App\Api\Controller\BaseController;
use App\Api\Service\RoleService;
use App\Api\Utils\Consts;

class InitBelong extends BaseController
{

    public function index()
    {
        $belong = $this->param['belong'];

        $result = '已初始化';
        if( !$this->player->getArg(Consts::CHARA_BELONG) )
        {
            //设置
            $this->player->setArg(Consts::CHARA_BELONG,$belong,'reset');

            $result = [
                'chara' 	=> RoleService::getInstance()->getCharaFmt($this->player),
                'user'		=> $this->player->getUserInfo(),
                'head' 		=> $this->player->getData('head'),
            ];
        }

        $this->sendMsg( $result);
    }

}