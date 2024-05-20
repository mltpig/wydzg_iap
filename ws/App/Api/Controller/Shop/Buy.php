<?php
namespace App\Api\Controller\Shop;
use App\Api\Table\ConfigShop;
use App\Api\Service\TaskService;
use App\Api\Service\ShopService;
use App\Api\Controller\BaseController;


class Buy extends BaseController
{

    public function index()
    {
        $shopid = $this->param['id'];
        $number = $this->param['num'];
        $config = ConfigShop::getInstance()->getOne($shopid);

        $result = '无效的物品ID';
        if($config && $config['shop_type'] == 11)
        {

            $costGid = $config['price']['gid'];
            $costTotal = $config['price']['num'] * $number;

            // $rewardGid   = $config['reward']['gid'];
            // $rewardTotal = $config['reward']['num'] * $number;

            $result = '元宝不足';
            if($this->player->getGoods($costGid) >= $costTotal)
            {
                if($config['buy_limit'] == -1 )
                {

                    $reward   = [];
                    foreach ($config['reward'] as $detail) 
                    {
                        $detail['num'] *= $number;
                        $reward[] = $detail;
                    }
                    
                    $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $costGid,'num' => -$costTotal ] ];
                    $this->player->goodsBridge( array_merge($reward,$costList),'商城购买',$shopid);

                    TaskService::getInstance()->setVal($this->player,1002,$number,'add');

                    $result = [
                        'reward' => $reward,
                        'list'   => ShopService::getInstance()->getShowList($this->player,11),
                        'remain' => $this->player->getGoods($costGid),
                    ];
                }else{

                    $result = '选择数量超过限制';
                    if($config['buy_limit'] >= $number )
                    {
                        $result   = '今日购买次数已达上限';
                        $nowNum   = $this->player->getArg($shopid);
                        if($config['buy_limit'] > $nowNum )
                        {
                            $result   = '总计数量超过限制,请重新选择';
                            $totalNum = $nowNum + $number;
                            
                            if($config['buy_limit'] >= $totalNum)
                            {
                                TaskService::getInstance()->setVal($this->player,1002,$number,'add');

                                $this->player->setArg($shopid,$number,'add');

                                $reward   = [];
                                foreach ($config['reward'] as $detail) 
                                {
                                    $detail['num'] *= $number;
                                    $reward[] = $detail;
                                }
                                
                                $costList = [ [ 'type' => GOODS_TYPE_1,'gid' => $costGid,'num' => -$costTotal ] ];
                                $this->player->goodsBridge( array_merge($reward,$costList),'商城购买',$shopid);

                                $this->player->goodsBridge($reward,'商城购买',$shopid);
        
                                $result = [
                                    'reward' => $reward,
                                    'list'   => ShopService::getInstance()->getShowList($this->player,11),
                                    'remain' => $this->player->getGoods($costGid),
                                ];
                            }
                        }

                    }
                }
            }
        }
        
        $this->sendMsg( $result );
    }

}