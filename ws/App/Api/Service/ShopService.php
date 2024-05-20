<?php
namespace App\Api\Service;
use App\Api\Table\ConfigShop;
use EasySwoole\Component\CoroutineSingleTon;

class ShopService
{
    use CoroutineSingleTon;

    public function getShowList(PlayerService $playerSer,int $shopType):array
    {
        $list  = [];
        $config  = ConfigShop::getInstance()->getAll($shopType);
        foreach ($config as $id => $value) 
        {
            $list[] = [
                'id'        => $id,
                'name'      => $value['name'],
                'remain'    => $playerSer->getArg($id),
                'buy_limit' => $value['buy_limit'],
                'price'     => $value['price'],
                'reward'    => $value['reward'],
            ];
        }

        return $list;
    }

    public function dailyReset(PlayerService $playerSer,int $nowTime,int $lastTime):void
    {
        //所有商品
        $config  = ConfigShop::getInstance()->getAll(0);
        foreach ($config as $shopid => $value) 
        {
            if($value['buy_limit'] == -1) continue;

            switch ($value['buy_limit_type']) 
            {
                case 1://日
                    $playerSer->setArg($shopid,1,'unset');
                    break;
                case 2://周
                    if(date('W',$nowTime) !== date('W',$lastTime)) $playerSer->setArg($shopid,1,'unset');
                    break;
                case 3://月
                    if(date('m',$nowTime) !== date('m',$lastTime)) $playerSer->setArg($shopid,1,'unset');
                    break;
            }
        }
        
    }

    public function getShopRedPointInfo(PlayerService $playerSer):array
    {
        //100000085
        $red = [false];
        
        if(empty($playerSer->getArg(100000085))) $red[0] = true;

        return [
            '100000085' => $red[0],
        ];
    }

}
