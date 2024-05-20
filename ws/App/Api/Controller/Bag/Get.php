<?php
namespace App\Api\Controller\Bag;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigGoods;

//抽卡
class Get  extends BaseController
{

    public function index()
    {
        $list  = [];

        $goods  = $this->player->getData('goods');

        foreach ($goods as $gid => $number) 
        {
            if(!$number) continue;
            if(!$config = ConfigGoods::getInstance()->getOne($gid)) continue;
            if($config['is_hidden']) continue;

            $list[] = ['type' => GOODS_TYPE_1 , 'gid' => $gid ,'num' => $number ];
        }

        $this->sendMsg( [ 'list' => $list ] );
    }

}