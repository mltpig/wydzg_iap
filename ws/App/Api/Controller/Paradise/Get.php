<?php
namespace App\Api\Controller\Paradise;
use App\Api\Controller\BaseController;
use App\Api\Service\ParadisService;

//获取当前福地物品状态
class Get extends BaseController
{

    public function index()
    {
        $result = ParadisService::getInstance()->getShowData( $this->player );

        $result['isopen'] = intval( $this->param['isopen'] );

        $this->sendMsg( $result );
    }

}