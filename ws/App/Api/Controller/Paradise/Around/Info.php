<?php
namespace App\Api\Controller\Paradise\Around;
use App\Api\Service\ParadisService;
use App\Api\Controller\BaseController;

//获取当前福地物品状态
class Info extends BaseController
{

    public function index()
    {

        $around  = $this->player->getData('paradise','around');
        $playerInfo =  ParadisService::getInstance()->existsPlayer( $around,$this->param['rid'] );

        $result = '无该邻居数据';
        if($playerInfo)
        {
            $result = [
                'list' => ParadisService::getInstance()->getAroundPlayerInfo( $playerInfo )
            ]; 
        }

        $this->sendMsg( $result );
    }

}